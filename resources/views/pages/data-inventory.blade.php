<x-layouts.app :title="'Bảng đăng ký dữ liệu cá nhân'">
    @php
        $rows = [
            ['Họ tên, email', 'Tạo tài khoản, đăng nhập, gửi xác nhận và cập nhật đơn hàng', 'Thực hiện hợp đồng mua bán', 'Đến khi bạn yêu cầu xóa tài khoản', 'Nhà cung cấp dịch vụ gửi email'],
            ['Mật khẩu', 'Xác thực đăng nhập (chỉ lưu dạng băm bcrypt, không lưu mật khẩu gốc)', 'Thực hiện hợp đồng; bảo đảm an toàn tài khoản', 'Đến khi xóa tài khoản', 'Không chia sẻ'],
            ['Số điện thoại, địa chỉ, tỉnh/thành nhận hàng', 'Giao hàng, liên hệ khi giao; tính phí vận chuyển theo khu vực', 'Thực hiện hợp đồng mua bán', 'Lưu cùng đơn hàng theo thời hạn lưu trữ chứng từ kế toán', 'Đơn vị vận chuyển'],
            ['Lịch sử đơn hàng, mã giao dịch thanh toán', 'Xử lý thanh toán, đổi trả, hoàn tiền, khiếu nại; nghĩa vụ kế toán (không lưu số thẻ)', 'Thực hiện hợp đồng; nghĩa vụ pháp lý', 'Theo thời hạn lưu trữ chứng từ kế toán', 'Cổng thanh toán VNPay (đơn VNPay)'],
            ['Lựa chọn nhận email tiếp thị (đồng ý/hủy, thời điểm), lịch sử thư đã gửi', 'Gửi tối đa 3 email giữ chân: chào mừng, nhắc giỏ hàng, ưu đãi có thời hạn', 'Sự đồng ý của bạn (ô không tích sẵn khi đăng ký/đặt hàng); rút lại bằng link Hủy đăng ký trong thư hoặc trang Hồ sơ', 'Đến khi bạn hủy đăng ký hoặc xóa tài khoản', 'Nhà cung cấp dịch vụ gửi email'],
            ['Tài khoản Google/Facebook (mã định danh, ảnh đại diện)', 'Đăng nhập bằng mạng xã hội', 'Sự đồng ý khi bạn chọn đăng nhập bằng mạng xã hội', 'Đến khi xóa tài khoản', 'Google, Meta (để xác thực)'],
            ['Đánh giá sản phẩm, ảnh đánh giá', 'Hiển thị công khai trên trang sản phẩm sau khi duyệt', 'Sự đồng ý khi bạn gửi đánh giá', 'Đến khi bạn yêu cầu xóa', 'Công khai trên website'],
            ['Nội dung trò chuyện với trợ lý AI', 'Trả lời câu hỏi; với khách đã đăng nhập kèm thông tin đơn hàng liên quan để tra cứu', 'Sự đồng ý khi bạn sử dụng trợ lý', 'Không lưu trên máy chủ SocialShop sau phiên trò chuyện', 'Google (Gemini API) để tạo câu trả lời'],
            ['Cookie phiên đăng nhập, giỏ hàng', 'Duy trì đăng nhập và giỏ hàng', 'Cần thiết để cung cấp dịch vụ', config('session.lifetime').' phút không hoạt động', 'Không chia sẻ'],
            ['Cookie mã giới thiệu (ref)', 'Ghi nhận người giới thiệu để tính hoa hồng', 'Sự đồng ý khi truy cập qua link giới thiệu', intdiv((int) config('referral.cookie_lifetime', 43200), 1440).' ngày', 'Không chia sẻ'],
            ['Thống kê truy cập (mã phiên ngẫu nhiên, trang đã xem, nguồn UTM)', 'Đo lường hiệu quả website và chiến dịch quảng cáo ở dạng tổng hợp; không lưu địa chỉ IP', 'Lợi ích hợp pháp trong vận hành, cải thiện dịch vụ', \App\Console\Commands\PrunePersonalData::ANALYTICS_MONTHS.' tháng, sau đó tự xóa', 'Không chia sẻ'],
            ['Nhật ký bảo mật (địa chỉ IP, trình duyệt, thao tác đăng nhập/đặt hàng)', 'Phát hiện gian lận, điều tra sự cố bảo mật', 'Bảo đảm an ninh hệ thống; nghĩa vụ pháp lý', \App\Console\Commands\PrunePersonalData::AUDIT_MONTHS.' tháng, sau đó tự xóa', 'Cơ quan có thẩm quyền khi có yêu cầu hợp pháp'],
        ];
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
        <h1 class="font-serif text-3xl font-bold text-[#3d3d3d] mb-3">Bảng đăng ký dữ liệu cá nhân</h1>
        <p class="text-sm text-[#9a9490] mb-8 max-w-3xl">
            Danh mục dữ liệu cá nhân SocialShop xử lý, mục đích, cơ sở xử lý, thời gian lưu trữ và bên được chia sẻ —
            công khai theo Luật Bảo vệ dữ liệu cá nhân số 91/2025/QH15 (hiệu lực từ 01/01/2026).
            Chi tiết quyền của bạn xem tại <a href="{{ route('pages.privacy') }}" class="text-[#b8847e] underline">Chính sách bảo vệ dữ liệu cá nhân</a>.
        </p>

        <div class="overflow-x-auto bg-white rounded-xl border border-[#efe8e3]">
            <table class="w-full text-sm text-left">
                <thead class="bg-[#faf7f4] text-[#3d3d3d]">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Dữ liệu</th>
                        <th class="px-4 py-3 font-semibold">Mục đích xử lý</th>
                        <th class="px-4 py-3 font-semibold">Cơ sở xử lý</th>
                        <th class="px-4 py-3 font-semibold">Thời gian lưu trữ</th>
                        <th class="px-4 py-3 font-semibold">Bên được chia sẻ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#efe8e3] text-[#6b6560]">
                    @foreach($rows as [$data, $purpose, $basis, $retention, $shared])
                        <tr class="align-top">
                            <td class="px-4 py-3 font-medium text-[#3d3d3d]">{{ $data }}</td>
                            <td class="px-4 py-3">{{ $purpose }}</td>
                            <td class="px-4 py-3">{{ $basis }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $retention }}</td>
                            <td class="px-4 py-3">{{ $shared }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 text-sm text-[#6b6560] leading-relaxed">
            <div class="bg-white rounded-xl border border-[#efe8e3] p-5">
                <h2 class="font-semibold text-[#3d3d3d] mb-2">Tối thiểu hóa dữ liệu</h2>
                <p>Form đặt hàng chỉ hỏi thông tin cần để giao hàng. SocialShop không thu thập số thẻ, CMND/CCCD, ngày sinh; không bán dữ liệu cá nhân cho bên thứ ba.</p>
            </div>
            <div class="bg-white rounded-xl border border-[#efe8e3] p-5">
                <h2 class="font-semibold text-[#3d3d3d] mb-2">Yêu cầu về dữ liệu của bạn</h2>
                <p>Truy cập, sửa, xóa dữ liệu hoặc rút lại sự đồng ý: email <strong class="text-[#3d3d3d]">support@socialshop.vn</strong> hoặc hotline <strong class="text-[#3d3d3d]">077123456</strong>.</p>
            </div>
        </div>
    </div>
</x-layouts.app>
