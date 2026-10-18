<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * Dữ liệu cho chuông thông báo (gọi định kỳ bằng JavaScript).
     */
    public function feed(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->limit(8)->get()->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? '',
                'message' => $n->data['message'] ?? '',
                'level' => $n->data['level'] ?? 'info',
                'read' => $n->read_at !== null,
                'time' => $n->created_at->diffForHumans(),
                'open_url' => route('admin.notifications.open', $n->id),
            ]),
        ]);
    }

    /**
     * Đánh dấu đã đọc rồi chuyển tới trang liên quan (đơn hàng, sản phẩm...).
     */
    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // Chỉ chuyển hướng nội bộ: cùng scheme + host + port với website (chặn kiểu localhost.evil.com)
        $site = parse_url(url('/'));
        $target = is_string($url) ? parse_url($url) : false;
        $internal = $target
            && ($target['scheme'] ?? null) === ($site['scheme'] ?? null)
            && ($target['host'] ?? null) === ($site['host'] ?? null)
            && ($target['port'] ?? null) === ($site['port'] ?? null);

        return $internal
            ? redirect()->to($url)
            : redirect()->route('admin.notifications.index');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }
}
