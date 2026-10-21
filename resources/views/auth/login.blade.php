<x-layouts.app :title="'Đăng nhập'">
    <div class="min-h-screen flex">
        {{-- Left: Branding (hidden on mobile) --}}
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-[#b8847e] via-[#c9a9a6] to-[#e8c4c4] relative overflow-hidden items-center justify-center animate-gradient">
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-20 -right-20 w-80 h-80 bg-white/10 rounded-full blur-3xl animate-float"></div>
                <div class="absolute -bottom-32 -left-20 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
                <div class="absolute top-1/4 right-1/4 w-3 h-3 bg-white/40 rounded-full animate-float" style="animation-delay: 0.8s"></div>
            </div>
            <div class="text-center relative z-10 px-12">
                <div class="text-6xl mb-6">🛍️</div>
                <h2 class="text-4xl font-extrabold text-white mb-4">SocialShop</h2>
                <p class="text-white/80 text-lg max-w-sm mx-auto">Mua sắm thông minh, chia sẻ tiện lợi. Kết nối và khám phá sản phẩm chất lượng.</p>
                <div class="grid grid-cols-3 gap-6 max-w-sm mx-auto mt-10">
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-white">10K+</div>
                        <div class="text-xs text-white/60 mt-1">Sản phẩm</div>
                    </div>
                    <div class="text-center border-x border-white/20">
                        <div class="text-2xl font-extrabold text-white">50K+</div>
                        <div class="text-xs text-white/60 mt-1">Khách hàng</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-extrabold text-white">99%</div>
                        <div class="text-xs text-white/60 mt-1">Hài lòng</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Login Form --}}
        <div class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50">
            <div class="max-w-md w-full animate-fade-in-up">
                {{-- Logo (mobile only) --}}
                <div class="text-center mb-8 lg:hidden">
                    <a href="{{ route('home') }}" class="text-3xl font-extrabold gradient-text">SocialShop</a>
                </div>

                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900">Đăng nhập</h2>
                    <p class="mt-2 text-gray-500 text-sm">Chào mừng bạn đến với SocialShop</p>
                </div>

                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 p-8">
                    {{-- Google Login --}}
                    <a href="{{ route('auth.google') }}"
                       class="w-full flex items-center justify-center gap-3 bg-white border-2 border-gray-200 rounded-xl px-4 py-3 hover:bg-gray-50 hover:border-gray-300 transition-all group">
                        <svg class="h-5 w-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span class="font-semibold text-gray-700 group-hover:text-gray-900 transition-colors">Đăng nhập với Google</span>
                    </a>

                    @if(config('services.facebook.client_id'))
                        {{-- Facebook Login (chỉ hiện khi đã cấu hình FACEBOOK_CLIENT_ID) --}}
                        <a href="{{ route('auth.facebook') }}"
                           class="mt-3 w-full flex items-center justify-center gap-3 bg-[#1877F2] border-2 border-[#1877F2] rounded-xl px-4 py-3 hover:bg-[#166fe5] transition-all">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="#fff" aria-hidden="true">
                                <path d="M24 12.07C24 5.41 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.04V9.41c0-3.02 1.8-4.7 4.54-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.5c-1.5 0-1.96.93-1.96 1.89v2.26h3.33l-.53 3.5h-2.8V24C19.62 23.1 24 18.1 24 12.07z"/>
                            </svg>
                            <span class="font-semibold text-white">Đăng nhập với Facebook</span>
                        </a>
                    @endif

                    {{-- Divider --}}
                    <div class="divider-line my-6">
                        <span class="text-xs text-gray-400 bg-white px-2">hoặc đăng nhập bằng email</span>
                    </div>

                    {{-- Email/Password Form --}}
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" required autofocus
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
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Mật khẩu</label>
                                <input type="password" name="password" required
                                       placeholder="••••••••"
                                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all bg-gray-50 focus:bg-white @error('password') border-red-400 @enderror">
                                @error('password')
                                    <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="flex items-center justify-between">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-[#b8847e] focus:ring-[#c9a9a6]">
                                    <span class="text-sm text-gray-600">Ghi nhớ đăng nhập</span>
                                </label>
                                <a href="{{ route('password.request') }}" class="text-sm text-[#b8847e] font-semibold hover:text-[#a6736d] transition-colors">Quên mật khẩu?</a>
                            </div>

                            <button type="submit"
                                    class="w-full bg-[#b8847e] text-white py-3 rounded-xl font-bold hover:bg-[#a6736d] transition-all shadow-lg shadow-[#e8c4c4] btn-shine">
                                Đăng nhập
                            </button>
                        </div>
                    </form>
                </div>

                <p class="mt-6 text-center text-sm text-gray-500">
                    Chưa có tài khoản?
                    <a href="{{ route('register') }}" class="text-[#b8847e] font-semibold hover:text-[#a6736d] transition-colors">Đăng ký ngay</a>
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>
