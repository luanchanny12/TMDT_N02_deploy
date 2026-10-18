import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import http from 'node:http';
import { createServer, loadConfig } from '../server.js';

let gemini;
let service;
let baseUrl;
let lastGeminiRequest;
let geminiMode = 'ok';

function listen(server) {
    return new Promise((resolve) => server.listen(0, '127.0.0.1', () => resolve(server.address().port)));
}

before(async () => {
    // Gemini giả lập: trả về 2 sự kiện SSE
    gemini = http.createServer((req, res) => {
        let body = '';
        req.on('data', (c) => (body += c));
        req.on('end', () => {
            lastGeminiRequest = { url: req.url, key: req.headers['x-goog-api-key'], body: JSON.parse(body) };
            if (geminiMode === 'error') {
                res.writeHead(500);
                return res.end('boom');
            }
            res.writeHead(200, { 'Content-Type': 'text/event-stream' });
            res.write('data: {"candidates":[{"content":{"parts":[{"text":"Xin "}]}}]}\n\n');
            res.end('data: {"candidates":[{"content":{"parts":[{"text":"chào"}]}}]}\n\n');
        });
    });
    const geminiPort = await listen(gemini);

    service = createServer(loadConfig({
        AI_SERVICE_TOKEN: 'secret-token',
        GEMINI_API_KEY: 'gemini-key',
        GEMINI_MODEL: 'gemini-test',
        GEMINI_BASE_URL: `http://127.0.0.1:${geminiPort}`,
        RATE_LIMIT_PER_MINUTE: '50',
    }));
    baseUrl = `http://127.0.0.1:${await listen(service)}`;
});

after(() => {
    service.close();
    gemini.close();
});

const chat = (body, token = 'secret-token') => fetch(`${baseUrl}/v1/chat/stream`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', ...(token ? { Authorization: `Bearer ${token}` } : {}) },
    body: typeof body === 'string' ? body : JSON.stringify(body),
});

const validBody = {
    systemInstruction: { parts: [{ text: 'Bạn là trợ lý SocialShop' }] },
    contents: [{ role: 'user', parts: [{ text: 'gợi ý áo' }] }],
};

test('health endpoint reports status', async () => {
    const res = await fetch(`${baseUrl}/health`);
    assert.equal(res.status, 200);
    assert.deepEqual(await res.json(), { status: 'ok', service: 'ai-service', model: 'gemini-test', gemini_configured: true });
});

test('rejects missing or wrong token', async () => {
    assert.equal((await chat(validBody, null)).status, 401);
    assert.equal((await chat(validBody, 'wrong-token!')).status, 401);
});

test('validates payload', async () => {
    assert.equal((await chat('not json')).status, 400);
    assert.equal((await chat({ contents: [] })).status, 422);
    assert.equal((await chat({ contents: [{ role: 'admin', parts: [] }] })).status, 422);
});

test('rejects oversized body', async () => {
    const big = { contents: [{ role: 'user', parts: [{ text: 'a'.repeat(70 * 1024) }] }] };
    const res = await chat(big).catch(() => ({ status: 413 }));
    assert.equal(res.status, 413);
});

test('streams Gemini SSE through and keeps the API key server-side', async () => {
    geminiMode = 'ok';
    const res = await chat(validBody);
    assert.equal(res.status, 200);
    assert.match(res.headers.get('content-type'), /text\/event-stream/);
    const text = await res.text();
    assert.match(text, /"text":"Xin "/);
    assert.match(text, /"text":"chào"/);

    assert.equal(lastGeminiRequest.key, 'gemini-key');
    assert.match(lastGeminiRequest.url, /\/v1beta\/models\/gemini-test:streamGenerateContent\?alt=sse/);
    assert.equal(lastGeminiRequest.body.systemInstruction.parts[0].text, 'Bạn là trợ lý SocialShop');
    assert.equal(lastGeminiRequest.body.generationConfig.maxOutputTokens, 256);
});

test('returns 502 when Gemini fails', async () => {
    geminiMode = 'error';
    const res = await chat(validBody);
    assert.equal(res.status, 502);
    geminiMode = 'ok';
});

test('unknown route is 404', async () => {
    assert.equal((await fetch(`${baseUrl}/nope`)).status, 404);
});
