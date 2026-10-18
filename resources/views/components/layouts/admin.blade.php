<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} - {{ config('app.name') }}</title>
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
    @stack('styles')
</head>
<body class="bg-[#f7f5f2] text-[#2c2a28] min-h-screen" x-data="{ sidebarOpen: false }">

    <div class="flex min-h-screen">

        {{-- Sidebar Overlay (mobile) --}}
        <div x-show="sidebarOpen" x-cloak
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm md:hidden">
        </div>

        {{-- Sidebar (Obsidian Dark Luxury Theme) --}}
        <aside class="w-64 bg-[#181716] text-[#c5beb7] border-r border-[#262422] h-screen fixed z-50 flex flex-col
                       -translate-x-full transition-transform duration-300
                       md:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            {{-- Brand Logo --}}
            <div class="h-16 px-6 flex items-center justify-between border-b border-[#262422]">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#b8847e] to-[#a6736d] flex items-center justify-center text-white font-serif font-bold text-base shadow-sm">
                        S
                    </span>
                    <div>
                        <span class="font-serif font-bold text-lg text-white tracking-wide block leading-none">SocialShop</span>
                        <span class="text-[10px] text-[#b8847e] uppercase tracking-wider font-semibold">Admin Panel</span>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="md:hidden text-[#78716c] hover:text-white p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Grouped Navigation Links --}}
            <nav class="flex-1 overflow-y-auto px-4 py-5 space-y-6 text-xs">

                {{-- Nhóm: TỔNG QUAN --}}
                <div>
                    <p class="px-3 text-[10px] uppercase font-bold tracking-widest text-[#78716c] mb-2">Tổng quan</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.dashboard') }}"
                           class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-[#b8847e] text-white font-bold shadow-md' : 'text-[#a8a29e] hover:bg-[#262422] hover:text-white' }}">
                            <span class="flex items-center gap-2.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                <span>Dashboard</span>
                            </span>
                            @if(request()->routeIs('admin.dashboard'))
                                <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                            @endif
                        </a>

                        <a href="{{ route('admin.analytics.index') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.analytics.*') ? 'bg-[#b8847e] text-white font-bold shadow-md' : 'text-[#a8a29e] hover:bg-[#262422] hover:text-white' }}">
                            <span class="flex items-center gap-2.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <span>Phân tích dữ liệu</span>
                            </span>
                        </a>
                    </div>
                </div>

                {{-- Nhóm: CỬA HÀNG --}}
                <div>
                    <p class="px-3 text-[10px] uppercase font-bold tracking-widest text-[#78716c] mb-2">Cửa hàng</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.products.index') }}"
                           class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.products.*') ? 'bg-[#b8847e] text-white font-bold shadow-md' : 'text-[#a8a29e] hover:bg-[#262422] hover:text-white' }}">
                            <span class="flex items-center gap-2.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <span>Sản phẩm</span>
                            </span>
                            @if(request()->routeIs('admin.products.*'))
                                <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                            @endif
                        </a>

                        <a href="{{ route('admin.orders.index') }}"
                           class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.orders.*') ? 'bg-[#b8847e] text-white font-bold shadow-md' : 'text-[#a8a29e] hover:bg-[#262422] hover:text-white' }}">
                            <span class="flex items-center gap-2.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <span>Đơn hàng</span>
                            </span>
                            @if(request()->routeIs('admin.orders.*'))
                                <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                            @endif
                        </a>

                        <a href="{{ route('admin.categories.index') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.categories.*') ? 'bg-[#b8847e] text-white font-bold shadow-md' : 'text-[#a8a29e] hover:bg-[#262422] hover:text-white' }}">
                            <span class="flex items-center gap-2.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                <span>Danh mục</span>
                            </span>
                        </a>

                        <a href="{{ route('admin.reviews.index') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.reviews.*') ? 'bg-[#b8847e] text-white font-bold shadow-md' : 'text-[#a8a29e] hover:bg-[#262422] hover:text-white' }}">
                            <span class="flex items-center gap-2.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                                <span>Đánh giá</span>
                            </span>
                        </a>
                    </div>
                </div>

                {{-- Nhóm: MARKETING & AI --}}
                <div>
                    <p class="px-3 text-[10px] uppercase font-bold tracking-widest text-[#78716c] mb-2">Marketing & AI</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.coupons.index') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.coupons.*') ? 'bg-[#b8847e] text-white font-bold shadow-md' : 'text-[#a8a29e] hover:bg-[#262422] hover:text-white' }}">
                            <span class="flex items-center gap-2.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                                </svg>
                                <span>Mã giảm giá</span>
                            </span>
                        </a>

                        <a href="{{ route('admin.campaigns.index') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.campaigns.*') ? 'bg-[#b8847e] text-white font-bold shadow-md' : 'text-[#a8a29e] hover:bg-[#262422] hover:text-white' }}">
                            <span class="flex items-center gap-2.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                </svg>
                                <span>Chiến dịch quảng cáo</span>
                            </span>
                        </a>

                        <a href="{{ route('admin.chatbot-faqs.index') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.chatbot-faqs.*') ? 'bg-[#b8847e] text-white font-bold shadow-md' : 'text-[#a8a29e] hover:bg-[#262422] hover:text-white' }}">
                            <span class="flex items-center gap-2.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <span>Chatbot FAQ</span>
                            </span>
                        </a>
                    </div>
                </div>

                {{-- Nhóm: HỆ THỐNG --}}
                <div>
                    <p class="px-3 text-[10px] uppercase font-bold tracking-widest text-[#78716c] mb-2">Hệ thống</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.users.index') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.users.*') ? 'bg-[#b8847e] text-white font-bold shadow-md' : 'text-[#a8a29e] hover:bg-[#262422] hover:text-white' }}">
                            <span class="flex items-center gap-2.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <span>Người dùng</span>
                            </span>
                        </a>

                        <a href="{{ route('admin.settings.index') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.settings.*') ? 'bg-[#b8847e] text-white font-bold shadow-md' : 'text-[#a8a29e] hover:bg-[#262422] hover:text-white' }}">
                            <span class="flex items-center gap-2.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Cài đặt (Thuế, Ship)</span>
                            </span>
                        </a>

                        <a href="{{ route('admin.audit-logs.index') }}"
                           class="flex items-center px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.audit-logs.*') ? 'bg-[#b8847e] text-white font-bold shadow-md' : 'text-[#a8a29e] hover:bg-[#262422] hover:text-white' }}">
                            <span class="flex items-center gap-2.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Audit Log</span>
                            </span>
                        </a>
                    </div>
                </div>

            </nav>

            {{-- Bottom Admin User Profile Card --}}
            <div class="p-4 border-t border-[#262422] flex items-center justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#b8847e]/30 to-[#b8847e]/10 border border-[#b8847e]/30 flex items-center justify-center text-[#b8847e] font-bold text-xs flex-shrink-0">
                        {{ mb_substr(Auth::user()->name ?? 'A', 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-white truncate">{{ Auth::user()->name }}</p>
                        <p class="text-[10px] text-[#78716c] truncate">Quản trị viên</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-[#78716c] hover:text-rose-400 p-1.5 rounded-lg hover:bg-[#262422] transition-colors" title="Đăng xuất">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main Content Column --}}
        <div class="flex-1 md:ml-64 min-w-0">
            {{-- Top Bar Header --}}
            <header class="bg-white border-b border-[#efe8e3] h-16 flex items-center px-4 sm:px-8 sticky top-0 z-30 shadow-sm">
                {{-- Mobile Hamburger --}}
                <button @click="sidebarOpen = true" class="md:hidden mr-4 text-[#2c2a28] hover:text-[#b8847e] p-1.5 rounded-lg hover:bg-[#faf7f4]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <h1 class="font-serif text-lg sm:text-xl font-bold text-[#2c2a28] truncate">{{ $header ?? 'Dashboard' }}</h1>

                <div class="ml-auto flex items-center gap-3 sm:gap-4">
                    {{-- Quick Store Link --}}
                    <a href="{{ route('home') }}" target="_blank" rel="noopener"
                       class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-[#6b6560] hover:text-[#b8847e] bg-[#faf7f4] border border-[#efe8e3] px-3.5 py-1.5 rounded-xl transition-all shadow-sm" title="Mở trang cửa hàng">
                        <svg class="h-4 w-4 text-[#b8847e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        <span>Xem cửa hàng</span>
                    </a>

                    {{-- Chuông thông báo: data-testid="admin-bell" --}}
                    <div class="relative" data-testid="admin-bell"
                         x-data="{
                             open: false,
                             unread: {{ Auth::user()->unreadNotifications()->count() }},
                             items: [],
                             async load() {
                                 try {
                                     const res = await fetch('{{ route('admin.notifications.feed') }}', { headers: { 'Accept': 'application/json' } });
                                     if (!res.ok) return;
                                     const data = await res.json();
                                     this.unread = data.unread;
                                     this.items = data.items;
                                 } catch (e) {}
                             }
                         }"
                         x-init="load(); setInterval(() => load(), 30000)"
                         @click.outside="open = false">
                        <button type="button" @click="open = !open; if (open) load()" class="relative p-2 rounded-xl text-[#6b6560] hover:text-[#2c2a28] hover:bg-[#faf7f4] transition-colors" aria-label="Thông báo">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span x-show="unread > 0" x-cloak x-text="unread > 99 ? '99+' : unread"
                                  class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] leading-[18px] text-center font-bold shadow-sm"></span>
                        </button>
                        <div x-show="open" x-cloak x-transition
                             class="absolute right-0 mt-2 w-80 max-w-[90vw] bg-white rounded-2xl shadow-xl border border-[#efe8e3] z-50 overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-[#efe8e3] bg-[#faf7f4]/60">
                                <span class="font-serif font-bold text-sm text-[#2c2a28]">Thông báo</span>
                                <a href="{{ route('admin.notifications.index') }}" class="text-xs text-[#b8847e] hover:underline font-semibold">Xem tất cả</a>
                            </div>
                            <div class="max-h-96 overflow-y-auto divide-y divide-[#efe8e3]">
                                <template x-for="n in items" :key="n.id">
                                    <a :href="n.open_url" class="flex gap-3 px-4 py-3 hover:bg-[#faf7f4] transition-colors" :class="n.read ? '' : 'bg-[#faf7f4]/40'">
                                        <span class="mt-1.5 h-2 w-2 rounded-full flex-shrink-0"
                                              :class="{ 'bg-sky-500': n.level === 'info', 'bg-emerald-500': n.level === 'success', 'bg-amber-500': n.level === 'warning', 'bg-rose-500': n.level === 'danger' }"></span>
                                        <span class="min-w-0">
                                            <span class="block text-xs font-bold text-[#2c2a28]" :class="n.read ? 'font-normal text-[#6b6560]' : 'font-bold text-[#2c2a28]'" x-text="n.title"></span>
                                            <span class="block text-[11px] text-[#9a9490] truncate" x-text="n.message"></span>
                                            <span class="block text-[10px] text-[#b8847e] mt-0.5" x-text="n.time"></span>
                                        </span>
                                    </a>
                                </template>
                                <p x-show="!items.length" class="px-4 py-6 text-center text-xs text-[#9a9490]">Chưa có thông báo.</p>
                            </div>
                        </div>
                    </div>

                    <span class="text-xs text-[#6b6560] font-medium hidden sm:inline">
                        Xin chào, <strong class="text-[#2c2a28]">{{ Auth::user()->name }}</strong>
                    </span>
                </div>
            </header>

            {{-- Page Content Wrapper --}}
            <div class="p-4 sm:p-6 lg:p-8">
                @if(session('success'))
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-3.5 rounded-2xl mb-6 text-xs sm:text-sm flex items-center gap-2 shadow-sm">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-3.5 rounded-2xl mb-6 text-xs sm:text-sm flex items-center gap-2 shadow-sm">
                        <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-3.5 rounded-2xl mb-6 text-xs sm:text-sm shadow-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
