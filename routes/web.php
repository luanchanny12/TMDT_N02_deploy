<?php

use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\CampaignController as AdminCampaignController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ChatbotFaqController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\ShippingZoneController as AdminShippingZoneController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\FacebookController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\WishlistController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');

// API docs (Scribe) — postman/openapi sinh bởi `php artisan scribe:generate --no-extraction`
Route::view('/docs', 'scribe.index')->name('scribe');
Route::get('/docs.postman', function () {
    abort_unless(Storage::disk('local')->exists('scribe/collection.json'), 404);

    return new JsonResponse(Storage::disk('local')->get('scribe/collection.json'), json: true);
})->name('scribe.postman');
Route::get('/docs.openapi', function () {
    abort_unless(Storage::disk('local')->exists('scribe/openapi.yaml'), 404);

    return response()->file(Storage::disk('local')->path('scribe/openapi.yaml'));
})->name('scribe.openapi');

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Static Pages
Route::get('/huong-dan-mua-hang', [StaticPageController::class, 'buyingGuide'])->name('pages.buying-guide');
Route::get('/chinh-sach-doi-tra', [StaticPageController::class, 'returnPolicy'])->name('pages.return-policy');
Route::get('/chinh-sach-bao-mat', [StaticPageController::class, 'privacy'])->name('pages.privacy');
Route::get('/dieu-khoan-giao-dich', [StaticPageController::class, 'terms'])->name('pages.terms');
Route::get('/gioi-thieu', [StaticPageController::class, 'about'])->name('pages.about');
Route::get('/faq', [StaticPageController::class, 'faq'])->name('pages.faq');

// Products
Route::get('/danh-muc/{category}', [ProductController::class, 'category'])->name('categories.show');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

// Compare
Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
Route::post('/compare/add/{product:id}', [CompareController::class, 'add'])->name('compare.add');
Route::post('/compare/remove/{product:id}', [CompareController::class, 'remove'])->name('compare.remove');
Route::post('/compare/clear', [CompareController::class, 'clear'])->name('compare.clear');

// Payments (Public Webhook & Return URL)
Route::get('/payment/vnpay/return', [PaymentController::class, 'vnpayReturn'])->name('payment.vnpay.return');
Route::get('/payment/vnpay/ipn', [PaymentController::class, 'vnpayIpn'])->name('payment.vnpay.ipn');

// Auth: Guest only
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');

    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:register');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendLink'])->middleware('throttle:password')->name('password.email');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');

    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

    Route::get('/auth/facebook', [FacebookController::class, 'redirect'])->name('auth.facebook');
    Route::get('/auth/facebook/callback', [FacebookController::class, 'callback'])->name('auth.facebook.callback');
});

// Cart (guest allowed — session cart)
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// Chat Streaming (guest → FAQ-only; auth → Gemini + throttle)
// Checkout bước 1 — mở cho khách vãng lai (tự tạo tài khoản theo email khi gửi thông tin giao hàng)
Route::get('/checkout', [CartController::class, 'checkout'])->name('checkout.index');
Route::post('/checkout/review', [OrderController::class, 'review'])->middleware('throttle:checkout')->name('checkout.review');

Route::post('/chat/stream', [ChatController::class, 'stream'])->middleware('throttle:chat')->name('chat.stream');

// Auth: Authenticated users
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

    // Checkout bắt buộc đăng nhập
    Route::get('/checkout/review', [OrderController::class, 'showReview'])->name('checkout.review.show');
    Route::post('/checkout/confirm', [OrderController::class, 'confirm'])->middleware('throttle:checkout')->name('checkout.confirm');

    // Coupon
    Route::post('/checkout/apply-coupon', [CouponController::class, 'apply'])->name('checkout.apply-coupon');
    Route::post('/checkout/remove-coupon', [CouponController::class, 'remove'])->name('checkout.remove-coupon');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/success', [OrderController::class, 'success'])->name('orders.success');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->middleware('throttle:10,1')->name('orders.cancel');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product:id}/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

    // Reviews
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->middleware('throttle:reviews')->name('reviews.store');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('products', AdminProductController::class)->except(['show']);
    Route::post('/products/{product}/toggle', [AdminProductController::class, 'toggleActive'])->name('products.toggle');
    Route::post('/products/{product}/images', [AdminProductController::class, 'addImages'])->name('products.images.add');
    Route::delete('/products/{product}/images/{image}', [AdminProductController::class, 'deleteImage'])->name('products.images.delete');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');

    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category:id}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category:id}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Reviews moderation
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::patch('/reviews/{review}/reject', [AdminReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    // Audit logs
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');

    // Phân tích dữ liệu (lượt truy cập, chuyển đổi, ROAS) và chiến dịch quảng cáo UTM
    Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');
    Route::resource('campaigns', AdminCampaignController::class)->except(['show']);

    // Cài đặt cửa hàng (thuế VAT, phí vận chuyển)
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::resource('shipping-zones', AdminShippingZoneController::class)->except(['index', 'show']);

    // Coupons (specific routes before wildcard)
    Route::get('/coupons', [AdminCouponController::class, 'index'])->name('coupons.index');
    Route::get('/coupons/create', [AdminCouponController::class, 'create'])->name('coupons.create');
    Route::post('/coupons', [AdminCouponController::class, 'store'])->name('coupons.store');
    Route::delete('/coupons/bulk-delete', [AdminCouponController::class, 'bulkDelete'])->name('coupons.bulk-delete');
    Route::patch('/coupons/bulk-toggle', [AdminCouponController::class, 'bulkToggle'])->name('coupons.bulk-toggle');
    Route::get('/coupons/{coupon}/edit', [AdminCouponController::class, 'edit'])->name('coupons.edit');
    Route::put('/coupons/{coupon}', [AdminCouponController::class, 'update'])->name('coupons.update');
    Route::delete('/coupons/{coupon}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');
    Route::patch('/coupons/{coupon}/toggle', [AdminCouponController::class, 'toggleStatus'])->name('coupons.toggle');
    Route::get('/coupons/{coupon}/usage', [AdminCouponController::class, 'usageHistory'])->name('coupons.usage');

    // Chatbot FAQs (specific routes before wildcard)
    Route::get('/chatbot-faqs', [ChatbotFaqController::class, 'index'])->name('chatbot-faqs.index');
    Route::get('/chatbot-faqs/create', [ChatbotFaqController::class, 'create'])->name('chatbot-faqs.create');
    Route::post('/chatbot-faqs', [ChatbotFaqController::class, 'store'])->name('chatbot-faqs.store');
    Route::get('/chatbot-faqs/{chatbotFaq}/edit', [ChatbotFaqController::class, 'edit'])->name('chatbot-faqs.edit');
    Route::put('/chatbot-faqs/{chatbotFaq}', [ChatbotFaqController::class, 'update'])->name('chatbot-faqs.update');
    Route::delete('/chatbot-faqs/{chatbotFaq}', [ChatbotFaqController::class, 'destroy'])->name('chatbot-faqs.destroy');
    Route::patch('/chatbot-faqs/{chatbotFaq}/toggle', [ChatbotFaqController::class, 'toggleStatus'])->name('chatbot-faqs.toggle');
});
