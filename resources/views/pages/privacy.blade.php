<x-layouts.app :title="'Chính sách bảo mật'">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12">
        <h1 class="font-serif text-3xl font-bold text-[#3d3d3d] mb-8">Chính sách bảo vệ dữ liệu cá nhân</h1>
        <p class="text-sm text-[#9a9490] mb-8">
            Căn cứ Luật Bảo vệ dữ liệu cá nhân số 91/2025/QH15 (hiệu lực từ 01/01/2026); Luật Giao dịch điện tử số 20/2023/QH15.
            Danh mục chi tiết từng loại dữ liệu: <a href="{{ route('pages.data-inventory') }}" class="text-[#b8847e] underline">Bảng đăng ký dữ liệu cá nhân</a>.
        </p>

        <div class="space-y-10 text-[#6b6560] leading-relaxed text-[15px]">
            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">1. Nhóm dữ liệu cá nhân thu thập</h2>
                <p>SocialShop chỉ thu thập dữ liệu cá nhân cần thiết cho việc cung cấp dịch vụ:</p>
                <ul class="list-disc list-inside mt-2 space-y-1 ml-1">
                    <li><strong class="text-[#3d3d3d]">Thông tin định danh &amp; liên hệ:</strong> họ tên, email, số điện thoại (trường <code>users</code>);</li>
                    <li><strong class="text-[#3d3d3d]">Dữ liệu giao hàng:</strong> địa chỉ nhận hàng, ghi chú đơn (<code>orders.shipping_*</code>);</li>
                    <li><strong class="text-[#3d3d3d]">Dữ liệu tài khoản:</strong> mật khẩu (lưu dạng băm bcrypt — không lưu mật khẩu thô);</li>
                    <li><strong class="text-[#3d3d3d]">Lịch sử giao dịch:</strong> đơn hàng, sản phẩm đã mua, đánh giá sản phẩm;</li>
                    <li><strong class="text-[#3d3d3d]">Dữ liệu kỹ thuật:</strong> cookie phiên đăng nhập/giỏ hàng; cookie giới thiệu <code>ref</code> (mã referral — không chứa dữ liệu cá nhân nhạy cảm);</li>
                    <li><strong class="text-[#3d3d3d]">Dữ liệu thống kê truy cập:</strong> trang đã xem, sản phẩm đã xem/thêm vào giỏ, nguồn truy cập (tham số UTM của chiến dịch quảng cáo hoặc trang giới thiệu) — gắn với mã phiên ngẫu nhiên, <strong class="text-[#3d3d3d]">không lưu địa chỉ IP</strong>; cookie <code>sc_attribution</code> giữ nguồn chiến dịch tối đa 30 ngày;</li>
                    <li>Site <strong class="text-[#3d3d3d]">không lưu trữ</strong> số thẻ ngân hàng hay thông tin thẻ tín dụng.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">2. Mục đích xử lý dữ liệu</h2>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li>Tạo và quản lý tài khoản; xác thực đăng nhập;</li>
                    <li>Xử lý đơn hàng, giao hàng, thanh toán (COD / VNPay);</li>
                    <li>Hỗ trợ khách hàng, xử lý đổi trả và khiếu nại;</li>
                    <li>Cải thiện trải nghiệm và nội dung website; thống kê hiệu quả chiến dịch quảng cáo (tỷ lệ chuyển đổi, ROAS) ở dạng tổng hợp;</li>
                    <li>Chống gian lận, bảo mật hệ thống — ghi vết thao tác quan trọng (audit log: đăng nhập, tạo đơn, thay đổi trạng thái…);</li>
                    <li>Gửi thông báo đơn hàng (email).</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">3. Cách thức thu thập</h2>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li><strong class="text-[#3d3d3d]">Trực tiếp:</strong> do bạn nhập khi đăng ký, cập nhật hồ sơ, đặt hàng, đánh giá sản phẩm;</li>
                    <li><strong class="text-[#3d3d3d]">Gián tiếp (cookie):</strong> cookie phiên (giỏ hàng, đăng nhập); cookie giới thiệu <code>ref</code> — dùng ghi nhận đường giới thiệu bạn bè (referral), không tự động thu thập dữ liệu cá nhân mở rộng.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">4. Thời hạn lưu trữ</h2>
                <p>
                    Dữ liệu tài khoản được lưu trong thời gian tài khoản còn hoạt động; dữ liệu đơn hàng, thanh toán lưu theo thời hạn
                    lưu trữ chứng từ kế toán. Thống kê truy cập tự xóa sau {{ \App\Console\Commands\PrunePersonalData::ANALYTICS_MONTHS }} tháng,
                    nhật ký bảo mật (audit log) tự xóa sau {{ \App\Console\Commands\PrunePersonalData::AUDIT_MONTHS }} tháng.
                    Thời hạn từng loại dữ liệu: <a href="{{ route('pages.data-inventory') }}" class="text-[#b8847e] underline">Bảng đăng ký dữ liệu cá nhân</a>.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">5. Chia sẻ với bên thứ ba</h2>
                <p>Chỉ chia sẻ dữ liệu <strong class="text-[#3d3d3d]">cần thiết tối thiểu</strong> cho:</p>
                <ul class="list-disc list-inside mt-2 space-y-1 ml-1">
                    <li><strong class="text-[#3d3d3d]">Đơn vị vận chuyển:</strong> tên, SĐT, địa chỉ nhận hàng để giao;</li>
                    <li><strong class="text-[#3d3d3d]">Cổng thanh toán VNPay:</strong> thông tin giao dịch thanh toán trực tuyến;</li>
                    <li>Cơ quan nhà nước có thẩm quyền theo yêu cầu pháp luật.</li>
                </ul>
                <p class="mt-2">Không bán dữ liệu cá nhân cho bên thứ ba vì mục đích quảng cáo.</p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">6. Quyền của chủ thể dữ liệu (Luật BVDLCN 91/2025)</h2>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li>Được <strong class="text-[#3d3d3d]">biết</strong> dữ liệu nào về mình đang được xử lý;</li>
                    <li>Được <strong class="text-[#3d3d3d]">truy cập, yêu cầu sửa</strong> dữ liệu không chính xác;</li>
                    <li>Được <strong class="text-[#3d3d3d]">yêu cầu xóa</strong> dữ liệu / xóa tài khoản;</li>
                    <li>Được <strong class="text-[#3d3d3d]">rút đồng ý</strong> đối với xử lý dữ liệu (trừ xử lý bắt buộc theo pháp luật);</li>
                    <li>Được <strong class="text-[#3d3d3d]">khiếu nại</strong> về việc xử lý dữ liệu.</li>
                </ul>
                <p class="mt-2">
                    Cách thực hiện: gửi yêu cầu tới email <strong class="text-[#3d3d3d]">support@socialshop.vn</strong>
                    hoặc hotline <strong class="text-[#3d3d3d]">077123456</strong> — phản hồi trong thời gian hợp lý theo quy định.
                </p>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">7. Biện pháp bảo mật dữ liệu</h2>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li>Mật khẩu lưu dạng <strong class="text-[#3d3d3d]">bcrypt</strong> (không lưu thô);</li>
                    <li>Phân quyền truy cập (RBAC): admin / customer;</li>
                    <li>Giới hạn số lần đăng nhập sai (throttle);</li>
                    <li>Header bảo mật HTTP (X-Content-Type-Options, X-Frame-Options, Referrer-Policy);</li>
                    <li>Cookie secure khi triển khai HTTPS production;</li>
                    <li>Không lộ mật khẩu/khóa bí mật trong mã nguồn (biến môi trường);</li>
                    <li>Sao lưu cơ sở dữ liệu định kỳ (<code>db:backup</code>).</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">8. Đầu mối khiếu nại / phụ trách bảo vệ dữ liệu</h2>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li>Email: <strong class="text-[#3d3d3d]">support@socialshop.vn</strong></li>
                    <li>Hotline: <strong class="text-[#3d3d3d]">077123456</strong></li>
                    <li>Địa chỉ: 123 Đường Thời Trang, Quận 1, TP.HCM</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">9. Căn cứ pháp lý</h2>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li>Luật Bảo vệ dữ liệu cá nhân số 91/2025/QH15 (thông qua 26/6/2025, hiệu lực 01/01/2026);</li>
                    <li>Luật Thương mại điện tử số 122/2025/QH15 (thông qua 10/12/2025, hiệu lực 01/7/2026);</li>
                    <li>Luật Giao dịch điện tử số 20/2023/QH15.</li>
                </ul>
            </section>

            <section>
                <h2 class="font-serif text-xl font-semibold text-[#3d3d3d] mb-3">Liên kết liên quan</h2>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li><a href="{{ route('pages.terms') }}" class="text-[#b8847e] underline">Điều Kiện Giao Dịch Chung</a></li>
                    <li><a href="{{ route('pages.return-policy') }}" class="text-[#b8847e] underline">Chính Sách Đổi Trả</a></li>
                    <li><a href="{{ route('pages.about') }}" class="text-[#b8847e] underline">Thông Tin Người Bán</a></li>
                </ul>
            </section>
        </div>
    </div>
</x-layouts.app>
