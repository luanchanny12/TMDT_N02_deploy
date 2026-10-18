<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Đánh dấu dữ liệu demo (do CampaignSeeder tạo) để admin xóa được mà không đụng số liệu thật.
     */
    public function up(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('path')->index();
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('note');
        });

        // Dữ liệu demo đã seed trước khi có cột này: phiên seed có mã sha256("seed-{ngày}-{lượt}")
        $hashes = [];
        for ($d = 0; $d < 14; $d++) {
            for ($v = 0; $v < 70; $v++) {
                $hashes[] = hash('sha256', "seed-{$d}-{$v}");
            }
        }
        foreach (array_chunk($hashes, 200) as $chunk) {
            DB::table('analytics_events')->whereIn('session_hash', $chunk)->update(['is_demo' => true]);
        }

        DB::table('campaigns')
            ->whereIn('utm_campaign', ['sale-thu-2026-fb', 'sneaker-week-tiktok', 'review-tui-xach-yt'])
            ->update(['is_demo' => true]);
    }

    public function down(): void
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropIndex(['is_demo']);
            $table->dropColumn('is_demo');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });
    }
};
