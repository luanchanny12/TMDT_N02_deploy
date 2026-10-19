<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Render/CDN đặt scheme http phía proxy; ép https để asset()/url() không
        // sinh link mixed-content (browser chặn CSS → trang hiện HTML trần).
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Giới hạn đăng nhập sai: 5 lần / 5 phút theo email + IP (TC-01),
        // thêm 20 lần / 5 phút theo IP để chặn dò nhiều email từ 1 máy
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinutes(5, 5)->by(
                    strtolower((string) $request->input('email')).'|'.$request->ip()
                ),
                Limit::perMinutes(5, 20)->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('reviews', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinutes(1, 3)->by($request->ip());
        });

        RateLimiter::for('password', function (Request $request) {
            return Limit::perMinutes(1, 3)->by(
                strtolower((string) $request->input('email')).'|'.$request->ip()
            );
        });

        RateLimiter::for('chat', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        View::composer('components.layouts.app', function ($view) {
            $view->with('navCategories', Category::query()
                ->where('status', 'active')
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q
                    ->where('status', 'active')
                    ->orderBy('sort_order')
                    ->orderBy('name')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get());
        });
    }
}
