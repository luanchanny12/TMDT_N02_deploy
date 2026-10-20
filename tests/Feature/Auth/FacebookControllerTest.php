<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\FacebookProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class FacebookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.facebook.client_id' => 'fb-id',
            'services.facebook.client_secret' => 'fb-secret',
            'services.facebook.redirect' => 'http://localhost/auth/facebook/callback',
        ]);
    }

    private function fakeFacebookUser(?string $email, string $avatar = 'http://avatar.test/b.jpg'): void
    {
        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn('fb-123');
        $socialUser->shouldReceive('getName')->andReturn('Nguyễn Văn B');
        $socialUser->shouldReceive('getEmail')->andReturn($email);
        $socialUser->shouldReceive('getAvatar')->andReturn($avatar);

        $provider = Mockery::mock(FacebookProvider::class);
        $provider->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with('facebook')->andReturn($provider);
    }

    public function test_redirects_to_facebook(): void
    {
        $this->get(route('auth.facebook'))->assertRedirect();
    }

    public function test_callback_creates_user_and_logs_in(): void
    {
        $this->fakeFacebookUser('b@example.com');

        $this->get(route('auth.facebook.callback'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Đăng nhập bằng Facebook thành công!');

        $user = User::where('email', 'b@example.com')->firstOrFail();
        $this->assertSame('facebook', $user->provider);
        $this->assertNotNull($user->referral_code);
        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_accepts_long_avatar_url(): void
    {
        // URL ảnh từ CDN Facebook thường dài hơn 255 ký tự
        $avatar = 'https://scontent.fbcdn.net/v/t1/photo.jpg?'.str_repeat('a', 400);
        $this->fakeFacebookUser('b@example.com', $avatar);

        $this->get(route('auth.facebook.callback'))->assertRedirect(route('home'));

        $this->assertSame($avatar, User::where('email', 'b@example.com')->value('avatar'));
    }

    public function test_callback_without_email_asks_user_to_share_email(): void
    {
        $this->fakeFacebookUser(null);

        $this->get(route('auth.facebook.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_locked_account_cannot_login_with_facebook(): void
    {
        User::factory()->create(['email' => 'b@example.com', 'is_active' => false]);
        $this->fakeFacebookUser('b@example.com');

        $this->get(route('auth.facebook.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_facebook_button_shown_only_when_configured(): void
    {
        $this->get(route('login'))->assertSee('Đăng nhập với Facebook');
        $this->get(route('register'))->assertSee('Đăng ký với Facebook');

        config(['services.facebook.client_id' => null]);
        $this->get(route('login'))->assertDontSee('Đăng nhập với Facebook');
        $this->get(route('register'))->assertDontSee('Đăng ký với Facebook');
    }
}
