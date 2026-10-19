<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\Referral\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct(
        private ReferralService $referralService
    ) {}

    public function index()
    {
        $user = Auth::user();
        $referralCode = $user->referral_code ?? strtoupper(uniqid('REF-'));

        if (! $user->referral_code) {
            $user->update(['referral_code' => $referralCode]);
        }

        $stats = $this->referralService->getUserStatistics($user);

        return view('pages.profile', compact('user', 'referralCode', 'stats'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'marketing_consent' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $user->update(collect($validated)->except('marketing_consent')->all());
        if ($request->has('marketing_consent')) {
            $user->setMarketingConsent($request->boolean('marketing_consent'));
        }

        return redirect()->route('profile')->with('success', 'Cập nhật thông tin thành công!');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
            'password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditService::log('password_changed', 'User', $user->id);

        return redirect()->route('profile')->with('success', 'Đổi mật khẩu thành công!');
    }
}
