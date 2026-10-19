<x-layouts.app :title="'Tài khoản'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Tài khoản</h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Profile Form --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Thông tin cá nhân</h2>

                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Họ và tên</label>
                                <input type="text" name="name" value="{{ $user->name }}"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" value="{{ $user->email }}" disabled
                                       class="w-full border border-gray-200 rounded-lg px-4 py-2 bg-gray-50 text-gray-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                                <input type="text" name="phone" value="{{ $user->phone }}"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ</label>
                                <input type="text" name="address" value="{{ $user->address }}"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            </div>
                        </div>

                        <input type="hidden" name="marketing_consent" value="0">
                        <label class="mt-4 flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="marketing_consent" value="1" @checked($user->marketing_consent)
                                   class="mt-1 h-4 w-4 text-[#b8847e] border-gray-300 rounded">
                            <span>Nhận email về sản phẩm mới, nhắc giỏ hàng và ưu đãi
                                @if($user->marketing_consent && $user->marketing_consent_at)
                                    <span class="block text-xs text-gray-500">Đã đồng ý lúc {{ $user->marketing_consent_at->format('H:i d/m/Y') }}</span>
                                @endif
                            </span>
                        </label>

                        <button type="submit" class="mt-6 bg-[#b8847e] text-white px-6 py-2 rounded-lg hover:bg-[#a6736d] transition">
                            Cập nhật
                        </button>
                    </form>
                </div>

                {{-- Change Password --}}
                <div class="bg-white rounded-lg shadow p-6 mt-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Đổi mật khẩu</h2>

                    @if(session('success') && request()->is('profile') === false)
                        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('profile.password') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu hiện tại</label>
                                <input type="password" name="current_password" required
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 @error('current_password') border-red-400 @enderror">
                                @error('current_password')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
                                <input type="password" name="password" required minlength="8"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 @error('password') border-red-400 @enderror">
                                @error('password')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu mới</label>
                                <input type="password" name="password_confirmation" required minlength="8"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2">
                            </div>
                        </div>

                        <button type="submit" class="mt-6 bg-[#3d3d3d] text-white px-6 py-2 rounded-lg hover:bg-black transition">
                            Đổi mật khẩu
                        </button>
                    </form>
                </div>
            </div>

            {{-- Referral Dashboard --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- Mã giới thiệu --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#b8847e]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Chương trình giới thiệu
                    </h2>
                    <p class="text-sm text-gray-600 mb-4">Mã giới thiệu của bạn</p>
                    <div class="flex items-center gap-2 mb-4">
                        <input type="text" readonly value="{{ $referralCode }}" class="flex-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-center font-bold text-[#b8847e]">
                    </div>

                    <p class="text-sm text-gray-600 mb-2">Link giới thiệu</p>
                    <div class="flex items-center gap-2 mb-4">
                        <input type="text" readonly value="{{ url('/') }}?ref={{ $referralCode }}" class="flex-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-600 truncate">
                    </div>

                    <button onclick="copyReferralCode()"
                            class="w-full bg-[#b8847e] text-white py-2 rounded-lg hover:bg-[#a6736d] transition font-medium text-sm">
                        Copy Link Giới Thiệu
                    </button>
                </div>

                {{-- Thống kê Hoa hồng --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-md font-semibold text-gray-900 mb-4">Hoa hồng</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                            <div>
                                <p class="text-sm text-gray-600">Đang chờ duyệt</p>
                                <p class="text-xs text-gray-400 mt-0.5">Sẽ nhận khi đơn hoàn tất</p>
                            </div>
                            <span class="font-bold text-yellow-600">{{ number_format($stats['pending_commission'], 0, ',', '.') }}đ</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-600">Đã xác nhận</p>
                                <p class="text-xs text-gray-400 mt-0.5">Hoa hồng hợp lệ</p>
                            </div>
                            <span class="font-bold text-green-600">{{ number_format($stats['completed_commission'], 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                </div>

                {{-- Lịch sử giới thiệu --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-md font-semibold text-gray-900 mb-4">Lịch sử giới thiệu</h3>
                    
                    @if($stats['history']->isEmpty())
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <p class="text-sm text-gray-500">Chưa có lượt giới thiệu nào.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($stats['history'] as $referral)
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 bg-[#e8c4c4] text-[#b8847e] rounded-full flex items-center justify-center text-xs font-bold">
                                                {{ substr($referral->referredUser->name, 0, 1) }}
                                            </div>
                                            <p class="text-sm font-medium text-gray-900">{{ $referral->referredUser->name }}</p>
                                        </div>
                                        @if($referral->status === 'pending')
                                            <span class="inline-flex items-center gap-1 text-[10px] font-medium px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Chờ duyệt
                                            </span>
                                        @elseif($referral->status === 'completed')
                                            <span class="inline-flex items-center gap-1 text-[10px] font-medium px-2 py-0.5 rounded-full bg-green-100 text-green-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Đã xác nhận
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[10px] font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Đã hủy
                                            </span>
                                        @endif
                                    </div>
                                    @if($referral->order_id)
                                        <div class="text-xs text-gray-500 flex justify-between mt-1 pt-2 border-t border-gray-200">
                                            <span>Đơn hàng: #{{ $referral->order_id }}</span>
                                            <span>Hoa hồng: <strong class="text-[#3d3d3d]">{{ number_format($referral->commission, 0, ',', '.') }}đ</strong></span>
                                        </div>
                                    @else
                                        <p class="text-xs text-gray-400 mt-1 italic">Chưa phát sinh đơn hàng</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const referralLink = '{{ url('/') }}?ref={{ $referralCode }}';

        function copyReferralCode() {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(referralLink).then(() => {
                    alert('Đã copy đường link giới thiệu:\n' + referralLink);
                });
            } else {
                // Fallback cho môi trường HTTP không có SSL
                const textArea = document.createElement("textarea");
                textArea.value = referralLink;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    alert('Đã copy đường link giới thiệu:\n' + referralLink);
                } catch (err) {
                    alert('Không thể copy tự động, vui lòng copy tay:\n' + referralLink);
                }
                document.body.removeChild(textArea);
            }
        }

        function shareReferralToFacebook() {
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(referralLink)}`, '_blank');
        }

        function shareReferralToZalo() {
            const text = encodeURIComponent('Hãy mua hàng tại SocialShop bằng link này để nhận ưu đãi: ' + referralLink);
            window.open(`https://zalo.me/share/?msg=${text}`, '_blank');
        }
    </script>
    @endpush
</x-layouts.app>
