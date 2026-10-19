@extends('emails.retention.layout')

@section('heading', 'Ưu đãi riêng cho bạn')

@section('body')
    <p>Các sản phẩm bạn chọn vẫn đang trong giỏ hàng. Để bạn dễ quyết định hơn, SocialShop gửi tặng mã giảm giá dùng một lần:</p>

    <div class="box" style="text-align: center;">
        <p class="code">{{ $coupon->code }}</p>
        <p>Giảm {{ $coupon->value }}% (tối đa {{ number_format($coupon->max_discount, 0, ',', '.') }}₫) cho đơn tiếp theo.</p>
        <p><strong>Hết hạn lúc {{ $coupon->end_at->format('H:i d/m/Y') }}</strong></p>
    </div>

    <table>
        @foreach($items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td style="text-align: right;">x{{ $item->quantity }}</td>
            </tr>
        @endforeach
    </table>

    <p style="text-align: center;"><a class="btn" href="{{ route('cart.index') }}">Dùng mã ngay</a></p>

    <p style="font-size: 12px; color: #6b6560;">Đây là thư cuối cùng về giỏ hàng này. Nếu không muốn nhận thêm email ưu đãi, bạn có thể hủy đăng ký ở cuối thư.</p>
@endsection
