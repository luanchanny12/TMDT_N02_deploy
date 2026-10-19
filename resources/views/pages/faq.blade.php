<x-layouts.app :title="'Câu hỏi thường gặp (FAQ)'">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12">
        <h1 class="font-serif text-3xl font-bold text-[#3d3d3d] mb-2">Câu hỏi thường gặp</h1>
        <p class="text-[#9a9490] text-sm mb-10">Tìm câu trả lời nhanh cho thắc mắc phổ biến nhất.</p>

        <div class="space-y-3" x-data="{ open: null }">

            {{-- Item 1 --}}
            <div class="border border-[#efe8e3] rounded-xl overflow-hidden">
                <button @click="open === 1 ? open = null : open = 1"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-[#faf7f4] transition-colors">
                    <span class="text-sm font-medium text-[#3d3d3d]">Làm thế nào để theo dõi đơn hàng?</span>
                    <svg class="w-5 h-5 text-[#9a9490] flex-shrink-0 transition-transform duration-200"
                         :class="open === 1 ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 1" x-collapse x-cloak class="px-5 pb-4 text-sm text-[#6b6560] leading-relaxed">
                    Đăng nhập vào tài khoản, vào mục <strong class="text-[#3d3d3d]">"Đơn hàng"</strong> trong menu người dùng.
                    Tại đây bạn có thể xem chi tiết trạng thái đơn hàng: Đang xử lý &rarr; Đang giao &rarr; Đã giao thành công.
                    Bạn cũng sẽ nhận được email thông báo khi trạng thái đơn hàng thay đổi.
                </div>
            </div>

            {{-- Item 2 --}}
            <div class="border border-[#efe8e3] rounded-xl overflow-hidden">
                <button @click="open === 2 ? open = null : open = 2"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-[#faf7f4] transition-colors">
                    <span class="text-sm font-medium text-[#3d3d3d]">Thời gian giao hàng là bao lâu?</span>
                    <svg class="w-5 h-5 text-[#9a9490] flex-shrink-0 transition-transform duration-200"
                         :class="open === 2 ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 2" x-collapse x-cloak class="px-5 pb-4 text-sm text-[#6b6560] leading-relaxed">
                    Nội thành TP.HCM: 1&ndash;2 ngày làm việc. Các thành phố lớn: 2&ndash;4 ngày làm việc.
                    Ngoại thành và khu vực khác: 3&ndash;7 ngày làm việc. Thời gian giao hàng có thể thay đổi vào dịp lễ, Tết.
                </div>
            </div>

            {{-- Item 3 --}}
            <div class="border border-[#efe8e3] rounded-xl overflow-hidden">
                <button @click="open === 3 ? open = null : open = 3"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-[#faf7f4] transition-colors">
                    <span class="text-sm font-medium text-[#3d3d3d]">SocialShop chấp nhận những phương thức thanh toán nào?</span>
                    <svg class="w-5 h-5 text-[#9a9490] flex-shrink-0 transition-transform duration-200"
                         :class="open === 3 ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 3" x-collapse x-cloak class="px-5 pb-4 text-sm text-[#6b6560] leading-relaxed">
                    Hiện tại SocialShop hỗ trợ 2 phương thức thanh toán:
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li><strong class="text-[#3d3d3d]">COD (Thanh toán khi nhận hàng):</strong> Thanh toán bằng tiền mặt cho nhân viên giao hàng.</li>
                        <li><strong class="text-[#3d3d3d]">VNPay:</strong> Thanh toán qua cổng VNPay với thẻ ATM nội địa, Visa, MasterCard hoặc quét QR code.</li>
                    </ul>
                </div>
            </div>

            {{-- Item 4 --}}
            <div class="border border-[#efe8e3] rounded-xl overflow-hidden">
                <button @click="open === 4 ? open = null : open = 4"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-[#faf7f4] transition-colors">
                    <span class="text-sm font-medium text-[#3d3d3d]">Tôi có thể đổi trả sản phẩm không?</span>
                    <svg class="w-5 h-5 text-[#9a9490] flex-shrink-0 transition-transform duration-200"
                         :class="open === 4 ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 4" x-collapse x-cloak class="px-5 pb-4 text-sm text-[#6b6560] leading-relaxed">
                    Có. Bạn có thể đổi trả trong vòng <strong class="text-[#3d3d3d]">7 ngày</strong> kể từ ngày nhận hàng nếu sản phẩm bị lỗi,
                    sai size hoặc không đúng mô tả. Xem chi tiết tại trang <a href="{{ route('pages.return-policy') }}" class="text-[#b8847e] hover:underline">Chính sách đổi trả</a>.
                </div>
            </div>

            {{-- Item 5 --}}
            <div class="border border-[#efe8e3] rounded-xl overflow-hidden">
                <button @click="open === 5 ? open = null : open = 5"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-[#faf7f4] transition-colors">
                    <span class="text-sm font-medium text-[#3d3d3d]">Tôi quên mật khẩu, phải làm sao?</span>
                    <svg class="w-5 h-5 text-[#9a9490] flex-shrink-0 transition-transform duration-200"
                         :class="open === 5 ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 5" x-collapse x-cloak class="px-5 pb-4 text-sm text-[#6b6560] leading-relaxed">
                    Trên trang Đăng nhập, nhấn <strong class="text-[#3d3d3d]">"Quên mật khẩu"</strong> và nhập email đã đăng ký.
                    Hệ thống sẽ gửi hướng dẫn đặt lại mật khẩu qua email của bạn.
                </div>
            </div>

            {{-- Item 6 --}}
            <div class="border border-[#efe8e3] rounded-xl overflow-hidden">
                <button @click="open === 6 ? open = null : open = 6"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-[#faf7f4] transition-colors">
                    <span class="text-sm font-medium text-[#3d3d3d]">Tôi có thể hủy đơn hàng sau khi đặt không?</span>
                    <svg class="w-5 h-5 text-[#9a9490] flex-shrink-0 transition-transform duration-200"
                         :class="open === 6 ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 6" x-collapse x-cloak class="px-5 pb-4 text-sm text-[#6b6560] leading-relaxed">
                    Bạn có thể hủy đơn hàng khi trạng thái đang ở <strong class="text-[#3d3d3d]">"Đang xử lý"</strong>.
                    Liên hệ hotline <strong class="text-[#3d3d3d]">077123456</strong> hoặc gửi email kèm mã đơn hàng để yêu cầu hủy.
                    Đơn hàng đã chuyển sang <strong class="text-[#3d3d3d]">"Đang giao"</strong> sẽ không thể hủy.
                </div>
            </div>

            {{-- Item 7 --}}
            <div class="border border-[#efe8e3] rounded-xl overflow-hidden">
                <button @click="open === 7 ? open = null : open = 7"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-[#faf7f4] transition-colors">
                    <span class="text-sm font-medium text-[#3d3d3d]">Làm thế nào để đánh giá sản phẩm?</span>
                    <svg class="w-5 h-5 text-[#9a9490] flex-shrink-0 transition-transform duration-200"
                         :class="open === 7 ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 7" x-collapse x-cloak class="px-5 pb-4 text-sm text-[#6b6560] leading-relaxed">
                    Sau khi nhận hàng thành công, bạn có thể viết đánh giá trực tiếp trên trang chi tiết sản phẩm.
                    Chọn số sao, viết bình luận và gửi. Lưu ý: chỉ tài khoản đã mua sản phẩm mới có thể đánh giá.
                </div>
            </div>

            {{-- Item 8 --}}
            <div class="border border-[#efe8e3] rounded-xl overflow-hidden">
                <button @click="open === 8 ? open = null : open = 8"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-[#faf7f4] transition-colors">
                    <span class="text-sm font-medium text-[#3d3d3d]">Sản phẩm có cam kết chất lượng không?</span>
                    <svg class="w-5 h-5 text-[#9a9490] flex-shrink-0 transition-transform duration-200"
                         :class="open === 8 ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 8" x-collapse x-cloak class="px-5 pb-4 text-sm text-[#6b6560] leading-relaxed">
                    SocialShop cam kết 100% sản phẩm chính hãng và kiểm tra chất lượng nghiêm ngặt trước khi giao hàng.
                    Nếu phát hiện sản phẩm lỗi hoặc không đúng mô tả, bạn hoàn toàn có thể đổi trả miễn phí trong 7 ngày.
                </div>
            </div>

        </div>
    </div>
</x-layouts.app>
