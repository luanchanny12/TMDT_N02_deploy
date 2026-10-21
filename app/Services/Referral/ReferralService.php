<?php

namespace App\Services\Referral;

use App\Models\Order;
use App\Models\Referral;
use App\Models\User;
use App\Services\BaseService;

class ReferralService extends BaseService
{
    /**
     * Tạo bản ghi Referral khi User B đăng ký thành công qua mã giới thiệu.
     */
    public function createReferral(User $newUser, string $referralCode): void
    {
        // Tránh user tự giới thiệu chính mình
        if ($newUser->referral_code === $referralCode) {
            return;
        }

        $referrer = User::where('referral_code', $referralCode)->first();

        // Không tìm thấy người giới thiệu hoặc User B đã được giới thiệu bởi người khác
        if (! $referrer || Referral::where('referred_user_id', $newUser->id)->exists()) {
            return;
        }

        Referral::create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $newUser->id,
            'referral_code' => $referralCode,
            'order_id' => null, // Sẽ gắn khi có đơn hàng đầu tiên
            'commission' => 0,
            'status' => 'pending',
        ]);
    }

    /**
     * Gắn đơn hàng vào Referral (Chỉ đơn hàng ĐẦU TIÊN) và tính tạm hoa hồng.
     * Trạng thái referral vẫn là pending.
     */
    public function attachOrderToReferral(Order $order): void
    {
        // Kiểm tra xem User này có đang nằm trong chương trình Referral không
        $referral = Referral::where('referred_user_id', $order->user_id)
            ->whereNull('order_id')
            ->where('status', 'pending')
            ->first();

        // Nếu khách chưa từng được giới thiệu, kiểm tra xem có mua qua link Giỏ hàng trưng bày / Cookie của người khác không
        if (! $referral) {
            $cookieCode = request()->cookie(config('referral.cookie_name', 'referral_code'));
            if ($cookieCode) {
                $referrer = User::where('referral_code', $cookieCode)->first();
                if ($referrer && $referrer->id !== $order->user_id && ! Referral::where('referred_user_id', $order->user_id)->exists()) {
                    $percent = config('referral.commission_percent', 5);
                    $baseAmount = max(0, $order->subtotal - $order->discount);
                    $commission = (int) round($baseAmount * ($percent / 100));

                    Referral::create([
                        'referrer_id' => $referrer->id,
                        'referred_user_id' => $order->user_id,
                        'referral_code' => $cookieCode,
                        'order_id' => $order->id,
                        'commission' => $commission,
                        'status' => 'pending',
                    ]);
                }
            }

            return;
        }

        // Tính tiền hoa hồng = (Subtotal - Discount) * commission_percent. KHÔNG tính phí ship.
        $percent = config('referral.commission_percent', 5);
        $baseAmount = max(0, $order->subtotal - $order->discount);
        $commission = (int) round($baseAmount * ($percent / 100));

        $referral->update([
            'order_id' => $order->id,
            'commission' => $commission,
        ]);
    }

    /**
     * Hoàn tất Referral khi đơn hàng được giao (delivered).
     */
    public function completeReferral(Order $order): void
    {
        $referral = Referral::where('order_id', $order->id)
            ->where('status', 'pending')
            ->first();

        if ($referral) {
            $referral->update(['status' => 'completed']);
        }
    }

    /**
     * Hủy Referral nếu đơn hàng bị hủy (cancelled).
     */
    public function cancelReferral(Order $order): void
    {
        $referral = Referral::where('order_id', $order->id)
            ->where('status', 'pending')
            ->first();

        if ($referral) {
            $referral->update(['status' => 'cancelled']);
        }
    }

    /**
     * Lấy thống kê hoa hồng và lịch sử giới thiệu của User
     */
    public function getUserStatistics(User $user): array
    {
        $referrals = Referral::with('referredUser')
            ->where('referrer_id', $user->id)
            ->latest()
            ->get();

        return [
            'pending_commission' => $referrals->where('status', 'pending')->sum('commission'),
            'completed_commission' => $referrals->where('status', 'completed')->sum('commission'),
            'history' => $referrals,
        ];
    }
}
