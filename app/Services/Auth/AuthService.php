<?php

namespace App\Services\Auth;

use App\Exceptions\SocialLoginException;
use App\Models\User;
use App\Services\AuditService;
use App\Services\BaseService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthService extends BaseService
{
    /**
     * Đăng ký user mới.
     * Tự động generate referral_code duy nhất.
     * Nếu có referral_code từ URL → lưu lại để xử lý sau khi login.
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'customer',
            'referral_code' => $this->generateUniqueReferralCode(),
            'marketing_consent' => ! empty($data['marketing_consent']),
            'marketing_consent_at' => ! empty($data['marketing_consent']) ? now() : null,
        ]);

        // Nạp giá trị mặc định của DB (is_active = true...) để không bị coi là tài khoản bị khóa
        $user->refresh();

        event(new Registered($user));

        return $user;
    }

    /**
     * Đăng nhập bằng email + password.
     * Trả về true nếu thành công. Ghi vết login_success / login_failed.
     */
    public function login(array $credentials, bool $remember = false): bool
    {
        $success = Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'is_active' => true,
        ], $remember);

        if ($success) {
            AuditService::log('login_success', 'User', Auth::id());
        } else {
            $userId = User::where('email', $credentials['email'])->value('id');
            $user = $userId ? User::find($userId) : null;
            $blocked = $user
                && ! $user->is_active
                && Hash::check($credentials['password'], $user->password);

            AuditService::log(
                $blocked ? 'login_blocked' : 'login_failed',
                'User',
                $userId ? (int) $userId : null,
                ['email' => $credentials['email']],
                $userId ? (int) $userId : null
            );
        }

        return $success;
    }

    public function isBlockedLogin(string $email, string $password): bool
    {
        $user = User::where('email', $email)->first();

        return $user
            && ! $user->is_active
            && Hash::check($password, $user->password);
    }

    /**
     * Đăng xuất user hiện tại. Ghi vết logout.
     */
    public function logout(): void
    {
        AuditService::log('logout', 'User', auth()->id());

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    /**
     * Gửi email reset password.
     * Trả về status string từ Laravel Password broker.
     */
    public function sendResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    /**
     * Reset password bằng token.
     */
    public function resetPassword(array $data): string
    {
        return Password::reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
                'token' => $data['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );
    }

    /**
     * Xử lý Google OAuth callback.
     */
    public function handleGoogleCallback(): User
    {
        return $this->handleSocialCallback('google');
    }

    /**
     * Xử lý callback OAuth của mạng xã hội (google | facebook).
     * Tạo mới hoặc liên kết user theo email.
     *
     * @throws SocialLoginException Khi tài khoản mạng xã hội không cung cấp email.
     */
    public function handleSocialCallback(string $provider): User
    {
        $socialUser = Socialite::driver($provider)->user();

        if (! $socialUser->getEmail()) {
            // Facebook cho phép người dùng từ chối cấp quyền email
            throw new SocialLoginException(
                'Tài khoản '.ucfirst($provider).' chưa cung cấp email. Vui lòng cho phép chia sẻ email hoặc đăng ký bằng email.'
            );
        }

        // Calculate referral code before updateOrCreate because Eloquent doesn't evaluate Closures here
        $existingUser = User::where('email', $socialUser->getEmail())->first();
        $referralCode = $existingUser ? $existingUser->referral_code : $this->generateUniqueReferralCode();

        $user = User::updateOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'provider_id' => $socialUser->getId(),
                'provider' => $provider,
                'name' => $socialUser->getName() ?: $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
                'email_verified_at' => now(),
                'referral_code' => $referralCode,
            ]
        );

        // User vừa tạo: nạp lại giá trị mặc định của DB (is_active = true...),
        // nếu không is_active = null và user mới bị coi là "bị khóa"
        if ($user->wasRecentlyCreated) {
            $user->refresh();
        }

        // Đảm bảo referral_code không bị null (user cũ chưa có)
        if (! $user->referral_code) {
            $user->update(['referral_code' => $this->generateUniqueReferralCode()]);
        }

        return $user;
    }

    /**
     * Generate referral_code ngẫu nhiên và duy nhất (8 ký tự hoa).
     */
    public function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }
}
