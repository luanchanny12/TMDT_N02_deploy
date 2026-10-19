<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_inventory_page_lists_data_purpose_basis_retention_and_sharing(): void
    {
        $this->get(route('pages.data-inventory'))
            ->assertOk()
            ->assertSee('Bảng đăng ký dữ liệu cá nhân')
            ->assertSee('91/2025/QH15')
            ->assertSeeInOrder(['Dữ liệu', 'Mục đích xử lý', 'Cơ sở xử lý', 'Thời gian lưu trữ', 'Bên được chia sẻ'])
            ->assertSee('Gemini') // nội dung chat gửi sang Google được công bố
            ->assertSee('VNPay');

        $this->get(route('home'))->assertSee(route('pages.data-inventory'), false);
    }

    public function test_terms_state_contract_formation_time_and_current_laws(): void
    {
        $this->get(route('pages.terms'))
            ->assertOk()
            ->assertSee('Thời điểm giao kết hợp đồng')
            ->assertSee('122/2025/QH15')
            ->assertDontSee('52/2013');

        $this->get(route('pages.privacy'))
            ->assertOk()
            ->assertSee('91/2025/QH15')
            ->assertDontSee('13/2023');
    }

    public function test_hotline_is_consistent_across_pages(): void
    {
        foreach (['home', 'pages.about', 'pages.privacy', 'pages.return-policy', 'pages.faq', 'pages.buying-guide'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('1900-');
        }
    }

    public function test_prune_command_enforces_published_retention(): void
    {
        AnalyticsEvent::create(['type' => 'visit', 'session_hash' => 'old', 'created_at' => now()->subMonths(25)]);
        AnalyticsEvent::create(['type' => 'visit', 'session_hash' => 'new', 'created_at' => now()->subMonths(2)]);
        AuditLog::forceCreate(['action' => 'login_success', 'created_at' => now()->subMonths(13)]);
        AuditLog::forceCreate(['action' => 'login_success', 'created_at' => now()->subMonth()]);

        $this->artisan('data:prune')->assertSuccessful();

        $this->assertSame(['new'], AnalyticsEvent::pluck('session_hash')->all());
        $this->assertSame(1, AuditLog::count());
    }
}
