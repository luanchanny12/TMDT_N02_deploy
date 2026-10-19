<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Đồng ý nhận email tiếp thị: mặc định KHÔNG (khách phải tự tích chọn)
            $table->boolean('marketing_consent')->default(false)->after('referral_code');
            $table->timestamp('marketing_consent_at')->nullable()->after('marketing_consent');
        });

        Schema::create('retention_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30); // welcome | cart_reminder | offer
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->timestamp('sent_at');

            // Mỗi loại thư chỉ gửi một lần cho một khách
            $table->unique(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_emails');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['marketing_consent', 'marketing_consent_at']);
        });
    }
};
