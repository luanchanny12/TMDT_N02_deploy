@props([
    'title' => null,
    'metaTitle' => null,
    'metaDescription' => null,
    'metaImage' => null,
    'metaKeywords' => null,
    'jsonLd' => null,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- SEO Meta Tags --}}
    <title>{{ $metaTitle ?? ($title ?? config('app.name', 'SocialShop')) }}</title>
    <meta name="description" content="{{ $metaDescription ?? 'SocialShop - Thời trang và phong cách sống. Mua sắm online với nhiều ưu đãi hấp dẫn.' }}">
    <meta name="keywords" content="{{ $metaKeywords ?? 'thời trang, fashion, mua sắm online, áo quần, phụ kiện' }}">
    <meta name="author" content="SocialShop">
    <meta name="robots" content="index, follow">
    
    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $metaTitle ?? ($title ?? 'SocialShop') }}">
    <meta property="og:description" content="{{ $metaDescription ?? 'SocialShop - Thời trang và phong cách sống' }}">
    <meta property="og:image" content="{{ $metaImage ?? asset('images/og-image.jpg') }}">
    <meta property="og:site_name" content="SocialShop">
    
    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle ?? ($title ?? 'SocialShop') }}">
    <meta name="twitter:description" content="{{ $metaDescription ?? 'SocialShop - Thời trang và phong cách sống' }}">
    <meta name="twitter:image" content="{{ $metaImage ?? asset('images/og-image.jpg') }}">
    
    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ url()->current() }}">
    
    {{-- JSON-LD Structured Data --}}
    @isset($jsonLd)
        <script type="application/ld+json">
            {{-- JSON_HEX_TAG: mã hóa < > để dữ liệu sản phẩm không thể đóng thẻ </script> (chống XSS) --}}
            {!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP) !!}
        </script>
    @endisset
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
    @stack('styles')
    <style>
        :root {
            --cream: #faf7f4;
            --blush: #e8c4c4;
            --rose: #c9a9a6;
            --rose-deep: #b8847e;
            --charcoal: #3d3d3d;
            --soft-border: #efe8e3;
        }
        body { background: var(--cream); color: var(--charcoal); font-family: 'DM Sans', sans-serif; }
        h1, h2, h3, h4, h5, .font-serif { font-family: 'Cormorant Garamond', serif; }
        .nav-link { color: var(--charcoal) !important; font-weight: 500; font-size: .92rem; }
        .nav-link:hover { color: var(--rose-deep) !important; }
    </style>
