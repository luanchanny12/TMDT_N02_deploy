<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount('orders');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->input('status')) {
            $query->where('is_active', $status === 'active');
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:customer,admin',
            'is_active' => 'required|boolean',
        ]);

        if ($user->id === $request->user()->id && $validated['role'] !== 'admin') {
            return back()
                ->withErrors(['role' => 'Không thể tự hạ quyền của chính mình xuống Customer.'])
                ->withInput();
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'Cập nhật người dùng thành công!');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Không thể tự xóa tài khoản của chính mình!');
        }

        if ($user->isAdmin()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Không thể xóa tài khoản quản trị viên! Hãy khóa tài khoản nếu cần.');
        }

        if ($user->orders()->exists()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Người dùng đã có đơn hàng. Không thể xóa — hãy khóa tài khoản để giữ lịch sử đơn.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Đã xóa người dùng!');
    }

    public function toggleStatus(Request $request, User $user)
    {
        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể khóa tài khoản quản trị viên!',
            ], 403);
        }

        $user->update(['is_active' => ! $user->is_active]);

        AuditService::log(
            $user->is_active ? 'user_unblocked' : 'user_blocked',
            'User',
            $user->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $user->fresh()->is_active,
            ]);
        }

        return back()->with('success', 'Cập nhật trạng thái tài khoản thành công!');
    }
}
