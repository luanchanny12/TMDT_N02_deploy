<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Coupon;
use App\Services\Order\PricingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
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

        // Macro whereLike tương thích đa CSDL (PostgreSQL dùng ILIKE không phân biệt hoa thường, MySQL/SQLite dùng LIKE)
        QueryBuilder::macro('whereLike', function ($column, $value) {
            /** @var QueryBuilder $this */
            $operator = $this->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            return $this->where($column, $operator, $value);
        });

        QueryBuilder::macro('orWhereLike', function ($column, $value) {
            /** @var QueryBuilder $this */
            $operator = $this->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            return $this->orWhere($column, $operator, $value);
        });

        EloquentBuilder::macro('whereLike', function ($column, $value) {
            /** @var EloquentBuilder $this */
            $operator = $this->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            return $this->where($column, $operator, $value);
        });

        EloquentBuilder::macro('orWhereLike', function ($column, $value) {
            /** @var EloquentBuilder $this */
            $operator = $this->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            return $this->orWhere($column, $operator, $value);
        });

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

            // Thanh ưu đãi: ngưỡng freeship thật và chỉ quảng bá mã khi mã còn dùng được
            $welcome = Coupon::where('code', Coupon::WELCOME_CODE)
                ->where('status', 'active')
                ->where(fn ($q) => $q->whereNull('start_at')->orWhere('start_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('end_at')->orWhere('end_at', '>=', now()))
                ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
                ->first();

            $view->with('promoFreeShip', app(PricingService::class)->nationwideFreeShippingThreshold());
            $view->with('promoCoupon', $welcome);
        });
    }
}
