<?php

namespace Tests\Feature;

use App\Mail\RetentionMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\RetentionEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RetentionEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function customer(bool $consent = true, array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'marketing_consent' => $consent,
            'marketing_consent_at' => $consent ? now() : null,
        ], $attrs));
    }

    private function abandonCart(User $user): Cart
    {
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'status' => 'active', 'price' => 300000, 'sale_price' => null, 'stock' => 5]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 300000]);

        return $cart;
    }

    private function sendDue(): void
    {
        $this->artisan('marketing:send-retention')->assertSuccessful();
    }

    private function sentTypes(): array
    {
        return RetentionEmail::orderBy('id')->pluck('type')->all();
    }

    public function test_full_sequence_welcome_then_cart_reminder_then_offer(): void
    {
        $user = $this->customer();
        $this->abandonCart($user);

        $this->sendDue();
        $this->assertSame([], $this->sentTypes(), 'Chưa đủ 1 giờ thì chưa gửi gì');

        $this->travel(61)->minutes();
        $this->sendDue();
        $this->assertSame(['welcome'], $this->sentTypes());

        $this->travel(23)->hours();
        $this->sendDue();
        $this->assertSame(['welcome', 'cart_reminder'], $this->sentTypes());
        Mail::assertQueued(RetentionMail::class, fn ($m) => $m->type === 'cart_reminder' && $m->hasTo($user->email));

        $this->travel(47)->hours();
        $this->sendDue();
        $this->assertSame(['welcome', 'cart_reminder'], $this->sentTypes(), 'Thư 3 đúng +72 giờ, chưa tới');

        $this->travel(2)->hours();
        $this->sendDue();
        $this->assertSame(['welcome', 'cart_reminder', 'offer'], $this->sentTypes());

        $coupon = Coupon::sole();
        $this->assertSame(1, $coupon->usage_limit);
        $this->assertTrue($coupon->end_at->equalTo(now()->addHours(48)->startOfSecond()) || $coupon->end_at->diffInMinutes(now()->addHours(48)) < 1);

        // Chạy lại không gửi trùng
        $this->sendDue();
        $this->assertCount(3, $this->sentTypes());
        Mail::assertQueuedCount(3);
    }

    public function test_no_email_without_consent(): void
    {
        $this->abandonCart($this->customer(consent: false));

        $this->travel(4)->days();
        $this->sendDue();

        $this->assertSame([], $this->sentTypes());
        Mail::assertNothingQueued();
    }

    public function test_cart_reminder_skipped_when_customer_already_ordered(): void
    {
        $user = $this->customer();
        $this->abandonCart($user);
        $this->travel(25)->hours();
        Order::factory()->create(['user_id' => $user->id]);

        $this->sendDue();

        $this->assertNotContains('cart_reminder', $this->sentTypes());
    }

    public function test_emails_render_with_unsubscribe_link_and_policy(): void
    {
        $user = $this->customer();
        $cart = $this->abandonCart($user)->load('items.product');

        $reminder = (new RetentionMail($user, 'cart_reminder', ['items' => $cart->items, 'proof' => ['delivered_orders' => 12, 'review' => null]]))->render();
        $this->assertStringContainsString('7 ngày', $reminder);
        $this->assertStringContainsString(route('pages.return-policy'), $reminder);
        $this->assertStringContainsString('12 đơn hàng', $reminder);
        $this->assertStringContainsString('Hủy đăng ký', $reminder);

        $welcome = (new RetentionMail($user, 'welcome'))->render();
        $this->assertStringNotContainsStringIgnoringCase('giảm', $welcome, 'Thư chào mừng không kèm giảm giá');
        $this->assertStringContainsString('huy-dang-ky-email', $welcome);
    }

    public function test_signed_unsubscribe_link_revokes_consent(): void
    {
        $user = $this->customer();
        $url = (new RetentionMail($user, 'welcome'))->unsubscribeUrl;

        $this->get($url)->assertOk()->assertSee('Đã hủy đăng ký');

        $user->refresh();
        $this->assertFalse($user->marketing_consent);
        $this->assertNotNull($user->marketing_consent_at);
    }

    public function test_one_click_post_unsubscribe_and_tampered_link_rejected(): void
    {
        $user = $this->customer();
        $other = $this->customer();

        $this->post(URL::signedRoute('marketing.unsubscribe', ['user' => $user->id]))->assertNoContent();
        $this->assertFalse($user->fresh()->marketing_consent);

        $tampered = str_replace('/'.$user->id.'?', '/'.$other->id.'?', URL::signedRoute('marketing.unsubscribe', ['user' => $user->id]));
        $this->get($tampered)->assertForbidden();
        $this->assertTrue($other->fresh()->marketing_consent);
    }

    public function test_checkout_consent_checkbox_is_unchecked_and_optional(): void
    {
        $user = $this->customer(consent: false);
        $this->abandonCart($user);

        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'A', 'shipping_phone' => '0901234567', 'shipping_address' => 'số 1',
            'shipping_province' => 'Quảng Ninh', 'payment_method' => 'cod',
        ]);

        $html = $this->actingAs($user)->get(route('checkout.review.show'))->assertSee('Tôi đồng ý nhận email')->getContent();
        $this->assertDoesNotMatchRegularExpression('/name="marketing_consent"[^>]*checked/', $html);

        // Không tích vẫn đặt hàng được, và không bị coi là đồng ý
        $this->actingAs($user)->post(route('checkout.confirm'), ['agree_terms' => '1']);
        $this->assertSame(1, Order::count());
        $this->assertFalse($user->fresh()->marketing_consent);
    }

    public function test_checkout_consent_is_recorded_when_ticked(): void
    {
        $user = $this->customer(consent: false);
        $this->abandonCart($user);

        $this->actingAs($user)->post('/checkout/review', [
            'shipping_name' => 'A', 'shipping_phone' => '0901234567', 'shipping_address' => 'số 1',
            'shipping_province' => 'Quảng Ninh', 'payment_method' => 'cod',
        ]);
        $this->actingAs($user)->post(route('checkout.confirm'), ['agree_terms' => '1', 'marketing_consent' => '1']);

        $this->assertTrue($user->fresh()->marketing_consent);
        $this->assertNotNull($user->fresh()->marketing_consent_at);
    }

    public function test_register_defaults_to_no_consent_and_profile_can_toggle(): void
    {
        $this->post(route('register'), [
            'name' => 'Lan', 'email' => 'lan@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ]);
        $user = User::where('email', 'lan@example.com')->sole();
        $this->assertFalse($user->marketing_consent);

        $this->actingAs($user)->put(route('profile.update'), ['name' => 'Lan', 'marketing_consent' => '1']);
        $this->assertTrue($user->fresh()->marketing_consent);

        $this->actingAs($user)->put(route('profile.update'), ['name' => 'Lan', 'marketing_consent' => '0']);
        $this->assertFalse($user->fresh()->marketing_consent);
    }
}
