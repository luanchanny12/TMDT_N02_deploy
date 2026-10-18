<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chiến dịch quảng cáo (Facebook, YouTube, TikTok...) — link gắn UTM trỏ về website
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('platform', 30); // facebook | youtube | tiktok | google | zalo | instagram | other
            $table->string('utm_source', 50);
            $table->string('utm_medium', 50)->default('cpc');
            $table->string('utm_campaign', 100)->unique();
            $table->string('landing_path')->default('/');
            $table->unsignedBigInteger('spend')->default(0); // chi phí quảng cáo thực tế (₫), nhập từ trình quản lý quảng cáo
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->enum('status', ['active', 'paused', 'ended'])->default('active');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Sự kiện hành vi: visit (phiên truy cập), product_view, add_to_cart, purchase
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);
            $table->string('session_hash', 64); // sha256(session id) — không lưu IP
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 50)->nullable(); // utm_source hoặc domain giới thiệu; null = truy cập trực tiếp
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('value')->nullable();
            $table->string('path')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'created_at']);
            $table->index(['campaign_id', 'type']);
            $table->index('session_hash');
        });

        // Gán đơn hàng cho chiến dịch (last-click) để tính doanh thu & ROAS
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('traffic_source', 50)->nullable()->after('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campaign_id');
            $table->dropColumn('traffic_source');
        });

        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('campaigns');
    }
};
