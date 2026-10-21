<x-layouts.app :title="'Đặt lại mật khẩu'">
    <div class="min-h-screen flex">
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-[#b8847e] via-[#c9a9a6] to-[#e8c4c4] relative overflow-hidden items-center justify-center animate-gradient">
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-20 -right-20 w-80 h-80 bg-white/10 rounded-full blur-3xl animate-float"></div>
                <div class="absolute -bottom-32 -left-20 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
                <div class="absolute top-1/4 left-1/4 w-3 h-3 bg-white/40 rounded-full animate-float" style="animation-delay: 1s"></div>
            </div>
            <div class="text-center relative z-10 px-12">
                <div class="text-6xl mb-6">🛡️</div>
                <h2 class="text-4xl font-extrabold text-white mb-4">Tạo mật khẩu mới</h2>
                <p class="text-white/80 text-lg max-w-sm mx-auto">Chọn mật khẩu mạnh để bảo vệ tài khoản SocialShop của bạn.</p>
                <div class="flex flex-col gap-4 max-w-sm mx-auto mt-10 text-left">
                    <div class="flex items-center gap-3 bg-white/10 rounded-xl px-4 py-3">
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <span class="text-white/90 text-sm">Ít nhất 8 ký tự</span>
                    </div>
                    <div class="flex items-center gap-3 bg-white/10 rounded-xl px-4 py-3">
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <span class="text-white/90 text-sm">Thay đổi có hiệu lực ngay lập tức</span>
                    </div>
                    <div class="flex items-center gap-3 bg-white/10 rounded-xl px-4 py-3">
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <span class="text-white/90 text-sm">Đăng nhập lại bằng mật khẩu mới</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50">
            <div class="max-w-md w-full animate-fade-in-up">
                <div class="text-center mb-8 lg:hidden">
                    <a href="{{ route('home') }}" class="text-3xl font-extrabold gradient-text">SocialShop</a>
                </div>

                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900">Đặt lại mật khẩu</h2>
                    <p class="mt-2 text-gray-500 text-sm">Tạo mật khẩu mới cho tài khoản của bạn</p>
                </div>

                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 p-8">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
                                <input type="email" name="email" value="{{ old('email', $email ?? '') }}" required
                                       placeholder="your@email.com"
                                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all bg-gray-50 focus:bg-white @error('email') border-red-400 @enderror">
                                @error('email')
                                    <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Mật khẩu mới</label>
                                <input type="password" name="password" required
                                       placeholder="Ít nhất 8 ký tự"
                                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all bg-gray-50 focus:bg-white @error('password') border-red-400 @enderror">
                                @error('password')
                                    <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Xác nhận mật khẩu mới</label>
                                <input type="password" name="password_confirmation" required
                                       placeholder="Nhập lại mật khẩu mới"
                                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                            </div>

                            <button type="submit"
                                    class="w-full bg-[#b8847e] text-white py-3 rounded-xl font-bold hover:bg-[#a6736d] transition-all shadow-lg shadow-[#e8c4c4] btn-shine">
                                Đặt lại mật khẩu
                            </button>
                        </div>
                    </form>
                </div>

                <p class="mt-6 text-center text-sm text-gray-500">
                    Quay lại?
                    <a href="{{ route('login') }}" class="text-[#b8847e] font-semibold hover:text-[#a6736d] transition-colors">Đăng nhập</a>
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>
