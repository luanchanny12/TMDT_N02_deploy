<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Referral;
use App\Models\User;
use App\Services\Referral\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ReferralServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReferralService $referralService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->referralService = app(ReferralService::class);
    }

    public function test_create_referral_ignores_self_referral()
    {
        $user = User::factory()->create(['referral_code' => 'MYCODE']);
        $this->referralService->createReferral($user, 'MYCODE');
        $this->assertDatabaseEmpty('referrals');
    }

    public function test_create_referral_ignores_invalid_code()
    {
        $user = User::factory()->create();
        $this->referralService->createReferral($user, 'INVALID');
        $this->assertDatabaseEmpty('referrals');
    }

    public function test_create_referral_ignores_already_referred_user()
    {
        $referrer = User::factory()->create(['referral_code' => 'REF123']);
        $user = User::factory()->create();

        Referral::create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $user->id,
            'referral_code' => 'OLDCODE',
            'commission' => 0,
            'status' => 'pending',
        ]);

        $this->referralService->createReferral($user, 'REF123');
        $this->assertDatabaseCount('referrals', 1);
    }

    public function test_create_referral_success()
    {
        $referrer = User::factory()->create(['referral_code' => 'REF123']);
        $user = User::factory()->create();

        $this->referralService->createReferral($user, 'REF123');

        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $referrer->id,
            'referred_user_id' => $user->id,
            'referral_code' => 'REF123',
            'order_id' => null,
            'status' => 'pending',
            'commission' => 0,
        ]);
    }

    public function test_attach_order_ignores_if_no_pending_referral()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->referralService->attachOrderToReferral($order);
        // No exception, just returns
        $this->assertTrue(true);
    }

    public function test_attach_order_to_referral_success()
    {
        Config::set('referral.commission_percent', 10);
        $referrer = User::factory()->create();
        $user = User::factory()->create();

        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $user->id,
            'referral_code' => 'TEST',
            'commission' => 0,
            'status' => 'pending',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'subtotal' => 200000,
            'discount' => 50000,
        ]);

        $this->referralService->attachOrderToReferral($order);

        $this->assertEquals($order->id, $referral->fresh()->order_id);
        $this->assertEquals(15000, $referral->fresh()->commission);
    }

    public function test_complete_referral()
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $user->id,
            'referral_code' => 'TEST',
            'order_id' => $order->id,
            'commission' => 10000,
            'status' => 'pending',
        ]);

        $this->referralService->completeReferral($order);
        $this->assertEquals('completed', $referral->fresh()->status);
    }

    public function test_cancel_referral()
    {
        $referrer = User::factory()->create();
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $user->id,
            'referral_code' => 'TEST',
            'order_id' => $order->id,
            'commission' => 10000,
            'status' => 'pending',
        ]);

        $this->referralService->cancelReferral($order);
        $this->assertEquals('cancelled', $referral->fresh()->status);
    }

    public function test_get_user_statistics()
    {
        $referrer = User::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Referral::create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $user1->id,
            'referral_code' => 'TEST',
            'commission' => 50000,
            'status' => 'completed',
        ]);

        Referral::create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $user2->id,
            'referral_code' => 'TEST',
            'commission' => 20000,
            'status' => 'pending',
        ]);

        $stats = $this->referralService->getUserStatistics($referrer);

        $this->assertEquals(20000, $stats['pending_commission']);
        $this->assertEquals(50000, $stats['completed_commission']);
        $this->assertCount(2, $stats['history']);
    }

    public function test_attach_order_creates_showcase_referral_when_cookie_present(): void
    {
        $referrer = User::factory()->create(['referral_code' => 'SHOWCASE99']);
        $buyer = User::factory()->create();

        // Simulate cookie present on request
        request()->cookies->set(config('referral.cookie_name', 'referral_code'), 'SHOWCASE99');

        $order = Order::factory()->create([
            'user_id' => $buyer->id,
            'subtotal' => 1000000,
            'discount' => 0,
        ]);

        $this->referralService->attachOrderToReferral($order);

        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $referrer->id,
            'referred_user_id' => $buyer->id,
            'order_id' => $order->id,
            'commission' => 50000,
            'status' => 'pending',
        ]);
    }
}
