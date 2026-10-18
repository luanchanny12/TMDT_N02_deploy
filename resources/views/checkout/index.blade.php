<x-layouts.app :title="'Thanh toán'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Thanh toán</h1>

        <form action="{{ route('checkout.review') }}" method="POST" x-data="checkoutForm()">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                {{-- Shipping Info --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Thông tin giao hàng</h2>

                        @guest
                            {{-- Khách vãng lai: mua hàng không cần đăng ký trước --}}
                            <div class="mb-5 rounded-lg border border-[#efe8e3] bg-[#faf7f4] p-4 text-sm text-gray-700" data-testid="guest-checkout">
                                Bạn đang mua hàng <strong>không cần đăng nhập</strong>. Chúng tôi sẽ gửi xác nhận đơn và link đặt mật khẩu
                                (để theo dõi đơn sau này) tới email bên dưới.
                                Đã có tài khoản? <a href="{{ route('login') }}" class="text-[#b8847e] underline">Đăng nhập</a>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                <input type="email" name="guest_email" value="{{ old('guest_email') }}" autocomplete="email"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 @error('guest_email') border-red-500 @enderror"
                                       required>
                                @error('guest_email')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        @endguest

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Họ và tên *</label>
                                <input type="text" name="shipping_name" value="{{ old('shipping_name', Auth::user()?->name) }}"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 @error('shipping_name') border-red-500 @enderror"
                                       required>
                                @error('shipping_name')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại *</label>
                                <input type="text" name="shipping_phone" value="{{ old('shipping_phone') }}"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 @error('shipping_phone') border-red-500 @enderror"
                                       required>
                                @error('shipping_phone')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tỉnh / Thành phố *</label>
                            <select name="shipping_province" x-model="province" required
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white @error('shipping_province') border-red-500 @enderror">
                                <option value="">— Chọn tỉnh/thành phố —</option>
                                @foreach(config('vietnam.regions') as $region => $provinces)
                                    <optgroup label="{{ $region }}">
                                        @foreach($provinces as $province)
                                            <option value="{{ $province }}">{{ $province }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1" x-show="zone" x-cloak>
                                Khu vực giao hàng: <span x-text="zone"></span>
                            </p>
                            @error('shipping_province')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ cụ thể (số nhà, đường, phường/xã) *</label>
                            <textarea name="shipping_address" rows="3"
                                      class="w-full border border-gray-300 rounded-lg px-4 py-2 @error('shipping_address') border-red-500 @enderror"
                                      required>{{ old('shipping_address') }}</textarea>
                            @error('shipping_address')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                            <textarea name="shipping_notes" rows="2"
                                      class="w-full border border-gray-300 rounded-lg px-4 py-2"
                                      placeholder="Ghi chú cho đơn hàng (tùy chọn)">{{ old('shipping_notes') }}</textarea>
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    <div class="bg-white rounded-lg shadow p-6 mt-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Phương thức thanh toán</h2>

                        <div class="space-y-3">
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-[#c9a9a6] {{ old('payment_method') == 'cod' ? 'border-[#c9a9a6] bg-[#faf7f4]' : '' }}">
                                <input type="radio" name="payment_method" value="cod"
                                       {{ old('payment_method', 'cod') == 'cod' ? 'checked' : '' }}
                                       class="h-4 w-4 text-[#b8847e]">
                                <div class="ml-3">
                                    <span class="font-medium text-gray-900">Thanh toán khi nhận hàng (COD)</span>
                                    <p class="text-sm text-gray-500">Thanh toán bằng tiền mặt khi nhận hàng</p>
                                </div>
                            </label>

                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-[#c9a9a6] {{ old('payment_method') == 'vnpay' ? 'border-[#c9a9a6] bg-[#faf7f4]' : '' }}">
                                <input type="radio" name="payment_method" value="vnpay"
                                       {{ old('payment_method') == 'vnpay' ? 'checked' : '' }}
                                       class="h-4 w-4 text-[#b8847e]">
                                <div class="ml-3">
                                    <span class="font-medium text-gray-900">VNPay</span>
                                    <p class="text-sm text-gray-500">Thanh toán trực tuyến qua VNPay Sandbox</p>
                                </div>
                            </label>
                        </div>
                        @error('payment_method')
                            <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Order Summary --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Đơn hàng của bạn</h2>

                        <div class="space-y-3 mb-4">
                            @foreach($cartItems as $item)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">{{ $item->product->name }} x{{ $item->quantity }}</span>
                                    <span class="font-medium">{{ number_format($item->price * $item->quantity, 0, ',', '.') }} ₫</span>
                                </div>
                            @endforeach
                        </div>

                        @auth
                        {{-- Coupon Input --}}
                        <div class="border-t pt-4 mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mã giảm giá</label>

                            {{-- View khi chưa có coupon --}}
                            <div x-show="!appliedCoupon" class="flex gap-2">
                                <input type="text" x-model="couponCode" placeholder="Nhập mã giảm giá"
                                       class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-[#b8847e] focus:border-[#b8847e]"
                                       :disabled="loading">
                                <button type="button" @click="applyCoupon()"
                                        :disabled="loading || !couponCode.trim()"
                                        class="px-4 py-2 bg-[#b8847e] text-white text-sm rounded-lg hover:bg-[#a6736d] transition disabled:opacity-50">
                                    <span x-show="!loading">Áp dụng</span>
                                    <span x-show="loading" x-cloak>...</span>
                                </button>
                            </div>

                            {{-- View khi đã có coupon --}}
                            <div x-show="appliedCoupon" x-cloak class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm font-medium text-green-800" x-text="appliedCoupon"></span>
                                </div>
                                <button type="button" @click="removeCoupon()"
                                        class="text-sm text-red-500 hover:text-red-700 underline">
                                    Xóa
                                </button>
                            </div>

                            {{-- Error message --}}
                            <div x-show="couponError" x-cloak class="mt-2 text-sm text-red-600" x-text="couponError"></div>
                        </div>
                        @else
                            <p class="border-t pt-4 mb-4 text-sm text-gray-500">
                                <a href="{{ route('login') }}" class="text-[#b8847e] underline">Đăng nhập</a> để dùng mã giảm giá.
                            </p>
                        @endauth

                        <div class="border-t pt-3 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Tạm tính</span>
                                <span x-text="formatCurrency({{ $cartTotal }})"></span>
                            </div>

                            {{-- Discount line --}}
                            <div x-show="discount > 0" x-cloak class="flex justify-between text-sm">
                                <span class="text-green-600">Giảm giá</span>
                                <span class="text-green-600" x-text="'-' + formatCurrency(discount)"></span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600" x-text="province ? 'Phí vận chuyển' : 'Phí vận chuyển (ước tính)'">Phí vận chuyển</span>
                                <span x-text="shippingFee > 0 ? formatCurrency(shippingFee) : 'Miễn phí'" :class="shippingFee === 0 ? 'text-green-600' : ''"></span>
                            </div>
                            <p class="text-xs text-gray-500 -mt-1" x-show="province" x-cloak data-testid="shipping-hint"
                               x-text="shippingFee === 0
                                   ? 'Miễn phí vì đơn từ ' + formatCurrency(rule.threshold) + ' (' + (zone || 'mặc định') + ', phí thường ' + formatCurrency(rule.fee) + ')'
                                   : 'Mua thêm ' + formatCurrency(rule.threshold - cartTotal) + ' để được miễn phí ship (' + (zone || 'mặc định') + ')'"></p>

                            <div x-show="pricing.taxRate > 0" x-cloak class="flex justify-between text-sm" data-testid="tax-line">
                                <span class="text-gray-600" x-text="pricing.taxLabel + ' (' + pricing.taxRate + '%)'"></span>
                                <span x-text="formatCurrency(taxAmount)"></span>
                            </div>

                            <div class="flex justify-between text-lg font-bold border-t pt-2">
                                <span>Tổng cộng</span>
                                <span class="text-[#b8847e]" x-text="formatCurrency(finalTotal)"></span>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full bg-[#b8847e] text-white py-3 rounded-lg font-semibold hover:bg-[#a6736d] transition mt-6">
                            Xác nhận đơn hàng
                        </button>
                        <p class="text-xs text-gray-500 mt-3 text-center">
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
                province: @js(old('shipping_province', '')),

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
