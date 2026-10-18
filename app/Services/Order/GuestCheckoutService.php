<?php

namespace App\Services\Order;

use App\Models\User;
use App\Services\AuditService;
use App\Services\Auth\AuthService;
use App\Services\BaseService;
use App\Services\Cart\CartService;
use App\Services\Referral\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Mua hàng không cần đăng ký trước: tạo tài khoản theo email khách nhập ở checkout,
 * đăng nhập ngay để dùng chung luồng đặt hàng / thanh toán / email, rồi gửi link đặt mật khẩu
 * để khách theo dõi đơn sau này.
 */
class GuestCheckoutService extends BaseService
{
    private const MAX_ACCOUNTS_PER_HOUR = 5;

    public function __construct(
        private AuthService $authService,
        private CartService $cartService,
        private ReferralService $referralService
    ) {}

    /**
     * @throws ValidationException khi email đã có tài khoản hoặc tạo quá nhiều tài khoản từ một IP
     */
    public function createAccountAndLogin(Request $request, string $email, string $name): User
    {
        $email = Str::lower(trim($email));

        // Không gắn đơn vào tài khoản có sẵn khi chưa đăng nhập (tránh đặt hàng/xem đơn thay người khác)
        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'guest_email' => 'Email này đã có tài khoản. Vui lòng đăng nhập để đặt hàng (giỏ hàng sẽ được giữ nguyên).',
            ]);
        }

        $key = 'guest-checkout:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ACCOUNTS_PER_HOUR)) {
            throw ValidationException::withMessages([
                'guest_email' => 'Bạn đã đặt quá nhiều đơn khách vãng lai, vui lòng thử lại sau hoặc đăng ký tài khoản.',
            ]);
        }
        RateLimiter::hit($key, 3600);

        $user = $this->authService->register([
            'name' => $name,
            'email' => $email,
            'password' => Str::random(40), // khách tự đặt mật khẩu qua email
        ]);

        $referralCode = $request->cookie(config('referral.cookie_name', 'referral_code'));
        if ($referralCode) {
            $this->referralService->createReferral($user, $referralCode);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $this->cartService->mergeGuestCart($user->id);

        AuditService::log('guest_checkout_account_created', 'User', $user->id, ['email' => $email], $user->id);

        try {
            Password::sendResetLink(['email' => $email]);
        } catch (\Throwable $e) {
            Log::warning('Không gửi được email đặt mật khẩu cho khách vãng lai: '.$e->getMessage());
        }

        session()->flash('success', 'Đã tạo tài khoản cho '.$email.'. Kiểm tra email để đặt mật khẩu và theo dõi đơn hàng.');

        return $user;
    }
}
