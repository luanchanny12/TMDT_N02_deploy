<x-layouts.app :title="'Điều Kiện Giao Dịch Chung'">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12">
        <h1 class="font-serif text-3xl font-bold text-[#3d3d3d] mb-8">Điều Kiện Giao Dịch Chung</h1>
        <p class="text-sm text-[#9a9490] mb-8">Cập nhật lần đầu: {{ date('d/m/Y') }} — Áp dụng cho mọi giao dịch trên SocialShop.</p>

        <div class="space-y-10 text-[#6b6560] leading-relaxed text-[15px]">
            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">1. Thông tin các bên</h2>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li><strong class="text-[#3d3d3d]">Bên cung cấp dịch vụ (Người bán):</strong> SocialShop — website bán hàng tích hợp mạng xã hội (social commerce) B2C. Thông tin chi tiết tại <a href="{{ route('pages.about') }}" class="text-[#b8847e] underline">Thông Tin Người Bán</a>.</li>
                    <li><strong class="text-[#3d3d3d]">Bên sử dụng dịch vụ (Người mua):</strong> Cá nhân đăng ký tài khoản và/hoặc đặt hàng trên website SocialShop.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">2. Quy trình giao kết hợp đồng điện tử</h2>
                <p>Giao dịch điện tử (HĐĐT) được giao kết qua các bước bắt buộc trên website:</p>
                <ol class="list-decimal list-inside mt-2 space-y-2 ml-1">
                    <li>Đăng ký / đăng nhập tài khoản;</li>
                    <li>Chọn sản phẩm, thêm vào giỏ hàng;</li>
                    <li>Nhập thông tin giao hàng và chọn phương thức thanh toán;</li>
                    <li><strong class="text-[#3d3d3d]">Xác nhận đơn hàng</strong> — bước xem lại đơn và <strong class="text-[#3d3d3d]">bắt buộc tick đồng ý</strong> với Điều Kiện Giao Dịch Chung này trước khi đặt hàng;</li>
                    <li>Hệ thống gửi email/xác nhận đơn hàng;</li>
                    <li>Giao hàng và hoàn tất giao dịch.</li>
                </ol>
                <p class="mt-2">Việc tick đồng ý ở bước xác nhận đơn được ghi nhận là sự chấp thuận của Người mua đối với các điều khoản này.</p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">3. Phương thức thanh toán</h2>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li><strong class="text-[#3d3d3d]">COD (Thanh toán khi nhận hàng):</strong> trả tiền mặt cho đơn vị vận chuyển khi nhận hàng.</li>
                    <li><strong class="text-[#3d3d3d]">VNPay:</strong> thanh toán trực tuyến qua cổng VNPay (môi trường sandbox thử nghiệm trong khuôn khổ dự án).</li>
                </ul>
                <p class="mt-2">SocialShop <strong class="text-[#3d3d3d]">không lưu trữ</strong> thông tin thẻ ngân hàng của Người mua.</p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">4. Giao hàng &amp; phí vận chuyển</h2>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li>Phạm vi giao hàng: toàn quốc (theo biểu phí đơn vị vận chuyển).</li>
                    @php $pricing = app(\App\Services\Order\PricingService::class); @endphp
                    <li>Phí vận chuyển theo khu vực của tỉnh/thành nhận hàng, từ {{ number_format($pricing->lowestShippingFee(), 0, ',', '.') }}₫; <strong class="text-[#3d3d3d]">miễn phí</strong> toàn quốc cho đơn có giá trị hàng từ {{ number_format($pricing->nationwideFreeShippingThreshold(), 0, ',', '.') }}₫. Phí chính xác hiển thị ở bước thanh toán trước khi xác nhận đơn.</li>
                    <li>Thời gian giao dự kiến: 2–5 ngày làm việc tùy khu vực (kể từ khi đơn được xác nhận).</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">5. Đổi trả &amp; hoàn tiền</h2>
                <p>
                    Chính sách đổi trả, hoàn tiền được công bố tại
                    <a href="{{ route('pages.return-policy') }}" class="text-[#b8847e] underline">Chính Sách Đổi Trả</a>.
                    Khi có khiếu nại, Người mua liên hệ hotline/email theo trang đó.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">6. Quyền &amp; nghĩa vụ các bên</h2>
                <p class="mb-2"><strong class="text-[#3d3d3d]">Quyền của Người mua:</strong></p>
                <ul class="list-disc list-inside space-y-1 ml-1 mb-3">
                    <li>Nhận hàng đúng mô tả, đủ số lượng, đúng thời gian cam kết;</li>
                    <li>Được đổi trả theo chính sách; được bảo mật dữ liệu cá nhân;</li>
                    <li>Khiếu nại khi sản phẩm/ dịch vụ không đúng thỏa thuận.</li>
                </ul>
                <p class="mb-2"><strong class="text-[#3d3d3d]">Nghĩa vụ của Người mua:</strong></p>
                <ul class="list-disc list-inside space-y-1 ml-1 mb-3">
                    <li>Cung cấp thông tin giao hàng chính xác;</li>
                    <li>Thanh toán theo phương thức đã chọn;</li>
                    <li>Kiểm tra hàng khi nhận và thông báo lỗi trong thời hạn đổi trả.</li>
                </ul>
                <p class="mb-2"><strong class="text-[#3d3d3d]">Quyền &amp; nghĩa vụ của SocialShop:</strong> công bố thông tin người bán, bảo mật dữ liệu theo
                    <a href="{{ route('pages.privacy') }}" class="text-[#b8847e] underline">Chính Sách Bảo Mật</a>,
                    xử lý đơn hàng và hỗ trợ khách hàng theo các điều khoản này.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">7. Hiệu lực &amp; sửa đổi</h2>
                <p>
                    Điều khoản có hiệu lực kể từ ngày đăng tải trên website. SocialShop có quyền sửa đổi nội dung;
                    thay đổi sẽ được cập nhật trên trang này và áp dụng cho các giao dịch phát sinh sau thời điểm cập nhật.
                    Việc tiếp tục sử dụng dịch vụ sau khi điều khoản được sửa đổi được coi là chấp thuận thay đổi.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">8. Căn cứ pháp lý</h2>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li>Luật Giao dịch điện tử số 20/2023/QH15;</li>
                    <li>Nghị định 52/2013/NĐ-CP về Thương mại điện tử (đã sửa đổi, bổ sung bởi NĐ 85/2021/NĐ-CP);</li>
                    <li>Luật Bảo vệ quyền lợi người tiêu dùng số 19/2023/QH15.</li>
                </ul>
                <p class="mt-2 text-sm italic text-[#9a9490]">[Cần kiểm chứng] tự tra cứu hiệu lực của văn bản tại thời điểm nộp bài.</p>
            </section>
        </div>
    </div>
</x-layouts.app>
