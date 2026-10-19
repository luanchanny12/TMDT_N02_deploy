@extends('emails.retention.layout')

@section('heading', 'Giỏ hàng vẫn đang chờ bạn')

@section('body')
    <p>Bạn vẫn còn sản phẩm trong giỏ hàng. Chúng tôi giữ nguyên giỏ để bạn hoàn tất bất cứ lúc nào:</p>

    <table>
        @foreach($items as $item)
            <tr>
                <td>{{ $item->product->name }}{{ $item->variant ? ' — '.$item->variant->label() : '' }}</td>
                <td style="text-align: right;">x{{ $item->quantity }}</td>
            </tr>
        @endforeach
    </table>

    <div class="box">
        <p><strong>Mua sắm an tâm:</strong> đổi size, đổi mẫu hoặc trả hàng trong 7 ngày kể từ ngày nhận;
            hàng lỗi hoặc giao sai được đổi trả miễn phí. <a href="{{ route('pages.return-policy') }}">Xem chính sách đổi trả</a>.</p>
    </div>

    @if($proof['delivered_orders'] > 0 || $proof['review'])
        <div class="box">
            @if($proof['delivered_orders'] > 0)
                <p>Đã có <strong>{{ number_format($proof['delivered_orders'], 0, ',', '.') }} đơn hàng</strong> được giao thành công tới khách hàng của SocialShop.</p>
            @endif
            @if($proof['review'])
                <p style="font-style: italic;">“{{ \Illuminate\Support\Str::limit($proof['review']->comment, 160) }}”</p>
                <p style="font-size: 12px; color: #6b6560;">
                    {{ str_repeat('★', $proof['review']->rating) }} — {{ $proof['review']->user?->name ?? 'Khách hàng' }}, về {{ $proof['review']->product?->name }}
                </p>
            @endif
        </div>
    @endif

    <p style="text-align: center;"><a class="btn" href="{{ route('cart.index') }}">Quay lại giỏ hàng</a></p>
@endsection
