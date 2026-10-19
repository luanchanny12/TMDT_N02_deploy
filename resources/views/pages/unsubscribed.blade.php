<x-layouts.app :title="'Đã hủy đăng ký email'">
    <div class="max-w-xl mx-auto px-4 py-16 text-center">
        <h1 class="font-serif text-3xl font-bold text-[#3d3d3d] mb-4">Đã hủy đăng ký</h1>
        <p class="text-[#6b6560] leading-relaxed">
            Địa chỉ <strong class="text-[#3d3d3d]">{{ $email }}</strong> sẽ không nhận thêm email giới thiệu sản phẩm, nhắc giỏ hàng hay ưu đãi từ SocialShop.
            Bạn vẫn nhận email xác nhận và cập nhật trạng thái đơn hàng vì đó là thông tin cần thiết cho giao dịch.
        </p>
        <p class="text-sm text-[#9a9490] mt-4">
            Muốn nhận lại? Bật tùy chọn trong trang
            <a href="{{ route('profile') }}" class="text-[#b8847e] underline">Hồ sơ cá nhân</a>.
        </p>
        <a href="{{ route('home') }}" class="inline-block mt-8 bg-[#b8847e] text-white px-6 py-2.5 rounded-lg hover:bg-[#a6736d] transition">Về trang chủ</a>
    </div>
</x-layouts.app>
