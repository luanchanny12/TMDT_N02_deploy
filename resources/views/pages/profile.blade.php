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

            {{-- Social Commerce & Referral Hub --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- 1. Mời bạn mới nhận thưởng theo mốc (MB Bank Style) --}}
                @php
                    $invitedTotal = $stats['history']->count();
                    $milestones = [
                        ['target' => 1, 'reward' => '30.000₫', 'label' => 'Mốc Đồng (1 bạn)'],
                        ['target' => 3, 'reward' => '100.000₫', 'label' => 'Mốc Bạc (3 bạn)'],
                        ['target' => 5, 'reward' => '200.000₫', 'label' => 'Mốc Vàng (5 bạn)'],
                        ['target' => 10, 'reward' => '500.000₫', 'label' => 'Mốc VIP (10 bạn)'],
                    ];
                    $nextMilestone = collect($milestones)->first(fn($m) => $m['target'] > $invitedTotal) ?? end($milestones);
                    $progressPercent = min(100, round(($invitedTotal / $nextMilestone['target']) * 100));
                @endphp
                <div class="bg-white rounded-2xl border border-[#efe8e3] shadow-sm p-6 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#b8847e] to-[#a6736d] text-white flex items-center justify-center text-sm font-bold shadow-xs">🏆</span>
                            <div>
                                <h2 class="text-sm font-bold text-gray-900">Mời bạn mới nhận thưởng</h2>
                                <p class="text-[11px] text-gray-500">Thưởng theo bậc thang mốc mời bạn bè</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-[#b8847e] bg-[#faf7f4] border border-[#efe8e3] px-2.5 py-1 rounded-full">
                            {{ $invitedTotal }} bạn đã mời
                        </span>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mb-4 bg-[#faf7f4] p-3 rounded-xl border border-[#efe8e3]">
                        <div class="flex justify-between items-center text-xs mb-1.5">
                            <span class="font-medium text-gray-700">Mục tiêu: {{ $nextMilestone['label'] }}</span>
                            <span class="font-bold text-[#b8847e]">+{{ $nextMilestone['reward'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 h-2.5 rounded-full overflow-hidden">
                            <div class="bg-gradient-to-r from-[#b8847e] to-[#a6736d] h-2.5 rounded-full transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1">
                            @if($invitedTotal >= 10)
                                🎉 Bạn đã chinh phục toàn bộ các mốc thưởng cao nhất!
                            @else
                                Chỉ cần mời thêm {{ max(0, $nextMilestone['target'] - $invitedTotal) }} bạn mới nữa để mở khóa mốc này.
                            @endif
                        </p>
                    </div>

                    {{-- Referral Code & Link Box --}}
                    <div class="space-y-2.5 text-xs">
                        <div>
                            <span class="text-gray-500 block mb-1">Mã giới thiệu của bạn:</span>
                            <input type="text" readonly value="{{ $referralCode }}" class="w-full bg-[#faf7f4] border border-gray-200 rounded-xl px-3 py-2 text-center font-bold text-[#b8847e] text-sm">
                        </div>
                        <div>
                            <span class="text-gray-500 block mb-1">Link mời bạn đăng ký:</span>
                            <div class="flex items-center gap-1.5">
                                <input type="text" readonly id="referral-url-input" value="{{ url('/register') }}?ref={{ $referralCode }}" class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-600 truncate focus:outline-none">
                                <button type="button" onclick="copyReferralCode()" class="px-3 py-2 bg-[#b8847e] hover:bg-[#a6736d] text-white rounded-xl font-medium transition-colors whitespace-nowrap">
                                    Chép link
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Tủ đồ trưng bày TikTok Showcase (Affiliate) --}}
                @php
                    $showcaseUrl = route('wishlist.shared', ['code' => $referralCode]);
                @endphp
                <div class="bg-white rounded-2xl border border-[#efe8e3] shadow-sm p-6 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-[#2c2a28] text-white flex items-center justify-center text-sm font-bold shadow-xs">🛍️</span>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">Tủ đồ trưng bày (Showcase)</h3>
                                <p class="text-[11px] text-gray-500">Gắn giỏ hàng cá nhân nhận hoa hồng 5%</p>
                            </div>
                        </div>
                        <a href="{{ $showcaseUrl }}" target="_blank" class="text-[11px] text-[#b8847e] hover:underline font-semibold flex items-center gap-0.5">
                            Xem tủ đồ ↗
                        </a>
                    </div>

                    {{-- Double-Bonus Policy Card --}}
                    <div class="bg-gradient-to-br from-[#faf7f4] to-[#f5eeea] border border-[#efe8e3] p-3 rounded-xl mb-4 text-xs space-y-1.5">
                        <div class="font-bold text-[#2c2a28] flex items-center gap-1 text-[11px]">
                            <span>✨ Chính sách ăn hoa hồng kép:</span>
                        </div>
                        <p class="text-[11px] text-gray-600 leading-relaxed">
                            • <strong>Khách chưa có tài khoản:</strong> Bạn nhận <strong>CẢ HAI</strong> (vừa cộng +1 bạn vào mốc MB Bank, vừa nhận <strong>5% hoa hồng</strong> khi bạn bè mua hàng).<br>
                            • <strong>Khách đã có tài khoản:</strong> Bạn nhận <strong>5% hoa hồng</strong> ngay khi khách đặt mua từ Tủ đồ của bạn!
                        </p>
                    </div>

                    <div class="space-y-2">
                        <span class="text-gray-500 text-xs block">Link Tủ đồ trưng bày của bạn:</span>
                        <div class="flex items-center gap-1.5">
                            <input type="text" readonly id="showcase-url-input" value="{{ $showcaseUrl }}" class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-600 truncate focus:outline-none">
                            <button type="button" onclick="copyShowcaseLink()" id="btn-copy-showcase" class="px-3 py-2 bg-[#2c2a28] hover:bg-black text-white rounded-xl text-xs font-medium transition-colors whitespace-nowrap">
                                Chép link
                            </button>
                        </div>
                    </div>
                </div>

                {{-- 3. Thống kê Hoa hồng --}}
                <div class="bg-white rounded-2xl border border-[#efe8e3] shadow-sm p-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center justify-between">
                        <span>Ví hoa hồng tích lũy</span>
                        <span class="text-xs text-gray-400 font-normal">Tự động cộng khi giao xong</span>
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-amber-50/60 border border-amber-200/60 p-3 rounded-xl">
                            <p class="text-[11px] text-amber-800 font-medium">Chờ duyệt (Đang giao)</p>
                            <p class="text-base font-bold text-amber-600 mt-1">{{ number_format($stats['pending_commission'], 0, ',', '.') }}₫</p>
                        </div>
                        <div class="bg-emerald-50/60 border border-emerald-200/60 p-3 rounded-xl">
                            <p class="text-[11px] text-emerald-800 font-medium">Đã xác nhận</p>
                            <p class="text-base font-bold text-emerald-600 mt-1">{{ number_format($stats['completed_commission'], 0, ',', '.') }}₫</p>
                        </div>
                    </div>
                </div>

                {{-- 4. Lịch sử giới thiệu & Đơn hàng liên kết --}}
                <div class="bg-white rounded-2xl border border-[#efe8e3] shadow-sm p-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Lịch sử giới thiệu & Đơn liên kết</h3>
                    
                    @if($stats['history']->isEmpty())
                        <div class="text-center py-6 bg-gray-50 rounded-xl">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <p class="text-xs text-gray-500">Chưa có lượt mời hoặc đơn hàng liên kết nào.</p>
                        </div>
                    @else
                        <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                            @foreach($stats['history'] as $referral)
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 text-xs">
                                    <div class="flex justify-between items-start mb-1.5">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 bg-[#e8c4c4] text-[#b8847e] rounded-full flex items-center justify-center text-[10px] font-bold">
                                                {{ substr($referral->referredUser->name, 0, 1) }}
                                            </div>
                                            <p class="font-medium text-gray-900">{{ $referral->referredUser->name }}</p>
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
                                        <div class="text-[11px] text-gray-500 flex justify-between mt-1 pt-1.5 border-t border-gray-200">
                                            <span>Đơn: #{{ $referral->order_id }}</span>
                                            <span>Hoa hồng: <strong class="text-[#b8847e] font-bold">{{ number_format($referral->commission, 0, ',', '.') }}₫</strong></span>
                                        </div>
                                    @else
                                        <p class="text-[10px] text-gray-400 mt-0.5 italic">Khách mới đăng ký, chưa phát sinh đơn</p>
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

        function copyShowcaseLink() {
            const showcaseInput = document.getElementById('showcase-url-input');
            const link = showcaseInput ? showcaseInput.value : '';
            const btn = document.getElementById('btn-copy-showcase');
            const done = () => {
                if (btn) btn.textContent = 'Đã chép ✓';
                setTimeout(() => { if (btn) btn.textContent = 'Chép link'; }, 2000);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(link).then(done);
            } else {
                const textArea = document.createElement("textarea");
                textArea.value = link;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    done();
                } catch (err) {
                    alert('Copy link: ' + link);
                }
                document.body.removeChild(textArea);
            }
        }
    </script>
    @endpush
</x-layouts.app>
