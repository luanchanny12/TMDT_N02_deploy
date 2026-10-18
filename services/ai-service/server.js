// AI chat microservice: nhận yêu cầu chat từ web Laravel, gọi Gemini (streaming SSE) và chuyển tiếp kết quả.
// Không dùng thư viện ngoài — chỉ Node.js >= 20 (fetch, node:http).
import http from 'node:http';
import { timingSafeEqual } from 'node:crypto';
import { pathToFileURL } from 'node:url';

const MAX_BODY_BYTES = 64 * 1024;

export function loadConfig(env = process.env) {
    return {
        port: Number(env.PORT || 3000),
        token: env.AI_SERVICE_TOKEN || '',
        geminiKey: env.GEMINI_API_KEY || '',
        model: env.GEMINI_MODEL || 'gemini-3.5-flash',
        geminiBaseUrl: (env.GEMINI_BASE_URL || 'https://generativelanguage.googleapis.com').replace(/\/+$/, ''),
        timeoutMs: Number(env.UPSTREAM_TIMEOUT_MS || 20000),
        rateLimitPerMinute: Number(env.RATE_LIMIT_PER_MINUTE || 120),
    };
}

function log(level, message, extra = {}) {
    console.log(JSON.stringify({ time: new Date().toISOString(), level, message, ...extra }));
}

function sendJson(res, status, body) {
    res.writeHead(status, { 'Content-Type': 'application/json; charset=utf-8' });
    res.end(JSON.stringify(body));
}

function tokenMatches(expected, header) {
    if (!expected) return false;
    const given = (header || '').replace(/^Bearer\s+/i, '');
    const a = Buffer.from(given);
    const b = Buffer.from(expected);
    return a.length === b.length && timingSafeEqual(a, b);
}

function readBody(req) {
    return new Promise((resolve, reject) => {
        let size = 0;
        const chunks = [];
        req.on('data', (chunk) => {
            size += chunk.length;
            if (size > MAX_BODY_BYTES) {
                reject(Object.assign(new Error('Payload too large'), { status: 413 }));
                req.destroy();
                return;
            }
            chunks.push(chunk);
        });
        req.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
        req.on('error', reject);
    });
}

function validatePayload(payload) {
    if (!payload || typeof payload !== 'object') return 'Body phải là JSON object';
    if (!Array.isArray(payload.contents) || payload.contents.length === 0 || payload.contents.length > 40) {
        return 'contents phải là mảng 1–40 phần tử';
    }
    for (const item of payload.contents) {
        if (!item || !['user', 'model'].includes(item.role) || !Array.isArray(item.parts)) {
            return 'Mỗi phần tử contents cần role (user|model) và parts';
        }
    }
    return null;
}

export function createServer(config = loadConfig()) {
    const hits = new Map(); // giới hạn tần suất đơn giản theo IP gọi tới

    function rateLimited(ip) {
        const now = Date.now();
        const entry = hits.get(ip) || { start: now, count: 0 };
        if (now - entry.start > 60_000) {
            entry.start = now;
            entry.count = 0;
        }
        entry.count += 1;
        hits.set(ip, entry);
        return entry.count > config.rateLimitPerMinute;
    }

    async function handleChat(req, res) {
        if (!tokenMatches(config.token, req.headers.authorization)) {
            return sendJson(res, 401, { error: 'Unauthorized' });
        }
        if (rateLimited(req.socket.remoteAddress || 'unknown')) {
            return sendJson(res, 429, { error: 'Too many requests' });
        }
        if (!config.geminiKey) {
            return sendJson(res, 503, { error: 'GEMINI_API_KEY chưa được cấu hình' });
        }

        let payload;
        try {
            payload = JSON.parse(await readBody(req));
        } catch (err) {
            return sendJson(res, err.status || 400, { error: err.status ? err.message : 'JSON không hợp lệ' });
        }

        const invalid = validatePayload(payload);
        if (invalid) return sendJson(res, 422, { error: invalid });

        const body = {
            contents: payload.contents,
            ...(payload.systemInstruction ? { systemInstruction: payload.systemInstruction } : {}),
            generationConfig: { temperature: 0.7, maxOutputTokens: 256, ...(payload.generationConfig || {}) },
        };

        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), config.timeoutMs);
        req.on('close', () => controller.abort());
        const started = Date.now();

        try {
            const upstream = await fetch(
                `${config.geminiBaseUrl}/v1beta/models/${encodeURIComponent(config.model)}:streamGenerateContent?alt=sse`,
                {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'x-goog-api-key': config.geminiKey },
                    body: JSON.stringify(body),
                    signal: controller.signal,
                },
            );

            if (!upstream.ok || !upstream.body) {
                log('warn', 'gemini_error', { status: upstream.status });
                return sendJson(res, 502, { error: 'Gemini trả lỗi', status: upstream.status });
            }

            // Chuyển tiếp nguyên luồng SSE của Gemini để phía Laravel xử lý như khi gọi trực tiếp
            res.writeHead(200, {
                'Content-Type': 'text/event-stream; charset=utf-8',
                'Cache-Control': 'no-cache',
                Connection: 'keep-alive',
            });
            for await (const chunk of upstream.body) {
                res.write(chunk);
            }
            res.end();
            log('info', 'chat_completed', { ms: Date.now() - started, model: config.model });
        } catch (err) {
            log('error', 'chat_failed', { error: err.name === 'AbortError' ? 'timeout' : err.message });
            if (!res.headersSent) {
                sendJson(res, 504, { error: 'Gemini không phản hồi' });
            } else {
                res.end();
            }
        } finally {
            clearTimeout(timer);
        }
    }

    return http.createServer((req, res) => {
        const url = new URL(req.url, 'http://localhost');

        if (req.method === 'GET' && url.pathname === '/health') {
            return sendJson(res, 200, {
                status: 'ok',
                service: 'ai-service',
                model: config.model,
                gemini_configured: Boolean(config.geminiKey),
            });
        }

        if (req.method === 'POST' && url.pathname === '/v1/chat/stream') {
            handleChat(req, res).catch((err) => {
                log('error', 'unhandled', { error: err.message });
                if (!res.headersSent) sendJson(res, 500, { error: 'Internal error' });
            });
            return;
        }

        sendJson(res, 404, { error: 'Not found' });
    });
}

if (import.meta.url === pathToFileURL(process.argv[1] || '').href) {
    const config = loadConfig();
    if (!config.token) {
        log('error', 'AI_SERVICE_TOKEN is required');
        process.exit(1);
    }
    createServer(config).listen(config.port, () => log('info', 'ai-service listening', { port: config.port, model: config.model }));
}