</head>
<body class="min-h-screen flex flex-col pb-16 md:pb-0">

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('cart', {
                count: {{ auth()->check()
                    ? app(\App\Services\Cart\CartService::class)->getItemCount(auth()->id())
                    : app(\App\Services\Cart\CartService::class)->getGuestItemCount() }}
            });
            Alpine.store('wishlist', {
                count: {{ auth()->check() && auth()->user()->wishlist ? auth()->user()->wishlist->items()->count() : 0 }}
            });
        });

        // Phím tắt tìm kiếm: "/" hoặc Ctrl/Cmd + K đưa con trỏ vào ô tìm kiếm, Esc để thoát
        document.addEventListener('keydown', (e) => {
            const input = document.getElementById('site-search');
            if (!input || input.offsetParent === null) return;

            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName) || e.target.isContentEditable;
            const ctrlK = (e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k';

            if (ctrlK || (e.key === '/' && !typing)) {
                e.preventDefault();
                input.focus();
                input.select();
            } else if (e.key === 'Escape' && e.target === input) {
                input.blur();
            }
        });
    </script>

    {{-- Top Announcement Bar --}}
    <div class="bg-gradient-to-r from-[#b8847e] via-[#c9a9a6] to-[#b8847e] text-white py-2 px-4 text-xs font-medium tracking-wide">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-2 mx-auto sm:mx-0">
                <span class="bg-white/20 px-2 py-0.5 rounded text-[11px] font-bold tracking-wider">ƯU ĐÃI</span>
                <span>✨ Miễn phí vận chuyển cho đơn hàng từ {{ number_format($promoFreeShip, 0, ',', '.') }}₫@if($promoCoupon) • Nhập mã <strong class="underline decoration-white/60 font-bold">{{ $promoCoupon->code }}</strong> giảm {{ $promoCoupon->type === 'percent' ? $promoCoupon->value.'%' : number_format($promoCoupon->value, 0, ',', '.').'₫' }}{{ $promoCoupon->type === 'percent' && $promoCoupon->max_discount ? ' (tối đa '.number_format($promoCoupon->max_discount, 0, ',', '.').'₫)' : '' }}@endif</span>
            </div>
            <div class="hidden sm:flex items-center gap-4 text-white/90 text-xs">
                @auth
                    <a href="{{ route('orders.index') }}" class="hover:text-white transition-colors">Tra cứu đơn hàng</a>
                @endauth
                <span>•</span>
                <a href="tel:077123456" class="hover:text-white transition-colors">Hotline: 077123456</a>
            </div>
        </div>
    </div>

    {{-- Header --}}
    <header class="sticky top-0 z-[60] bg-[#faf7f4]/95 backdrop-blur-md border-b border-[#efe8e3]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                {{-- Logo --}}
                <div class="flex-shrink-0">
                    <a href="{{ route('home') }}" class="flex flex-col group">
                        <span class="text-2xl sm:text-[28px] font-serif font-bold text-[#2c2a28] tracking-wider leading-none group-hover:text-[#b8847e] transition-colors">SocialShop</span>
                        <span class="text-[9px] tracking-[3px] uppercase text-[#b8847e] font-semibold mt-0.5">DK Social Commerce</span>
                    </a>
                </div>

                {{-- Search Bar --}}
                <div class="hidden md:flex flex-1 max-w-md lg:max-w-lg mx-6 lg:mx-8">
                    <form action="{{ route('products.index') }}" method="GET" class="w-full">
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" id="site-search"
                                   placeholder="Tìm váy dạ hội, áo blazer, phụ kiện..." aria-keyshortcuts="/ Control+K"
                                   class="w-full pl-10 pr-20 py-2.5 border border-[#efe8e3] rounded-full bg-white focus:outline-none focus:ring-2 focus:ring-[#b8847e]/50 focus:border-transparent transition-all text-xs text-[#3d3d3d] shadow-sm">
                            <svg class="absolute left-3.5 top-3 h-4 w-4 text-[#9a9490]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span class="pointer-events-none absolute right-3.5 top-2.5 hidden lg:flex items-center gap-1 text-[10px] text-[#9a9490] bg-[#faf7f4] px-1.5 py-0.5 rounded border border-[#efe8e3]" title="Phím tắt tìm kiếm">
                                <kbd class="font-sans">Ctrl</kbd>
                                <kbd class="font-sans">K</kbd>
                            </span>
                        </div>
                    </form>
                </div>

                {{-- Navigation --}}
                <nav class="flex items-center space-x-1 sm:space-x-2">
                    {{-- Category menu (desktop hover + mobile accordion) --}}
                    {{-- Mobile: không "relative" để dropdown căn theo header (sticky), tránh tràn mép trái --}}
                    <div class="md:relative group"
                         x-data="{ mobile: false, expanded: null }"
                         @click.outside="mobile = false">
                        <button type="button"
                                class="nav-link px-3 py-2 flex items-center gap-1 whitespace-nowrap"
                                @click="if (window.innerWidth >= 768) { window.location.href = '{{ route('products.index') }}'; } else { mobile = !mobile; }">
                            Danh mục
                            <svg class="h-3.5 w-3.5 transition-transform duration-200 md:group-hover:rotate-180"
                                 :class="mobile ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- Desktop dropdown --}}
                        <div class="hidden md:block absolute left-0 top-full pt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-150 z-[60]">
                            <div class="w-64 max-h-[70vh] overflow-y-auto bg-white rounded-xl shadow-lg border border-[#efe8e3] py-2">
                                @forelse(($navCategories ?? collect()) as $parent)
                                    <div class="px-4 py-1.5">
                                        <a href="{{ route('categories.show', $parent) }}"
                                           class="block text-sm font-semibold text-[#3d3d3d] hover:text-[#b8847e] transition-colors">
                                            {{ $parent->name }}
                                        </a>
                                        @if($parent->children->isNotEmpty())
                                            <ul class="mt-1 space-y-0.5 border-l border-[#efe8e3] pl-3">
                                                @foreach($parent->children as $child)
                                                    <li>
                                                        <a href="{{ route('categories.show', $child) }}"
                                                           class="block text-sm text-[#5a5550] hover:text-[#b8847e] transition-colors py-0.5">
                                                            {{ $child->name }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @empty
                                    <a href="{{ route('products.index') }}"
                                       class="block px-4 py-2 text-sm text-[#3d3d3d] hover:bg-[#faf7f4] transition-colors">
                                        Tất cả sản phẩm
                                    </a>
                                @endforelse
                            </div>
                        </div>

                        {{-- Mobile accordion --}}
                        <div x-show="mobile" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="md:hidden absolute inset-x-4 top-full mt-1 max-h-[70vh] overflow-y-auto bg-white rounded-xl shadow-lg border border-[#efe8e3] py-2 z-[60]">
                            @forelse(($navCategories ?? collect()) as $parent)
                                <div class="border-b border-[#efe8e3] last:border-b-0">
                                    <div class="flex items-center">
                                        <a href="{{ route('categories.show', $parent) }}"
                                           class="flex-1 px-4 py-2.5 text-sm font-semibold text-[#3d3d3d] hover:text-[#b8847e] transition-colors">
                                            {{ $parent->name }}
                                        </a>
                                        @if($parent->children->isNotEmpty())
                                            <button type="button"
                                                    class="px-3 py-2.5 text-[#9a9490] hover:text-[#b8847e]"
                                                    @click="expanded === {{ $parent->id }} ? expanded = null : expanded = {{ $parent->id }}"
                                                    :aria-expanded="expanded === {{ $parent->id }}">
                                                <svg class="h-4 w-4 transition-transform duration-200"
                                                     :class="expanded === {{ $parent->id }} ? 'rotate-180' : ''"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                    @if($parent->children->isNotEmpty())
                                        <ul x-show="expanded === {{ $parent->id }}" x-cloak
                                            x-transition:enter="transition ease-out duration-150"
                                            class="bg-[#faf7f4] border-t border-[#efe8e3]">
                                            @foreach($parent->children as $child)
                                                <li>
                                                    <a href="{{ route('categories.show', $child) }}"
                                                       class="block pl-8 pr-4 py-2 text-sm text-[#5a5550] hover:text-[#b8847e] hover:bg-white transition-colors">
                                                        {{ $child->name }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @empty
                                <a href="{{ route('products.index') }}"
                                   class="block px-4 py-2 text-sm text-[#3d3d3d] hover:bg-[#faf7f4] transition-colors">
                                    Tất cả sản phẩm
                                </a>
                            @endforelse
                        </div>
                    </div>

                    {{-- Mobile Search Toggle --}}
                    <div x-data="{ open: false }" class="md:hidden">
                        <button @click="open = !open" class="nav-link px-3 py-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             @click.away="open = false"
                             class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-lg border border-[#efe8e3] p-3 z-50">
                            <form action="{{ route('products.index') }}" method="GET">
                                <div class="relative">
                                    <input type="text" name="search" value="{{ request('search') }}"
                                           placeholder="Tìm sản phẩm..."
                                           class="w-full pl-10 pr-4 py-2.5 border border-[#efe8e3] rounded-lg bg-white focus:ring-2 focus:ring-[#c9a9a6] focus:border-transparent transition-all text-sm">
                                    <svg class="absolute left-3 top-3 h-4 w-4 text-[#9a9490]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Wishlist (Sản phẩm yêu thích) --}}
                    <a href="{{ route('wishlist.index') }}" title="Sản phẩm yêu thích"
                       class="relative nav-link p-2 sm:px-2.5 rounded-full hover:bg-white text-[#3d3d3d] hover:text-[#b8847e] transition-all flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        <span x-data x-show="$store.wishlist.count > 0" x-cloak
                              x-text="$store.wishlist.count > 99 ? '99+' : $store.wishlist.count"
                              class="absolute -top-0.5 -right-0.5 bg-[#b8847e] text-white text-[10px] font-bold rounded-full h-4 min-w-[16px] flex items-center justify-center px-1 shadow-sm"></span>
                    </a>

                    {{-- Cart (Giỏ hàng cho cả khách & thành viên) --}}
                    <a href="{{ route('cart.index') }}" title="Giỏ hàng"
                       class="relative nav-link p-2 sm:px-2.5 rounded-full hover:bg-white text-[#3d3d3d] hover:text-[#b8847e] transition-all flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span x-data x-show="$store.cart.count > 0" x-cloak
                              x-text="$store.cart.count > 99 ? '99+' : $store.cart.count"
                              class="absolute -top-0.5 -right-0.5 bg-[#b8847e] text-white text-[10px] font-bold rounded-full h-4 min-w-[16px] flex items-center justify-center px-1 shadow-sm"></span>
                    </a>

                    @auth
                        {{-- User Menu --}}
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="flex items-center space-x-1.5 nav-link pl-1 pr-2 py-1.5 rounded-full hover:bg-white transition-all border border-transparent hover:border-[#efe8e3]">
                                @if(Auth::user()->avatar)
                                    <img src="{{ Auth::user()->avatar }}" alt="{{ Auth::user()->name }}" class="h-7 w-7 rounded-full object-cover border border-[#efe8e3]">
                                @else
                                    <div class="h-7 w-7 rounded-full bg-gradient-to-tr from-[#b8847e] to-[#c9a9a6] flex items-center justify-center text-white font-bold text-xs shadow-sm">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </div>
                                @endif
                                <svg class="h-3.5 w-3.5 transition-transform text-[#9a9490]" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="open" x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 @click.away="open = false"
                                 class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-[#efe8e3] py-1.5 z-50">
                                <div class="px-4 py-3 border-b border-[#efe8e3]">
                                    <p class="font-semibold text-[#3d3d3d] text-sm truncate">{{ Auth::user()->name }}</p>
                                    <p class="text-[#9a9490] text-xs mt-0.5 truncate">{{ Auth::user()->email }}</p>
                                </div>
                                <a href="{{ route('profile') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-[#3d3d3d] hover:bg-[#faf7f4] hover:text-[#b8847e] transition-colors">
                                    <svg class="w-4 h-4 text-[#9a9490]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Tài khoản của tôi
                                </a>
                                <a href="{{ route('orders.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-[#3d3d3d] hover:bg-[#faf7f4] hover:text-[#b8847e] transition-colors">
                                    <svg class="w-4 h-4 text-[#9a9490]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    Lịch sử đơn hàng
                                </a>
                                <a href="{{ route('wishlist.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-[#3d3d3d] hover:bg-[#faf7f4] hover:text-[#b8847e] transition-colors">
                                    <svg class="w-4 h-4 text-[#9a9490]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                    Sản phẩm yêu thích
                                </a>
                                <a href="{{ route('compare.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-[#3d3d3d] hover:bg-[#faf7f4] hover:text-[#b8847e] transition-colors">
                                    <svg class="w-4 h-4 text-[#9a9490]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                                    So sánh sản phẩm
                                </a>
                                @if(Auth::user()->isAdmin())
                                    <div class="border-t border-[#efe8e3] my-1"></div>
                                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-[#b8847e] font-semibold hover:bg-[#faf7f4] transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        Trang quản trị (Admin)
                                    </a>
                                @endif
                                <div class="border-t border-[#efe8e3] mt-1 pt-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex items-center gap-2.5 w-full px-4 py-2 text-sm text-red-500 hover:bg-red-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                            Đăng xuất
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="nav-link px-3 py-2 text-sm">Đăng nhập</a>
                        <a href="{{ route('register') }}"
                           class="bg-[#b8847e] text-white px-4 py-2 rounded-full text-xs font-semibold hover:bg-[#a6736d] transition-colors shadow-sm">
                            Đăng ký
                        </a>
                    @endauth
                </nav>
            </div>
        </div>
    </header>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm" role="alert">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm" role="alert">
                {{ session('error') }}
            </div>
        </div>
    @endif

    {{-- Main Content --}}
    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer id="contact" class="bg-[#242220] text-[#c9c4be] pt-16 pb-12 mt-20 border-t border-[#3d3a37]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Newsletter VIP Banner --}}
            <div class="rounded-3xl bg-gradient-to-r from-[#2c2a28] to-[#383330] border border-white/10 p-6 sm:p-10 mb-14 flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl">
                <div class="space-y-1.5 text-center md:text-left">
                    <span class="text-[10px] uppercase tracking-[3px] text-[#e8c4c4] font-bold">BẢN TIN SOCIALSHOP</span>
                    <h3 class="font-serif text-2xl sm:text-3xl font-bold text-white">Nhận Ngay Voucher 100K</h3>
                    <p class="text-xs text-white/70 max-w-md">Đăng ký để nhận sớm các xu hướng thời trang mùa mới và ưu đãi độc quyền dành riêng cho bạn.</p>
                </div>
                <div class="flex w-full md:w-auto max-w-md gap-2" x-data="{ email: '', subscribed: false }">
                    <input type="email" x-model="email" placeholder="Nhập địa chỉ email của bạn..."
                           class="w-full px-5 py-3 rounded-full bg-white/10 border border-white/20 text-xs text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-[#b8847e] transition-all">
                    <button type="button" @click="if(email) { subscribed = true; window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Đăng ký nhận bản tin thành công! Mã 100K đã gửi vào email.', type: 'success' } })); email = ''; }"
                            class="px-6 py-3 rounded-full bg-[#b8847e] hover:bg-[#a6736d] text-white text-xs font-bold whitespace-nowrap transition-all shadow-md flex-shrink-0">
                        Đăng ký
                    </button>
                </div>
            </div>

            {{-- Footer Columns --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 sm:gap-10 pb-12 border-b border-white/10">
                {{-- Col 1: Brand Info --}}
                <div class="lg:col-span-2 space-y-4">
                    <a href="{{ route('home') }}" class="flex flex-col">
                        <span class="font-serif text-3xl font-bold text-white tracking-wider">SocialShop</span>
                        <span class="text-[9px] tracking-[3px] uppercase text-[#e8c4c4] font-semibold">DK Haute Couture</span>
                    </a>
                    <p class="text-xs text-[#9a9490] leading-relaxed max-w-sm">
                        Nền tảng thương mại điện tử tích hợp mạng xã hội, mang đến trải nghiệm mua sắm thời trang thanh lịch, tinh tế và kết nối phong cách sống hiện đại.
                    </p>
                    <div class="space-y-1.5 text-xs text-white/80">
                        <p class="flex items-center gap-2"><span>📍</span> 123 Đường Thời Trang, Quận 1, TP. Hồ Chí Minh</p>
                        <p class="flex items-center gap-2"><span>📞</span> Hotline: <a href="tel:077123456" class="hover:text-[#e8c4c4] transition-colors font-bold">077123456</a> (8:00 - 21:00)</p>
                        <p class="flex items-center gap-2"><span>✉️</span> Email: <a href="mailto:support@socialshop.vn" class="hover:text-[#e8c4c4] transition-colors">support@socialshop.vn</a></p>
                    </div>
                </div>

                {{-- Col 2: Categories --}}
                <div class="space-y-3">
                    <h5 class="font-serif text-base font-bold text-white uppercase tracking-wider">Danh Mục</h5>
                    <ul class="space-y-2 text-xs text-white/70">
                        <li><a href="{{ route('products.index') }}" class="hover:text-white transition-colors">Bộ sưu tập 2026</a></li>
                        <li><a href="{{ route('products.index', ['category' => 2]) }}" class="hover:text-white transition-colors">Thời trang nữ</a></li>
                        <li><a href="{{ route('products.index', ['category' => 1]) }}" class="hover:text-white transition-colors">Thời trang nam</a></li>
                        <li><a href="{{ route('products.index', ['category' => 3]) }}" class="hover:text-white transition-colors">Giày dép cao cấp</a></li>
                        <li><a href="{{ route('products.index', ['category' => 4]) }}" class="hover:text-white transition-colors">Túi xách & Phụ kiện</a></li>
                    </ul>
                </div>

                {{-- Col 3: Support & Policy --}}
                <div class="space-y-3">
                    <h5 class="font-serif text-base font-bold text-white uppercase tracking-wider">Hỗ Trợ Khách Hàng</h5>
                    <ul class="space-y-2 text-xs text-white/70">
                        <li><a href="{{ route('pages.buying-guide') }}" class="hover:text-white transition-colors">Hướng dẫn mua hàng</a></li>
                        <li><a href="{{ route('pages.return-policy') }}" class="hover:text-white transition-colors">Chính sách đổi trả 7 ngày</a></li>
                        <li><a href="{{ route('pages.terms') }}" class="hover:text-white transition-colors">Điều kiện giao dịch chung</a></li>
                        <li><a href="{{ route('pages.privacy') }}" class="hover:text-white transition-colors">Chính sách bảo mật</a></li>
                        <li><a href="{{ route('pages.data-inventory') }}" class="hover:text-white transition-colors">Bảng đăng ký dữ liệu cá nhân</a></li>
                        <li><a href="{{ route('pages.faq') }}" class="hover:text-white transition-colors">Câu hỏi thường gặp (FAQ)</a></li>
                    </ul>
                </div>

                {{-- Col 4: Social & Payments --}}
                <div class="space-y-4">
                    <h5 class="font-serif text-base font-bold text-white uppercase tracking-wider">Kết Nối Với Chúng Tôi</h5>
                    <div class="flex items-center gap-2.5">
                        {{-- Facebook --}}
                        <a href="https://facebook.com" target="_blank" rel="noopener noreferrer"
                           class="w-10 h-10 rounded-full bg-white/10 hover:bg-[#1877F2] text-white/90 hover:text-white flex items-center justify-center transition-all duration-200 hover:scale-110 shadow-sm"
                           title="Facebook">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>

                        {{-- Instagram --}}
                        <a href="https://instagram.com" target="_blank" rel="noopener noreferrer"
                           class="w-10 h-10 rounded-full bg-white/10 hover:bg-gradient-to-tr hover:from-[#f09433] hover:via-[#dc2743] hover:to-[#bc1888] text-white/90 hover:text-white flex items-center justify-center transition-all duration-200 hover:scale-110 shadow-sm"
                           title="Instagram">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>

                        {{-- TikTok --}}
                        <a href="https://tiktok.com" target="_blank" rel="noopener noreferrer"
                           class="w-10 h-10 rounded-full bg-white/10 hover:bg-black hover:ring-1 hover:ring-white/30 text-white/90 hover:text-white flex items-center justify-center transition-all duration-200 hover:scale-110 shadow-sm"
                           title="TikTok">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 003 15.68a6.34 6.34 0 0010.86 4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-2.04-.54z"/>
                            </svg>
                        </a>

                        {{-- Zalo --}}
                        <a href="https://zalo.me" target="_blank" rel="noopener noreferrer"
                           class="w-10 h-10 rounded-full bg-white/10 hover:bg-[#0068FF] text-white/90 hover:text-white flex items-center justify-center transition-all duration-200 hover:scale-110 shadow-sm"
                           title="Zalo">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.03 2 11c0 2.86 1.5 5.43 3.86 7.11-.19 1.48-.79 3.52-.82 3.63-.07.24.16.45.39.37.15-.05 2.59-1.4 3.99-2.24 1.15.34 2.37.52 3.63.52 5.52 0 10-4.03 10-9S17.52 2 12 2zm-3.6 12.5H5.8v-1.2l2.3-3.1H5.8V9h3.6v1.2l-2.3 3.1h2.3v1.2zm3.3 0h-1.3V9h1.3v5.5zm3.7 0h-1.2v-.6c-.3.4-.8.7-1.4.7-1.2 0-2-.9-2-2.3 0-1.4.8-2.3 2-2.3.6 0 1.1.3 1.4.7V9h1.2v5.5zm-1.2-2.2c0-.7-.4-1.2-1-1.2s-1 .5-1 1.2.4 1.2 1 1.2 1-.5 1-1.2zm4.8 2.2h-1.2v-.6c-.3.4-.8.7-1.4.7-1.2 0-2-.9-2-2.3 0-1.4.8-2.3 2-2.3.6 0 1.1.3 1.4.7V9h1.2v5.5zm-1.2-2.2c0-.7-.4-1.2-1-1.2s-1 .5-1 1.2.4 1.2 1 1.2 1-.5 1-1.2z"/>
                            </svg>
                        </a>
                    </div>
                    <div class="pt-2">
                        <p class="text-[11px] text-[#9a9490] mb-2 font-medium">Phương thức thanh toán an toàn:</p>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="inline-flex items-center gap-1 bg-white/10 text-white text-[10px] font-bold px-2.5 py-1 rounded-md border border-white/10 hover:border-white/20 transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#005BAA]"></span>
                                VNPAY
                            </span>
                            <span class="inline-flex items-center gap-1 bg-white/10 text-white text-[10px] font-bold px-2.5 py-1 rounded-md border border-white/10 hover:border-white/20 transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                COD
                            </span>
                            <span class="inline-flex items-center gap-1 bg-white/10 text-white text-[10px] font-bold px-2.5 py-1 rounded-md border border-white/10 hover:border-white/20 transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                ATM / VISA
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bottom Bar --}}
            <div class="pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-[#9a9490]">
                <p>&copy; {{ date('Y') }} SocialShop. Hệ thống thời trang &amp; Social Commerce bản quyền.</p>
                <div class="flex items-center gap-4 text-[11px]">
                    <a href="{{ route('pages.privacy') }}" class="hover:text-white transition-colors">Bảo mật</a>
                    <span>•</span>
                    <a href="{{ route('pages.terms') }}" class="hover:text-white transition-colors">Điều khoản</a>
                    <span>•</span>
                    <a href="{{ route('pages.about') }}" class="hover:text-white transition-colors">Giới thiệu</a>
                </div>
            </div>
        </div>
    </footer>

    {{-- Global Toast Notification --}}
    <div x-data="{ show: false, message: '', type: 'success' }"
         x-on:toast.window="
            show = true;
            message = $event.detail.message;
            type = $event.detail.type || 'success';
            clearTimeout(window.__toastTimer);
            window.__toastTimer = setTimeout(() => show = false, 3000);
         "
         x-show="show"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-3"
         class="fixed bottom-20 md:bottom-5 right-5 z-[100] max-w-xs sm:max-w-sm">
        <div class="flex items-center gap-3 px-4 py-3.5 rounded-xl shadow-2xl"
             :class="type === 'error' ? 'bg-red-600' : 'bg-[#3d3d3d]'">
            <svg x-show="type !== 'error'" class="w-5 h-5 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <svg x-show="type === 'error'" class="w-5 h-5 text-red-200 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span class="text-sm font-medium text-white" x-text="message"></span>
        </div>
    </div>

    {{-- Mobile Bottom Navigation Bar --}}
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-[#faf7f4]/95 backdrop-blur-md border-t border-[#efe8e3] py-2 px-3 flex items-center justify-around shadow-lg">
        <a href="{{ route('home') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('home') ? 'text-[#b8847e] font-semibold' : 'text-[#6b6560]' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span class="text-[10px]">Trang chủ</span>
        </a>
        <a href="{{ route('products.index') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('products.*') || request()->routeIs('categories.*') ? 'text-[#b8847e] font-semibold' : 'text-[#6b6560]' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
            <span class="text-[10px]">Sản phẩm</span>
        </a>
        <a href="{{ route('wishlist.index') }}" class="relative flex flex-col items-center gap-0.5 {{ request()->routeIs('wishlist.*') ? 'text-[#b8847e] font-semibold' : 'text-[#6b6560]' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <span x-data x-show="$store.wishlist.count > 0" x-cloak x-text="$store.wishlist.count"
                  class="absolute -top-1 right-2 bg-[#b8847e] text-white text-[9px] font-bold rounded-full h-3.5 min-w-[14px] flex items-center justify-center px-0.5"></span>
            <span class="text-[10px]">Yêu thích</span>
        </a>
        <a href="{{ route('cart.index') }}" class="relative flex flex-col items-center gap-0.5 {{ request()->routeIs('cart.*') ? 'text-[#b8847e] font-semibold' : 'text-[#6b6560]' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <span x-data x-show="$store.cart.count > 0" x-cloak x-text="$store.cart.count"
                  class="absolute -top-1 right-2 bg-[#b8847e] text-white text-[9px] font-bold rounded-full h-3.5 min-w-[14px] flex items-center justify-center px-0.5"></span>
            <span class="text-[10px]">Giỏ hàng</span>
        </a>
        <a href="{{ auth()->check() ? route('profile') : route('login') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('profile') || request()->routeIs('login') ? 'text-[#b8847e] font-semibold' : 'text-[#6b6560]' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span class="text-[10px]">{{ auth()->check() ? 'Tài khoản' : 'Đăng nhập' }}</span>
        </a>
    </div>

    {{-- Chatbot Widget --}}
    <livewire:chatbot-widget />

    @livewireScripts
    @stack('scripts')
    {{-- Thanh so sánh nổi: hiện khi đã chọn sản phẩm để so sánh --}}
    @unless(request()->routeIs('compare.index'))
        <div x-data="{ count: {{ count(session('compare_products', [])) }} }"
             @compare-updated.window="count = $event.detail ?? count"
             x-show="count > 0" x-cloak x-transition
             class="fixed bottom-5 left-5 z-[55]" data-testid="compare-bar">
            <a href="{{ route('compare.index') }}"
               class="flex items-center gap-2 rounded-full bg-[#3d3a37] text-white pl-4 pr-5 py-3 shadow-lg hover:bg-[#b8847e] transition-colors text-sm font-medium">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                So sánh (<span x-text="count"></span>/4)
            </a>
        </div>
    @endunless

</body>
</html>
