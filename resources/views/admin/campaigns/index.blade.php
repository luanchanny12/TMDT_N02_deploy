<x-layouts.admin :title="'Chiến dịch quảng cáo'" :header="'Chiến dịch quảng cáo'">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div class="max-w-2xl text-sm text-[#9a9490]">
            Tạo chiến dịch cho từng nền tảng (Facebook, YouTube, TikTok...), sao chép <strong class="text-[#3d3d3d]">link UTM</strong>
            dán vào bài quảng cáo / mô tả video. Hệ thống tự ghi nhận lượt truy cập và đơn hàng đến từ link;
            nhập <strong class="text-[#3d3d3d]">chi phí</strong> từ trình quản lý quảng cáo để tính ROAS.
        </div>
        <a href="{{ route('admin.campaigns.create') }}"
           class="inline-flex items-center gap-2 bg-[#b8847e] text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-[#a6736d] transition">
            + Tạo chiến dịch
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] overflow-hidden">
        @include('admin.campaigns._table', ['campaigns' => $campaigns])
    </div>

    <p class="text-xs text-[#9a9490] mt-4">
        CR = đơn hàng / lượt truy cập · CPA = chi phí / đơn hàng · ROAS = doanh thu / chi phí (≥ 1x là có lãi trên chi phí quảng cáo).
        Đơn được gán cho chiến dịch cuối cùng khách bấm vào trong 30 ngày.
    </p>
</x-layouts.admin>
