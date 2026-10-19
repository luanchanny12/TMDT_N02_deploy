@extends('emails.retention.layout')

@section('heading', 'Chào mừng bạn')

@section('body')
    <p>Cảm ơn bạn đã đồng hành cùng SocialShop — cửa hàng thời trang và phụ kiện cho nam và nữ.</p>

    <div class="box">
        <p><strong>Những điều bạn có thể yên tâm:</strong></p>
        <ul>
            <li>Giao hàng toàn quốc, thanh toán khi nhận hàng (COD) hoặc VNPay.</li>
            <li>Đổi/trả trong 7 ngày kể từ ngày nhận hàng — <a href="{{ route('pages.return-policy') }}">xem chính sách đổi trả</a>.</li>
            <li>Theo dõi đơn hàng bất cứ lúc nào trong mục <a href="{{ route('orders.index') }}">Đơn hàng của tôi</a>.</li>
        </ul>
    </div>

    <p>Cần tư vấn chọn size hay phối đồ? Hãy trả lời thư này hoặc gọi hotline 077123456.</p>

    <p style="text-align: center;"><a class="btn" href="{{ route('products.index') }}">Khám phá sản phẩm mới</a></p>
@endsection
