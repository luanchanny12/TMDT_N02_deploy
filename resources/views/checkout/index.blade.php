<x-layouts.app :title="'Thanh toán'">
    @php
        // Quay lại từ trang xác nhận ("Sửa") → điền sẵn thông tin đã nhập
        $prev = session('checkout', []);
        $payment = old('payment_method', $prev['payment_method'] ?? 'cod');
    @endphp
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10">

        {{-- Stepper Progress Header --}}
        <div class="max-w-3xl mx-auto mb-10">
            <div class="flex items-center justify-between relative">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-[#efe8e3] z-0 rounded-full"></div>
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1/3 h-1 bg-gradient-to-r from-[#b8847e] to-[#c9a9a6] z-0 rounded-full"></div>

                {{-- Step 1 (Active) --}}
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-11 h-11 rounded-full bg-gradient-to-br from-[#b8847e] to-[#a6736d] text-white flex items-center justify-center font-bold text-sm shadow-md ring-4 ring-[#b8847e]/20">
                        1
                    </div>
                    <span class="text-xs font-bold text-[#b8847e] mt-2.5">1. Thông tin giao hàng</span>
                </div>

                {{-- Step 2 --}}
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-11 h-11 rounded-full bg-white border-2 border-[#efe8e3] text-[#9a9490] flex items-center justify-center font-bold text-sm shadow-sm">
                        2
                    </div>
                    <span class="text-xs font-medium text-[#9a9490] mt-2.5">2. Xác nhận đơn hàng</span>
                </div>

                {{-- Step 3 --}}
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-11 h-11 rounded-full bg-white border-2 border-[#efe8e3] text-[#9a9490] flex items-center justify-center font-bold text-sm shadow-sm">
                        3
                    </div>
                    <span class="text-xs font-medium text-[#9a9490] mt-2.5">3. Hoàn tất</span>
                </div>
            </div>
        </div>

        <form action="{{ route('checkout.review') }}" method="POST" x-data="checkoutForm()">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                {{-- Left 7 cols: Shipping Info & Payment Methods --}}
                <div class="lg:col-span-7 space-y-6">

                    {{-- Shipping Info Card --}}
                    <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-8 shadow-sm">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-[#efe8e3]">
                            <span class="w-9 h-9 rounded-2xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center text-[#b8847e] shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </span>
                            <div>
                                <h2 class="font-serif text-xl font-bold text-[#2c2a28]">Thông tin giao hàng</h2>
                                <p class="text-xs text-[#9a9490]">Vui lòng nhập chính xác để đơn hàng được giao nhanh nhất</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            @guest
                                {{-- Khách vãng lai: mua hàng không cần đăng nhập --}}
                                <div class="rounded-2xl border border-[#efe8e3] bg-[#faf7f4] p-4 text-xs sm:text-sm text-[#6b6560] leading-relaxed" data-testid="guest-checkout">
                                    Bạn đang mua hàng <strong class="text-[#2c2a28]">không cần đăng nhập</strong>. Chúng tôi sẽ gửi xác nhận đơn và link đặt mật khẩu
                                    (để theo dõi đơn sau này) tới email bên dưới.
                                    Đã có tài khoản? <a href="{{ route('login') }}" class="text-[#b8847e] font-semibold underline">Đăng nhập</a>
                                </div>
                                <div>
                                    <label class="block text-xs uppercase tracking-wider font-semibold text-[#2c2a28] mb-1.5">Email *</label>
                                    <input type="email" name="guest_email" value="{{ old('guest_email') }}" autocomplete="email"
                                           class="w-full bg-[#faf7f4]/60 border border-[#efe8e3] rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#b8847e] focus:bg-white outline-none transition-all @error('guest_email') border-red-500 @enderror"
                                           placeholder="ban@email.com"
                                           required>
                                    @error('guest_email')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endguest

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs uppercase tracking-wider font-semibold text-[#2c2a28] mb-1.5">Họ và tên *</label>
                                    <input type="text" name="shipping_name" value="{{ old('shipping_name', $prev['shipping_name'] ?? Auth::user()?->name) }}"
                                           class="w-full bg-[#faf7f4]/60 border border-[#efe8e3] rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#b8847e] focus:bg-white outline-none transition-all @error('shipping_name') border-red-500 @enderror"
                                           placeholder="Nguyễn Văn A"
                                           required>
                                    @error('shipping_name')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs uppercase tracking-wider font-semibold text-[#2c2a28] mb-1.5">Số điện thoại *</label>
                                    <input type="text" name="shipping_phone" value="{{ old('shipping_phone', $prev['shipping_phone'] ?? Auth::user()?->phone) }}"
                                           class="w-full bg-[#faf7f4]/60 border border-[#efe8e3] rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#b8847e] focus:bg-white outline-none transition-all @error('shipping_phone') border-red-500 @enderror"
                                           placeholder="0912 345 678"
                                           required>
                                    @error('shipping_phone')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs uppercase tracking-wider font-semibold text-[#2c2a28] mb-1.5">Tỉnh / Thành phố *</label>
                                <select name="shipping_province" x-model="province" required
                                        class="w-full bg-[#faf7f4]/60 border border-[#efe8e3] rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#b8847e] focus:bg-white outline-none transition-all bg-white @error('shipping_province') border-red-500 @enderror">
                                    <option value="">— Chọn tỉnh/thành phố —</option>
                                    @foreach(config('vietnam.regions') as $region => $provinces)
                                        <optgroup label="{{ $region }}">
                                            @foreach($provinces as $regionProvince)
                                                <option value="{{ $regionProvince }}">{{ $regionProvince }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-[#9a9490] mt-1.5" x-show="zone" x-cloak>
                                    Khu vực giao hàng: <span x-text="zone" class="font-semibold text-[#b8847e]"></span>
                                </p>
                                @error('shipping_province')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs uppercase tracking-wider font-semibold text-[#2c2a28] mb-1.5">Địa chỉ cụ thể (số nhà, đường, phường/xã) *</label>
                                <textarea name="shipping_address" rows="3"
                                          class="w-full bg-[#faf7f4]/60 border border-[#efe8e3] rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#b8847e] focus:bg-white outline-none transition-all resize-none @error('shipping_address') border-red-500 @enderror"
                                          placeholder="Số nhà, tên đường, phường/xã, quận/huyện..."
                                          required>{{ old('shipping_address', $prev['shipping_address'] ?? '') }}</textarea>
                                @error('shipping_address')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs uppercase tracking-wider font-semibold text-[#2c2a28] mb-1.5">Ghi chú giao hàng (tùy chọn)</label>
                                <textarea name="shipping_notes" rows="2"
                                          class="w-full bg-[#faf7f4]/60 border border-[#efe8e3] rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#b8847e] focus:bg-white outline-none transition-all resize-none"
                                          placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao...">{{ old('shipping_notes', $prev['shipping_notes'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Method Selection Card --}}
                    <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-8 shadow-sm">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-[#efe8e3]">
                            <span class="w-9 h-9 rounded-2xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center text-[#b8847e] shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </span>
                            <div>
                                <h2 class="font-serif text-xl font-bold text-[#2c2a28]">Phương thức thanh toán</h2>
                                <p class="text-xs text-[#9a9490]">Chọn cách thức thanh toán tiện lợi và an toàn nhất cho bạn</p>
                            </div>
                        </div>

                        <div class="space-y-3.5">
                            {{-- COD Option --}}
                            <label class="flex items-center justify-between p-4 sm:p-5 rounded-2xl border-2 cursor-pointer transition-all {{ $payment === 'cod' ? 'border-[#b8847e] bg-[#faf7f4]/50 shadow-sm' : 'border-[#efe8e3] hover:border-[#c9a9a6]' }}">
                                <div class="flex items-center gap-3.5">
                                    <input type="radio" name="payment_method" value="cod"
                                           {{ $payment === 'cod' ? 'checked' : '' }}
                                           class="h-4 w-4 text-[#b8847e] focus:ring-[#b8847e]">
                                    <div>
                                        <span class="font-bold text-sm text-[#2c2a28] block">Thanh toán khi nhận hàng (COD)</span>
                                        <p class="text-xs text-[#9a9490] mt-0.5">Kiểm tra sản phẩm trước khi thanh toán tiền mặt cho shipper</p>
                                    </div>
                                </div>
                                <span class="text-xs font-bold bg-white border border-[#efe8e3] px-3 py-1.5 rounded-xl text-[#6b6560] shadow-sm">
                                    Tiền mặt
                                </span>
                            </label>

                            {{-- VNPay Option --}}
                            <label class="flex items-center justify-between p-4 sm:p-5 rounded-2xl border-2 cursor-pointer transition-all {{ $payment === 'vnpay' ? 'border-[#b8847e] bg-[#faf7f4]/50 shadow-sm' : 'border-[#efe8e3] hover:border-[#c9a9a6]' }}">
                                <div class="flex items-center gap-3.5">
                                    <input type="radio" name="payment_method" value="vnpay"
                                           {{ $payment === 'vnpay' ? 'checked' : '' }}
                                           class="h-4 w-4 text-[#b8847e] focus:ring-[#b8847e]">
                                    <div>
                                        <span class="font-bold text-sm text-[#2c2a28] block">VNPay</span>
                                        <p class="text-xs text-[#9a9490] mt-0.5">Thanh toán trực tuyến qua VNPay Sandbox (Quét QR, thẻ ATM, Visa/Master)</p>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 px-3 py-1.5 rounded-xl shadow-sm">
                                    VNPAY QR
                                </span>
                            </label>
                        </div>
                        @error('payment_method')
                            <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Right 5 cols: Order Summary Sidebar --}}
                <div class="lg:col-span-5">
                    <div class="bg-white border border-[#efe8e3] rounded-3xl p-6 sm:p-7 shadow-sm sticky top-28 space-y-5">
                        <div class="flex items-center justify-between pb-4 border-b border-[#efe8e3]">
                            <h2 class="font-serif text-xl font-bold text-[#2c2a28]">Đơn hàng của bạn</h2>
                            <a href="{{ route('cart.index') }}" class="text-xs font-semibold text-[#b8847e] hover:underline">Sửa giỏ hàng</a>
                        </div>

                        {{-- Cart Items Mini Preview --}}
                        <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                            @foreach($cartItems as $item)
                                <div class="flex items-center justify-between gap-3 text-xs sm:text-sm">
                                    <div class="flex items-center gap-3 min-w-0">
                                        @if($item->product->image_url)
                                            <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="w-12 h-12 rounded-xl object-cover border border-[#efe8e3] flex-shrink-0">
                                        @else
                                            <div class="w-12 h-12 rounded-xl bg-[#faf7f4] border border-[#efe8e3] flex items-center justify-center text-[#c9a9a6] flex-shrink-0 text-xs font-bold">SP</div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-medium text-[#2c2a28] truncate">{{ $item->product->name }}</p>
                                            <p class="text-[11px] text-[#9a9490]">Số lượng: x{{ $item->quantity }}</p>
                                        </div>
                                    </div>
                                    <span class="font-serif font-bold text-sm text-[#2c2a28] flex-shrink-0">
                                        {{ number_format($item->price * $item->quantity, 0, ',', '.') }} ₫
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        @auth
                        {{-- Coupon Input Section --}}
                        <div class="border-t border-[#efe8e3] pt-4">
                            <label class="block text-xs uppercase tracking-wider font-semibold text-[#2c2a28] mb-2">Mã giảm giá</label>

                            {{-- View khi chưa có coupon --}}
                            <div x-show="!appliedCoupon" class="flex gap-2">
                                <input type="text" x-model="couponCode" placeholder="Nhập mã ưu đãi..."
                                       class="flex-1 bg-[#faf7f4]/60 border border-[#efe8e3] rounded-xl px-3.5 py-2.5 text-xs font-medium focus:ring-2 focus:ring-[#b8847e] focus:bg-white outline-none"
                                       :disabled="loading">
                                <button type="button" @click="applyCoupon()"
                                        :disabled="loading || !couponCode.trim()"
                                        class="px-5 py-2.5 bg-gradient-to-r from-[#b8847e] to-[#a6736d] text-white text-xs font-bold rounded-xl hover:shadow-md transition-all disabled:opacity-50">
                                    <span x-show="!loading">Áp dụng</span>
                                    <span x-show="loading" x-cloak>...</span>
                                </button>
                            </div>

                            {{-- View khi đã có coupon --}}
                            <div x-show="appliedCoupon" x-cloak class="flex items-center justify-between bg-emerald-50 border border-emerald-200 rounded-2xl px-4 py-2.5">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-xs font-bold text-emerald-800" x-text="appliedCoupon"></span>
                                </div>
                                <button type="button" @click="removeCoupon()"
                                        class="text-xs text-rose-500 hover:text-rose-700 font-semibold underline">
                                    Gỡ mã
                                </button>
                            </div>

                            {{-- Error message --}}
                            <div x-show="couponError" x-cloak class="mt-2 text-xs text-rose-600 font-medium" x-text="couponError"></div>
                        </div>
                        @else
                            <p class="border-t pt-4 mb-4 text-sm text-gray-500">
                                <a href="{{ route('login') }}" class="text-[#b8847e] underline">Đăng nhập</a> để dùng mã giảm giá.
                            </p>
                        @endauth

                        {{-- Calculation Breakdown --}}
                        <div class="border-t border-[#efe8e3] pt-4 space-y-2.5 text-xs sm:text-sm">
                            <div class="flex justify-between text-[#6b6560]">
                                <span>Tạm tính</span>
                                <span class="font-medium text-[#2c2a28]" x-text="formatCurrency({{ $cartTotal }})"></span>
                            </div>

                            {{-- Discount line --}}
                            <div x-show="discount > 0" x-cloak class="flex justify-between text-emerald-700">
                                <span>Giảm giá</span>
                                <span class="font-bold" x-text="'-' + formatCurrency(discount)"></span>
                            </div>

                            <div class="flex justify-between text-[#6b6560]">
                                <span x-text="province ? 'Phí vận chuyển' : 'Phí vận chuyển (ước tính)'">Phí vận chuyển</span>
                                <span x-text="shippingFee > 0 ? formatCurrency(shippingFee) : 'Miễn phí'" :class="shippingFee === 0 ? 'text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-full text-xs' : 'font-medium text-[#2c2a28]'"></span>
                            </div>
                            </div>
                            <p class="text-xs text-gray-500 -mt-1" x-show="province" x-cloak data-testid="shipping-hint"
                               x-text="shippingFee === 0
                                   ? 'Miễn phí vì đơn từ ' + formatCurrency(rule.threshold) + ' (' + (zone || 'mặc định') + ', phí thường ' + formatCurrency(rule.fee) + ')'
                                   : 'Mua thêm ' + formatCurrency(rule.threshold - cartTotal) + ' để được miễn phí ship (' + (zone || 'mặc định') + ')'"></p>

                            <div x-show="pricing.taxRate > 0" x-cloak class="flex justify-between text-[#6b6560]" data-testid="tax-line">
                                <span x-text="pricing.taxLabel + ' (' + pricing.taxRate + '%)'"></span>
                                <span class="font-medium text-[#2c2a28]" x-text="formatCurrency(taxAmount)"></span>
                            </div>

                            <div class="flex justify-between items-baseline border-t border-[#efe8e3] pt-3 text-base">
                                <span class="font-bold text-[#2c2a28]">Tổng cộng</span>
                                <span class="font-serif text-2xl font-bold text-[#b8847e]" x-text="formatCurrency(finalTotal)"></span>
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <button type="submit"
                                class="w-full bg-gradient-to-r from-[#b8847e] to-[#a6736d] text-white py-4 rounded-2xl font-bold hover:shadow-lg hover:shadow-[#b8847e]/30 transition-all text-sm btn-shine mt-4">
                            Xác nhận đơn hàng
                        </button>

                        <p class="text-[11px] text-[#9a9490] mt-3 text-center leading-relaxed">
                            Bằng cách tiếp tục, bạn đồng ý với
                            <a href="{{ route('pages.terms') }}" class="underline hover:text-[#b8847e]">Điều Kiện Giao Dịch</a>
                            và
                            <a href="{{ route('pages.privacy') }}" class="underline hover:text-[#b8847e]">Chính sách bảo mật</a>.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function checkoutForm() {
            return {
                couponCode: '{{ $couponCode ?? '' }}',
                appliedCoupon: '{{ $couponCode ?? '' }}',
                discount: {{ $discount ?? 0 }},
                loading: false,
                couponError: '',

                get cartTotal() {
                    return {{ $cartTotal }};
                },

                pricing: @js($pricingConfig),
                province: @js(old('shipping_province', $prev['shipping_province'] ?? '')),

                get rule() {
                    const z = this.pricing.zones[this.province];
                    return {
                        fee: z ? z.fee : this.pricing.shippingFee,
                        threshold: z && z.threshold !== null ? z.threshold : this.pricing.freeShippingThreshold,
                    };
                },

                get zone() {
                    return this.pricing.zones[this.province]?.zone || '';
                },

                get shippingFee() {
                    return this.cartTotal >= this.rule.threshold ? 0 : this.rule.fee;
                },

                get taxAmount() {
                    const base = Math.max(0, this.cartTotal - this.discount) + (this.pricing.taxOnShipping ? this.shippingFee : 0);
                    return Math.round(base * this.pricing.taxRate / 100);
                },

                get finalTotal() {
                    return Math.max(0, this.cartTotal - this.discount + this.shippingFee + this.taxAmount);
                },

                formatCurrency(amount) {
                    return new Intl.NumberFormat('vi-VN').format(amount) + ' ₫';
                },

                async applyCoupon() {
                    if (!this.couponCode.trim()) return;

                    this.loading = true;
                    this.couponError = '';

                    try {
                        const response = await fetch('{{ route("checkout.apply-coupon") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ code: this.couponCode.trim() }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.appliedCoupon = data.code;
                            this.discount = data.discount;
                            this.couponError = '';
                        } else {
                            this.couponError = data.message;
                            this.appliedCoupon = '';
                            this.discount = 0;
                        }
                    } catch (e) {
                        this.couponError = 'Có lỗi xảy ra, vui lòng thử lại.';
                    } finally {
                        this.loading = false;
                    }
                },

                async removeCoupon() {
                    this.loading = true;

                    try {
                        await fetch('{{ route("checkout.remove-coupon") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });

                        this.appliedCoupon = '';
                        this.couponCode = '';
                        this.discount = 0;
                        this.couponError = '';
                    } catch (e) {
                        // ignore
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
    @endpush
</x-layouts.app>
