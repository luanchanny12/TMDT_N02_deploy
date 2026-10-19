<?php

namespace App\Services\Marketing;

use App\Mail\RetentionMail;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\RetentionEmail;
use App\Models\Review;
use App\Models\User;
use App\Services\BaseService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Chuỗi 3 email giữ chân, chỉ gửi cho khách đã tích đồng ý nhận email (marketing_consent):
 *  1. +1 giờ sau đăng ký / đặt đơn: thư chào mừng, không giảm giá.
 *  2. +24 giờ sau khi bỏ giỏ hàng: nhắc giỏ hàng kèm chính sách đổi trả và bằng chứng xã hội.
 *  3. +72 giờ (vẫn chưa mua): ưu đãi có thời hạn 48 giờ.
 * Mỗi thư có liên kết hủy đăng ký; mỗi loại chỉ gửi một lần cho một khách.
 */
class RetentionEmailService extends BaseService
{
    /** Quá hạn này thì không gửi bù nữa (tránh gửi thư cũ khi scheduler dừng lâu). */
    private const MAX_AGE_DAYS = 7;

    public const OFFER_PERCENT = 10;

    public const OFFER_MAX_DISCOUNT = 50000;

    public const OFFER_HOURS = 48;

    /** @return array{welcome: int, cart_reminder: int, offer: int} */
    public function run(): array
    {
        $now = CarbonImmutable::now();

        return [
            'welcome' => $this->sendWelcome($now),
            'cart_reminder' => $this->sendCartReminders($now),
            'offer' => $this->sendOffers($now),
        ];
    }

    private function recipients(string $type): Builder
    {
        return User::query()
            ->where('role', 'customer')
            ->where('is_active', true)
            ->where('marketing_consent', true)
            ->whereDoesntHave('retentionEmails', fn ($q) => $q->where('type', $type));
    }

    private function sendWelcome(CarbonImmutable $now): int
    {
        $window = [$now->subDays(self::MAX_AGE_DAYS), $now->subHour()];

        $users = $this->recipients('welcome')
            ->where(fn ($q) => $q->whereBetween('created_at', $window)
                ->orWhereHas('orders', fn ($o) => $o->whereBetween('created_at', $window)))
            ->get();

        return $users->filter(fn (User $user) => $this->deliver($user, 'welcome'))->count();
    }

    private function sendCartReminders(CarbonImmutable $now): int
    {
        $sent = 0;

        foreach ($this->recipients('cart_reminder')->whereHas('cart.items')->get() as $user) {
            $cart = Cart::where('user_id', $user->id)->with('items.product')->first();
            $lastActivity = $this->lastCartActivity($cart);

            if ($lastActivity->greaterThan($now->subDay()) || $lastActivity->lessThan($now->subDays(self::MAX_AGE_DAYS))) {
                continue;
            }
            if ($this->orderedSince($user, $lastActivity)) {
                continue;
            }

            $sent += (int) $this->deliver($user, 'cart_reminder', [
                'items' => $cart->items,
                'proof' => $this->socialProof($cart),
            ]);
        }

        return $sent;
    }

    private function sendOffers(CarbonImmutable $now): int
    {
        $sent = 0;

        $users = $this->recipients('offer')
            ->whereHas('cart.items')
            // Thư 2 gửi lúc +24 giờ → thư 3 sau đó 48 giờ = +72 giờ kể từ khi bỏ giỏ
            ->whereHas('retentionEmails', fn ($q) => $q->where('type', 'cart_reminder')
                ->whereBetween('sent_at', [$now->subDays(self::MAX_AGE_DAYS), $now->subHours(48)]))
            ->get();

        foreach ($users as $user) {
            $cart = Cart::where('user_id', $user->id)->with('items.product')->first();
            if ($this->orderedSince($user, $this->lastCartActivity($cart))) {
                continue;
            }

            $coupon = Coupon::create([
                'code' => 'QUAYLAI-'.Str::upper(Str::random(6)),
                'type' => 'percent',
                'value' => self::OFFER_PERCENT,
                'min_order_amount' => 0,
                'max_discount' => self::OFFER_MAX_DISCOUNT,
                'start_at' => $now,
                'end_at' => $now->addHours(self::OFFER_HOURS),
                'usage_limit' => 1,
                'status' => 'active',
            ]);

            if ($this->deliver($user, 'offer', ['items' => $cart->items, 'coupon' => $coupon], $coupon->id)) {
                $sent++;
            } else {
                $coupon->delete();
            }
        }

        return $sent;
    }

    private function deliver(User $user, string $type, array $data = [], ?int $couponId = null): bool
    {
        // Ghi trước rồi mới gửi: ràng buộc unique chặn gửi trùng khi hai tiến trình chạy cùng lúc
        $record = RetentionEmail::firstOrCreate(
            ['user_id' => $user->id, 'type' => $type],
            ['coupon_id' => $couponId, 'sent_at' => now()],
        );

        if (! $record->wasRecentlyCreated) {
            return false;
        }

        Mail::to($user)->send(new RetentionMail($user, $type, $data));

        return true;
    }

    private function lastCartActivity(Cart $cart): CarbonImmutable
    {
        return CarbonImmutable::parse($cart->items->max('updated_at'));
    }

    private function orderedSince(User $user, CarbonImmutable $since): bool
    {
        return Order::where('user_id', $user->id)->where('created_at', '>=', $since)->exists();
    }

    /** Bằng chứng xã hội thật: số đơn đã giao và một đánh giá đã duyệt (ưu tiên sản phẩm trong giỏ). */
    private function socialProof(Cart $cart): array
    {
        $reviews = Review::query()->with(['user', 'product'])
            ->where('status', 'approved')
            ->where('rating', '>=', 4)
            ->whereNotNull('comment');

        $review = (clone $reviews)->whereIn('product_id', $cart->items->pluck('product_id'))->latest()->first()
            ?? $reviews->latest()->first();

        return [
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'review' => $review,
        ];
    }
}
