<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', ($settings->site_title ?: 'Your Agency Name Here') . (' — ' . ($settings->tagline ?: 'Your trusted local real estate experts')))</title>
    <meta name="description" content="@yield('meta_description', $settings->site_description ?? '')">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Favicon --}}
    @php
    $faviconUrl = !empty($settings->favicon_preset)
        ? url('/' . app('tenant')->slug . '/favicon.svg') . '?v=' . optional($settings->updated_at)->timestamp
        : null;
    @endphp
    @if($faviconUrl)
    <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    @endif

    {{-- Robots --}}
    @if(!($settings->search_engine_visibility ?? true))
    <meta name="robots" content="noindex, nofollow">
    @endif

    {{-- Google verification --}}
    @if(!empty($settings->google_site_verification))
    <meta name="google-site-verification" content="{{ $settings->google_site_verification }}">
    @endif

    {{-- Open Graph --}}
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="{{ url()->current() }}">
    <meta property="og:title"       content="@yield('title', $settings->site_title ?: 'Your Agency Name Here')">
    <meta property="og:description" content="@yield('meta_description', $settings->site_description ?? '')">
    @php
        $ogImage = trim($__env->yieldContent('og_image'))
            ?: (!empty($settings->favicon_preset)
                ? url('/' . app('tenant')->slug . '/favicon.svg') . '?v=' . optional($settings->updated_at)->timestamp
                : null);
    @endphp
    @if($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="@yield('title', $settings->site_title ?: 'Your Agency Name Here')">
    <meta name="twitter:description" content="@yield('meta_description', $settings->site_description ?? '')">
    @if($ogImage)
    <meta name="twitter:image" content="{{ $ogImage }}">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // Apply dark mode immediately to prevent flash
        (function() {
            var saved = localStorage.getItem('theme');
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (saved === 'dark' || (!saved && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @php
        $titleFont = $settings->title_font ?? 'Poppins';
        $primaryColor = $settings->primary_color ?? '#3B82F6';
        $r = hexdec(substr(ltrim($primaryColor,'#'), 0, 2));
        $g = hexdec(substr(ltrim($primaryColor,'#'), 2, 2));
        $b = hexdec(substr(ltrim($primaryColor,'#'), 4, 2));
    @endphp
    <link href="https://fonts.googleapis.com/css2?family={{ urlencode($titleFont) }}:wght@400;600;700;800&display=block" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        .footer-link:hover { color: var(--primary) !important; }
        :root {
            --primary: {{ $primaryColor }};
            --primary-rgb: {{ $r }}, {{ $g }}, {{ $b }};
        }
        .nav-active { color: var(--primary) !important; }
        .hover-primary:hover { color: var(--primary) !important; }
        .btn-primary { background-color: var(--primary); color: white; }
        .btn-primary:hover:not(:disabled) { opacity: 0.9; }

                @include('partials.dark-mode-styles')


        /* Opacity-variant white backgrounds (hero search box uses bg-white/95) */
        .dark .bg-white\/95,
        .dark .bg-white\/90,
        .dark .bg-white\/80 { background-color: rgba(30, 41, 59, 0.95) !important; }

        /* Gradient color stops (used in property image placeholder fallbacks) */
        .dark .from-gray-100 { --tw-gradient-from: #1e293b; }
        .dark .from-gray-200 { --tw-gradient-from: #334155; }
        .dark .to-gray-100   { --tw-gradient-to: #1e293b; }
        .dark .to-gray-200   { --tw-gradient-to: #334155; }

        /* Footer */
        .dark footer          { background-color: #020617 !important; color: #9ca3af !important; }
        .dark footer h4       { color: #f1f5f9 !important; }
        .dark footer .text-gray-900 { color: #f1f5f9 !important; }
        .dark footer .text-gray-500 { color: #6b7280 !important; }
        .dark footer .social-icon        { background-color: #1f2937 !important; }
        .dark footer .social-icon:hover  { background-color: #374151 !important; }
        .dark footer .text-gray-600      { color: #9ca3af !important; }
        .dark footer .border-gray-200    { border-color: #1f2937 !important; }

        /* Nav */
        .dark header.bg-white { background-color: #1e293b !important; }
        .dark nav a.text-gray-700 { color: #cbd5e1 !important; }
        /* Chatbot widget */
        .dark #chatbot-modal { background-color: #1e293b !important; color: #f1f5f9; }
        .dark #chatbot-messages { background-color: #0f172a !important; }
        .dark #chatbot-modal .bg-white { background-color: #1e293b !important; }
        .dark #chatbot-modal .border-t { border-color: #334155; }
        .dark #chatbot-input { background-color: #334155 !important; border-color: #475569 !important; color: #f1f5f9 !important; }
        .dark #chatbot-input::placeholder { color: #64748b !important; }
        #chatbot-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 20%, transparent); }
        #contact-modal input:focus, #contact-modal textarea:focus { border-color: var(--primary) !important; box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 20%, transparent) !important; outline: none; }
        .dark #chatbot-modal .text-gray-400 { color: #64748b !important; }

        /* Contact widget */
        .dark #contact-modal { background-color: #1e293b !important; color: #f1f5f9; }
        .dark #contact-modal .border-t { border-color: #334155; }
        .dark #contact-modal input,
        .dark #contact-modal textarea { background-color: #334155 !important; border-color: #475569 !important; color: #f1f5f9 !important; }
        .dark #contact-modal input::placeholder,
        .dark #contact-modal textarea::placeholder { color: #64748b !important; }
        .dark #contact-modal label { color: #cbd5e1 !important; }

        /* ===================== END DARK MODE ===================== */
        /* Hide scrollbar while preserving scroll behaviour */
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        /* Hero header scroll states — driven by JS adding .is-scrolled class */
        #tenant-hero-header                          { background-color: transparent; }
        #tenant-hero-header.is-scrolled              { background-color: #ffffff; box-shadow: 0 4px 16px rgba(0,0,0,0.1); padding-top: 0.75rem; padding-bottom: 0.75rem; }
        .dark #tenant-hero-header.is-scrolled        { background-color: #1e293b; }
        #tenant-hero-header .nav-link                { color: rgba(255,255,255,0.9); }
        #tenant-hero-header .nav-link:hover          { color: #ffffff; }
        #tenant-hero-header .theme-btn               { color: #ffffff; }
        #tenant-hero-header .hamburger-btn           { color: #ffffff; }
        #tenant-hero-header.is-scrolled .nav-link       { color: #374151; }
        #tenant-hero-header.is-scrolled .nav-link:hover { color: var(--primary); }
        #tenant-hero-header.is-scrolled .theme-btn      { color: #4b5563; }
        #tenant-hero-header.is-scrolled .hamburger-btn  { color: #374151; }
        .dark #tenant-hero-header.is-scrolled .nav-link       { color: #cbd5e1; }
        .dark #tenant-hero-header.is-scrolled .nav-link:hover { color: var(--primary); }
        .dark #tenant-hero-header.is-scrolled .theme-btn      { color: #94a3b8; }
        .dark #tenant-hero-header.is-scrolled .hamburger-btn  { color: #cbd5e1; }
        @yield('styles')
    </style>
    @yield('head')
    @stack('head')
    @if(!empty($ga) && $ga->api_key)
    <!-- Google Analytics (consent-gated) -->
    <script>
    window._gaId = '{{ $ga->api_key }}';
    function loadGA() {
        if (localStorage.getItem('cookie_consent') !== 'true') return;
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + window._gaId;
        document.head.appendChild(s);
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', window._gaId);
        window.gtag = gtag;
    }
    document.addEventListener('DOMContentLoaded', loadGA);
    </script>
    @endif
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">

@php
    $headerMode = $settings->header_mode ?? 'default';
    // Gallery and map always use the sticky default header — only homepage uses hero mode
    if (request()->routeIs('tenant.gallery') || request()->routeIs('tenant.map')) {
        $headerMode = 'default';
    }
    $headerDisplayMode = $settings->header_display_mode ?? 'both';
    $titleColorType = $settings->title_color_type ?? 'gradient';
    $gradStart = $settings->title_gradient_start ?? '#3B82F6';
    $gradVia = $settings->title_gradient_via ?? '#8B5CF6';
    $gradEnd = $settings->title_gradient_end ?? '#1E40AF';
    $solidColor = $settings->title_color_solid ?? '#3B82F6';
    $titleSize = match($settings->site_title_font_size ?? '3xl') { 'xl' => '1.25rem', '2xl' => '1.5rem', '4xl' => '2.25rem', default => '1.875rem' };
    $mobileTitleSize = match($settings->site_title_font_size ?? '3xl') { '4xl' => '1.5rem', '3xl' => '1.25rem', '2xl' => '1rem', default => '0.875rem' };
    $titleWeight = $settings->site_title_font_weight ?? '800';
    $titleTracking = $settings->site_title_letter_spacing ?? 'normal';
    $titleStyle = "font-family: '{$titleFont}', sans-serif; font-size: {$titleSize}; font-weight: {$titleWeight}; letter-spacing: " . match($titleTracking) { 'tight' => '-0.05em', 'wide' => '0.05em', default => 'normal' } . ";";
    if ($titleColorType === 'gradient') {
        $titleStyle .= " background: linear-gradient(to right, {$gradStart}, {$gradVia}, {$gradEnd}); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; color: transparent;";
    } else {
        $titleStyle .= " color: {$solidColor};";
    }
    $account = app('tenant')->slug;
    // Active page detection
    $isGallery = request()->routeIs('tenant.gallery');
    $isMap     = request()->routeIs('tenant.map');
    $isLogin   = request()->routeIs('login');
    // Preserve filters when switching between gallery and map
    $qs = ($isGallery || $isMap) && count(request()->query()) > 0
        ? '?' . http_build_query(request()->query()) : '';
    $galleryUrl = route('tenant.gallery', $account) . $qs;
    $mapUrl     = route('tenant.map',     $account) . $qs;
    // Primary RGB for active state backgrounds
    $pc = $settings->primary_color ?? '#3B82F6';
    $pr = hexdec(substr($pc, 1, 2));
    $pg = hexdec(substr($pc, 3, 2));
    $pb = hexdec(substr($pc, 5, 2));
@endphp
<style>
@media (max-width: 767px) {
    #site-title-text {
        font-size: {{ $mobileTitleSize }} !important;
        max-width: calc(100vw - 100px);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: inline-block;
    }
}
</style>

{{-- Flash Notification --}}
@include('partials.flash')

{{-- HERO MODE HEADER --}}
@unless(View::hasSection('hide_header'))
@if($headerMode === 'hero')
<header id="tenant-hero-header" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <a href="{{ route('tenant.home', $account) }}" class="flex items-center space-x-3" id="tenant-logo" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3))">
                @if(!empty($settings->favicon_preset) && $headerDisplayMode !== 'text_only')
                    <img src="{{ url('/' . $account . '/favicon.svg') . '?v=' . optional($settings->updated_at)->timestamp }}" alt="{{ $settings->site_title ?: $tenant->name }}" class="h-8 w-8 object-contain rounded-lg">
                @endif
                @if(in_array($headerDisplayMode, ['text_only', 'favicon_text', 'both']))
                    <span id="site-title-text" style="{{ $titleStyle }}">{{ $settings->site_title ?: 'Your Agency Name Here' }}</span>
                @endif
            </a>
            <nav id="tenant-nav" class="hidden md:flex items-center space-x-6 transition-all duration-300" style="opacity:0;pointer-events:none">
                <a href="{{ $galleryUrl }}" class="nav-link font-medium transition-colors hover-primary" @if($isGallery) style="color: var(--primary);" @endif>Gallery</a>
                <a href="{{ $mapUrl }}" class="nav-link font-medium transition-colors hover-primary" @if($isMap) style="color: var(--primary);" @endif>Map</a>
                <a href="{{ route('login') }}" class="nav-link font-medium transition-colors hover-primary" @if($isLogin) style="color: var(--primary);" @endif>Login</a>
                <button id="theme-toggle-btn" onclick="themeToggle()" class="theme-btn p-1 rounded-full">
                    <svg id="theme-icon-moon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg id="theme-icon-sun" class="w-5 h-5" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
            </nav>
            <button onclick="tenantNavToggle()" id="tenant-hamburger" class="hamburger-btn md:hidden p-2 rounded transition-all duration-300" style="opacity:0;pointer-events:none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>
    <div id="tenant-mobile-menu" style="display:none"
         class="md:hidden border-t bg-white shadow-lg">
        <nav class="px-4 py-3 space-y-1">
            <a href="{{ $galleryUrl }}" class="flex items-center px-3 py-2 rounded-lg font-medium @if($isGallery) text-white @else text-gray-700 hover:bg-gray-100 @endif" @if($isGallery) style="background-color: rgba({{ $pr }},{{ $pg }},{{ $pb }},0.1); color: var(--primary);" @endif>
                <svg class="w-5 h-5 mr-3 @if($isGallery) @else text-gray-500 @endif" style="@if($isGallery) color: var(--primary); @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Gallery
            </a>
            <a href="{{ $mapUrl }}" class="flex items-center px-3 py-2 rounded-lg font-medium @if($isMap) @else text-gray-700 hover:bg-gray-100 @endif" @if($isMap) style="background-color: rgba({{ $pr }},{{ $pg }},{{ $pb }},0.1); color: var(--primary);" @endif>
                <svg class="w-5 h-5 mr-3 @if($isMap) @else text-gray-500 @endif" style="@if($isMap) color: var(--primary); @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                Map
            </a>
            <div class="border-t my-2"></div>
            <a href="{{ route('tenant.contact', $account) }}" class="flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
                <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Contact Us
            </a>
            @if($settings->chatbot_enabled ?? false)
            <a href="{{ route('tenant.chat', $account) }}" class="flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
                <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Chat Assistant
            </a>
            @endif
            <div class="border-t my-2"></div>
            <a href="{{ route('login') }}" class="flex items-center px-3 py-2 rounded-lg font-medium @if($isLogin) @else text-gray-700 hover:bg-gray-100 @endif" @if($isLogin) style="background-color: rgba({{ $pr }},{{ $pg }},{{ $pb }},0.1); color: var(--primary);" @endif>
                <svg class="w-5 h-5 mr-3 @if($isLogin) @else text-gray-500 @endif" style="@if($isLogin) color: var(--primary); @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                Login
            </a>
        </nav>
    </div>
</header>
<div class="h-0"></div>

@else
{{-- DEFAULT MODE HEADER --}}
<header id="tenant-default-header" class="bg-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between py-4">
            <a href="{{ route('tenant.home', $account) }}" class="flex items-center space-x-3 drop-shadow-lg hover:opacity-80 transition-opacity">
                @if(!empty($settings->favicon_preset) && $headerDisplayMode !== 'text_only')
                    <img src="{{ url('/' . $account . '/favicon.svg') . '?v=' . optional($settings->updated_at)->timestamp }}" alt="{{ $settings->site_title ?: $tenant->name }}" class="h-8 w-8 object-contain rounded-lg">
                @endif
                @if(in_array($headerDisplayMode, ['text_only', 'favicon_text', 'both']))
                    <span id="site-title-text" style="{{ $titleStyle }}">{{ $settings->site_title ?: 'Your Agency Name Here' }}</span>
                @endif
            </a>
            <nav class="hidden md:flex items-center space-x-6">
                <a href="{{ $galleryUrl }}" class="font-medium transition-colors hover-primary @if(!$isGallery) text-gray-700 @endif" @if($isGallery) style="color: var(--primary);" @endif>Gallery</a>
                <a href="{{ $mapUrl }}" class="font-medium transition-colors hover-primary @if(!$isMap) text-gray-700 @endif" @if($isMap) style="color: var(--primary);" @endif>Map</a>
                <a href="{{ route('login') }}" class="font-medium transition-colors hover-primary @if(!$isLogin) text-gray-700 @endif" @if($isLogin) style="color: var(--primary);" @endif>Login</a>
                <button onclick="themeToggle()" class="theme-btn p-1 rounded-full text-gray-600 hover:text-gray-900">
                    <svg id="default-theme-icon-moon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg id="default-theme-icon-sun" class="w-5 h-5" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
            </nav>
            <button onclick="tenantNavToggle()" id="tenant-default-hamburger" class="md:hidden p-2 rounded text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>
    <div id="tenant-default-mobile-menu" style="display:none"
         class="md:hidden border-t bg-white">
        <nav class="px-4 py-3 space-y-1">
            <a href="{{ $galleryUrl }}" onclick="tenantNavClose()" class="flex items-center px-3 py-2 rounded-lg font-medium @if($isGallery) @else text-gray-700 hover:bg-gray-100 @endif" @if($isGallery) style="background-color: rgba({{ $pr }},{{ $pg }},{{ $pb }},0.1); color: var(--primary);" @endif>
                <svg class="w-5 h-5 mr-3 @if($isGallery) @else text-gray-500 @endif" style="@if($isGallery) color: var(--primary); @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Gallery
            </a>
            <a href="{{ $mapUrl }}" class="flex items-center px-3 py-2 rounded-lg font-medium @if($isMap) @else text-gray-700 hover:bg-gray-100 @endif" @if($isMap) style="background-color: rgba({{ $pr }},{{ $pg }},{{ $pb }},0.1); color: var(--primary);" @endif>
                <svg class="w-5 h-5 mr-3 @if($isMap) @else text-gray-500 @endif" style="@if($isMap) color: var(--primary); @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                Map
            </a>
            <div class="border-t my-2"></div>
            <a href="{{ route('tenant.contact', $account) }}" class="flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
                <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Contact Us
            </a>
            @if($settings->chatbot_enabled ?? false)
            <a href="{{ route('tenant.chat', $account) }}" class="flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
                <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Chat Assistant
            </a>
            @endif
            <div class="border-t my-2"></div>
            <button onclick="themeToggle()" class="flex items-center w-full px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
                <svg id="default-mobile-theme-icon-moon" class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg id="default-mobile-theme-icon-sun" class="w-5 h-5 mr-3 text-gray-500" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span id="default-mobile-theme-label">Dark Mode</span>
            </button>
            <div class="border-t my-2"></div>
            <a href="{{ route('login') }}" class="flex items-center px-3 py-2 rounded-lg font-medium @if($isLogin) @else text-gray-700 hover:bg-gray-100 @endif" @if($isLogin) style="background-color: rgba({{ $pr }},{{ $pg }},{{ $pb }},0.1); color: var(--primary);" @endif>
                <svg class="w-5 h-5 mr-3 @if($isLogin) @else text-gray-500 @endif" style="@if($isLogin) color: var(--primary); @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                Login
            </a>
        </nav>
    </div>
</header>
@endif
@endunless

<main class="flex-1">
    @yield('content')
</main>

{{-- FOOTER --}}
{{-- Cookie Consent Banner --}}
<div id="cookie-banner" style="display:none">
    <div class="cookie-banner-inner">
        <div class="cookie-banner-icon">🍪</div>
        <div class="cookie-banner-text">
            <strong>We use cookies</strong>
            <span>We use cookies to improve your experience and analyze traffic. See our <a href="{{ route('tenant.privacy', $account) }}">Privacy Policy</a>.</span>
        </div>
        <div class="cookie-banner-actions">
            <button onclick="cookieConsent('false')" class="cookie-btn-decline">Decline</button>
            <button onclick="cookieConsent('true')" class="cookie-btn-accept">Accept All</button>
        </div>
    </div>
</div>
<style>
#cookie-banner {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 9999;
    padding: 0 1rem 1rem;
    pointer-events: none;
}
.cookie-banner-inner {
    max-width: 860px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-radius: 1rem 1rem 0 0;
    box-shadow: 0 -4px 24px rgba(0,0,0,0.18);
    pointer-events: all;
    flex-wrap: wrap;
    /* Light mode */
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-bottom: none;
    color: #374151;
}
.dark .cookie-banner-inner {
    background: #1e293b;
    border-color: #334155;
    color: #cbd5e1;
}
.cookie-banner-icon { font-size: 1.5rem; flex-shrink: 0; }
.cookie-banner-text {
    flex: 1;
    min-width: 200px;
    font-size: 0.875rem;
    line-height: 1.5;
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}
.cookie-banner-text strong {
    font-weight: 600;
    color: #111827;
}
.dark .cookie-banner-text strong { color: #f1f5f9; }
.cookie-banner-text span { opacity: 0.8; }
.cookie-banner-text a {
    text-decoration: underline;
    color: var(--primary);
}
.cookie-banner-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}
.cookie-btn-decline {
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
    font-weight: 500;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s;
    background: transparent;
    border: 1.5px solid #d1d5db;
    color: #6b7280;
}
.cookie-btn-decline:hover { border-color: #9ca3af; color: #374151; }
.dark .cookie-btn-decline { border-color: #475569; color: #94a3b8; }
.dark .cookie-btn-decline:hover { border-color: #64748b; color: #cbd5e1; }
.cookie-btn-accept {
    padding: 0.5rem 1.25rem;
    font-size: 0.8125rem;
    font-weight: 600;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: opacity 0.15s;
    border: none;
    color: #fff;
    background-color: var(--primary);
}
.cookie-btn-accept:hover { opacity: 0.88; }
</style>
<script>
(function() {
    var consent = localStorage.getItem('cookie_consent');
    if (!consent) {
        var banner = document.getElementById('cookie-banner');
        if (banner) { banner.style.display = ''; }
    }
    document.addEventListener('DOMContentLoaded', updateCookiePrefsLink);
})();
function cookieConsent(val) {
    localStorage.setItem('cookie_consent', val);
    var banner = document.getElementById('cookie-banner');
    if (banner) banner.style.display = 'none';
    if (val === 'true' && typeof loadGA === 'function') loadGA();
    updateCookiePrefsLink();
}
function openCookiePrefs() {
    var banner = document.getElementById('cookie-banner');
    if (banner) { banner.style.display = ''; }
    banner.scrollIntoView({ behavior: 'smooth', block: 'end' });
}
function updateCookiePrefsLink() {
    var link = document.getElementById('cookie-prefs-link');
    if (!link) return;
    var consent = localStorage.getItem('cookie_consent');
    if (consent === 'true') link.textContent = 'Cookie Preferences ✓';
    else if (consent === 'false') link.textContent = 'Cookie Preferences ✕';
    else link.textContent = 'Cookie Preferences';
}
</script>

@if(View::hasSection('show_footer'))
<footer class="bg-gray-100 text-gray-600 mt-auto">
    <div class="max-w-7xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-8 mb-10">
            {{-- Brand --}}
            <div class="md:col-span-2">
                <div class="flex items-center space-x-3 mb-4">
                    @if(!empty($settings->favicon_preset))
                        <img src="{{ url('/' . $account . '/favicon.svg') . '?v=' . optional($settings->updated_at)->timestamp }}" alt="{{ $settings->site_title ?: $tenant->name }}" class="h-8 w-8 object-contain rounded-lg flex-shrink-0">
                    @elseif($settings->logo_image)
                        <img src="{{ asset('storage/' . $settings->logo_image) }}" alt="Logo" class="h-10 w-auto flex-shrink-0">
                    @endif
                    <div>
                        <span class="text-lg font-bold text-gray-900 block">{{ $settings->site_title ?: 'Your Agency Name Here' }}</span>
                        <span class="text-gray-500 text-sm">{{ $settings->tagline ?: 'Your trusted local real estate experts' }}</span>
                    </div>
                </div>
                @php
                    $footerBadge = '';
                    if (!empty($settings->brokerage_name)) $footerBadge .= $settings->brokerage_name;
                    if (!empty($settings->brokerage_name) && !empty($settings->license_number)) $footerBadge .= ' · ';
                    if (!empty($settings->license_number)) $footerBadge .= 'Lic. #' . $settings->license_number;
                @endphp
                <p class="text-gray-500 text-xs mb-4">@if($footerBadge)<span class="text-gray-500">{{ $footerBadge }}</span> &middot; @endif Information deemed reliable but not guaranteed. Listing data is provided for consumers' personal, non-commercial use and may not be used for any purpose other than to identify prospective properties. Equal Housing Opportunity.</p>
                <div class="flex space-x-3">
                    @foreach([['url' => $settings->social_facebook ?? null, 'label' => 'Facebook', 'path' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z'], ['url' => $settings->social_instagram ?? null, 'label' => 'Instagram', 'path' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z'], ['url' => $settings->social_twitter ?? null, 'label' => 'Twitter', 'path' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z'], ['url' => $settings->social_linkedin ?? null, 'label' => 'LinkedIn', 'path' => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z'], ['url' => $settings->social_youtube ?? null, 'label' => 'YouTube', 'path' => 'M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z']] as $social)
                        @if($social['url'])
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['label'] }}" class="w-9 h-9 bg-gray-200 hover:bg-gray-300 rounded-full flex items-center justify-center transition-colors social-icon">
                            <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="{{ $social['path'] }}"/></svg>
                        </a>
                        @endif
                    @endforeach
                </div>
            </div>
            {{-- Quick Links --}}
            <div>
                <h4 class="text-gray-900 font-semibold text-sm mb-4">Quick Links</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('login') }}" class="footer-link transition-colors">Login</a></li>
                    <li><a href="{{ route('tenant.gallery', $account) }}" class="footer-link transition-colors">Properties</a></li>
                    <li><a href="{{ route('tenant.map', $account) }}" class="footer-link transition-colors">Map Search</a></li>
                </ul>
            </div>
            {{-- Legal --}}
            <div>
                <h4 class="text-gray-900 font-semibold text-sm mb-4">Legal</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('tenant.privacy', $account) }}" class="footer-link transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('tenant.terms', $account) }}" class="footer-link transition-colors">Terms of Service</a></li>
                    <li><button onclick="openCookiePrefs()" class="footer-link transition-colors text-left" id="cookie-prefs-link">Cookie Preferences</button></li>
                </ul>
            </div>
            {{-- Affiliates --}}
            <div>
                <h4 class="text-gray-900 font-semibold text-sm mb-4">Affiliates</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="https://punchlistify.com" target="_blank" rel="noopener" class="footer-link transition-colors">Punchlistify</a></li>
                    <li><a href="https://punchlistlabs.com" target="_blank" rel="noopener" class="footer-link transition-colors">Punchlist Labs</a></li>
                    <li><a href="https://routepilot.pro" target="_blank" rel="noopener" class="footer-link transition-colors">RoutePilot</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-300 pt-6 flex flex-col md:flex-row items-center justify-between gap-3 text-xs text-gray-500">
            <p>&copy; {{ date('Y') }} <span style="color: var(--primary)">{{ $settings->site_title ?: 'Your Agency Name Here' }}</span>. All rights reserved.</p>
            <p>Powered by <a href="https://www.busyrealtor.com" class="transition-colors" style="color: var(--primary)">BusyRealtor</a></p>
        </div>
    </div>
</footer>
@endif

{{-- Chatbot Widget --}}
@unless(View::hasSection('hide_chatbot'))
@if($settings->chatbot_enabled ?? false)
@php $chatbotApiUrl = route('tenant.api.chatbot', $account); @endphp
<div class="hidden md:block"><div id="chatbot-root"></div></div>
<script>
(function() {
    const API_URL = '{{ $chatbotApiUrl }}';
    const CSRF = () => document.querySelector('meta[name=csrf-token]')?.content || '';
    const STORAGE_KEY = 'chatbot_position';
    let state = { isOpen: false, sessionId: null, isLoading: false, messages: [], isDragging: false, position: null };

    document.addEventListener('DOMContentLoaded', init);

    function init() { loadPosition(); createWidget(); loadConversation(); }

    function loadPosition() {
        try { const s = localStorage.getItem(STORAGE_KEY); if (s) state.position = JSON.parse(s); } catch(e) {}
        if (!state.position) state.position = { right: 24, bottom: 24 };
    }
    function savePosition() { localStorage.setItem(STORAGE_KEY, JSON.stringify(state.position)); }

    function esc(t) {
        return String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').split('\n').join('<br>');
    }

    function createWidget() {
        document.getElementById('chatbot-root').innerHTML = `
<button id="chatbot-btn" class="fixed w-14 h-14 rounded-full shadow-xl flex items-center justify-center text-white z-50 cursor-grab active:cursor-grabbing select-none" style="background-color:var(--primary);right:24px;bottom:24px">
    <svg id="chatbot-icon-chat" class="w-6 h-6 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
    </svg>
    <svg id="chatbot-icon-x" class="w-6 h-6 pointer-events-none hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
    </svg>
</button>
<div id="chatbot-modal" class="fixed w-96 bg-white rounded-xl shadow-2xl z-50 hidden" style="height:480px;max-height:calc(100vh - 80px);max-width:calc(100vw - 2rem);display:none;flex-direction:column">
    <div class="flex items-center justify-between p-4 border-b rounded-t-xl shrink-0" style="background-color:var(--primary)">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            </div>
            <div class="text-white">
                <div class="font-semibold text-sm">Chat Assistant</div>
                <div class="text-xs opacity-80">Online</div>
            </div>
        </div>
        <button id="chatbot-close" class="text-white/80 hover:text-white hover:bg-white/20 rounded p-1 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div id="chatbot-messages" class="flex-1 overflow-y-auto p-4 space-y-3" style="background:#f7f8fa"></div>
    <div class="p-3 border-t bg-white rounded-b-xl shrink-0">
        <form id="chatbot-form" class="flex gap-2">
            <input type="text" id="chatbot-input" placeholder="Type a message..." autocomplete="off"
                   class="flex-1 border border-gray-200 rounded-full px-4 py-2 text-sm focus:outline-none">
            <button type="submit" id="chatbot-send" class="px-4 py-2 rounded-full text-white text-sm font-medium shrink-0" style="background-color:var(--primary)">Send</button>
        </form>
        <div id="chatbot-typing" class="hidden text-xs text-gray-400 mt-2 px-1">Typing...</div>
        <p class="text-center text-gray-400 mt-1.5 px-1" style="font-size:0.65rem;line-height:1.3">AI assistant &middot; Not legal, financial, or real estate advice</p>
    </div>
</div>`;

        // Keep chatbot-messages background in sync with dark mode
        const messagesEl = document.getElementById('chatbot-messages');
        function syncChatDark() {
            messagesEl.style.background = document.documentElement.classList.contains('dark') ? '#0f172a' : '#f7f8fa';
        }
        syncChatDark();
        new MutationObserver(syncChatDark).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        const btn = document.getElementById('chatbot-btn');
        const modal = document.getElementById('chatbot-modal');
        applyPosition(btn);
        makeDraggable(btn, () => { if (state.isOpen) positionModal(btn, modal); });
        btn.addEventListener('click', () => { if (!state.isDragging) toggle(); });
        document.getElementById('chatbot-close').addEventListener('click', close);
        document.getElementById('chatbot-form').addEventListener('submit', handleSubmit);
    }

    function applyPosition(el) {
        el.style.left   = state.position.left   !== undefined ? state.position.left   + 'px' : 'auto';
        el.style.right  = state.position.right  !== undefined ? state.position.right  + 'px' : 'auto';
        el.style.top    = state.position.top    !== undefined ? state.position.top    + 'px' : 'auto';
        el.style.bottom = state.position.bottom !== undefined ? state.position.bottom + 'px' : 'auto';
    }

    function makeDraggable(el, onMove) {
        let startX, startY, startLeft, startTop, moved;
        el.addEventListener('mousedown', ds);
        el.addEventListener('touchstart', ds, { passive: false });
        function ds(e) {
            moved = false; state.isDragging = false;
            const t = e.touches ? e.touches[0] : e;
            startX = t.clientX; startY = t.clientY;
            const r = el.getBoundingClientRect(); startLeft = r.left; startTop = r.top;
            document.addEventListener('mousemove', drag);
            document.addEventListener('touchmove', drag, { passive: false });
            document.addEventListener('mouseup', de); document.addEventListener('touchend', de);
        }
        function drag(e) {
            e.preventDefault();
            const t = e.touches ? e.touches[0] : e;
            const dx = t.clientX - startX, dy = t.clientY - startY;
            if (Math.abs(dx) > 5 || Math.abs(dy) > 5) { moved = true; state.isDragging = true; }
            if (!moved) return;
            let nl = Math.max(0, Math.min(startLeft + dx, window.innerWidth  - el.offsetWidth));
            let nt = Math.max(0, Math.min(startTop  + dy, window.innerHeight - el.offsetHeight));
            const cx = nl + el.offsetWidth/2, cy = nt + el.offsetHeight/2;
            state.position = {};
            if (cx < window.innerWidth/2)  state.position.left   = nl; else state.position.right  = window.innerWidth  - nl - el.offsetWidth;
            if (cy < window.innerHeight/2) state.position.top    = nt; else state.position.bottom = window.innerHeight - nt - el.offsetHeight;
            applyPosition(el); if (onMove) onMove();
        }
        function de() {
            document.removeEventListener('mousemove', drag); document.removeEventListener('touchmove', drag);
            document.removeEventListener('mouseup', de);    document.removeEventListener('touchend', de);
            if (moved) { savePosition(); setTimeout(() => { state.isDragging = false; }, 10); }
        }
    }

    function positionModal(btn, modal) {
        const r = btn.getBoundingClientRect(), vw = window.innerWidth, vh = window.innerHeight;
        const cx = r.left + r.width/2, cy = r.top + r.height/2;
        modal.style.left = modal.style.right = modal.style.top = modal.style.bottom = 'auto';
        if (cx > vw/2) modal.style.right = (vw - r.left + 8) + 'px'; else modal.style.left = (r.right + 8) + 'px';
        if (cy > vh/2) modal.style.bottom = (vh - r.top  + 8) + 'px'; else modal.style.top  = (r.bottom + 8) + 'px';
        requestAnimationFrame(() => {
            const mr = modal.getBoundingClientRect();
            if (mr.left < 8) modal.style.left = '8px';
            if (mr.right  > vw - 8) { modal.style.left = 'auto'; modal.style.right  = '8px'; }
            if (mr.top  < 8) modal.style.top  = '8px';
            if (mr.bottom > vh - 8) { modal.style.top  = 'auto'; modal.style.bottom = '8px'; }
        });
    }

    function toggle() {
        state.isOpen = !state.isOpen;
        const btn = document.getElementById('chatbot-btn');
        const modal = document.getElementById('chatbot-modal');
        document.getElementById('chatbot-icon-chat').classList.toggle('hidden', state.isOpen);
        document.getElementById('chatbot-icon-x').classList.toggle('hidden', !state.isOpen);
        if (state.isOpen) {
            modal.style.display = 'flex';
            positionModal(btn, modal);
            if (state.messages.length === 0) addMessage("Hi! I'm your AI real estate assistant. How can I help you today?", 'bot');
            document.getElementById('chatbot-input').focus();
        } else {
            modal.style.display = 'none';
        }
    }

    function close() {
        state.isOpen = false;
        document.getElementById('chatbot-icon-chat').classList.remove('hidden');
        document.getElementById('chatbot-icon-x').classList.add('hidden');
        document.getElementById('chatbot-modal').style.display = 'none';
    }

    function addMessage(text, sender) {
        const container = document.getElementById('chatbot-messages');
        const div = document.createElement('div');
        div.className = sender === 'user' ? 'flex justify-end' : 'flex justify-start';
        div.innerHTML = '<div class="max-w-[85%] px-3 py-2 rounded-lg text-sm ' +
            (sender === 'user' ? 'text-white rounded-br-none' : 'bg-white text-gray-800 rounded-bl-none shadow-sm') +
            '" ' + (sender === 'user' ? 'style="background-color:var(--primary)"' : '') + '>' + esc(text) + '</div>';
        container.appendChild(div);
        state.messages.push({ text, sender, ts: Date.now() });
        saveConversation();
        setTimeout(() => { container.scrollTop = container.scrollHeight; }, 10);
    }

    function showTyping(show) {
        document.getElementById('chatbot-typing').classList.toggle('hidden', !show);
        document.getElementById('chatbot-send').disabled = show;
        document.getElementById('chatbot-input').disabled = show;
    }

    async function handleSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('chatbot-input');
        const msg = input.value.trim();
        if (!msg || state.isLoading) return;
        input.value = '';
        addMessage(msg, 'user');
        showTyping(true); state.isLoading = true;
        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF() },
                body: JSON.stringify({ message: msg, session_id: state.sessionId })
            });
            const data = await res.json();
            if (data.session_id) state.sessionId = data.session_id;
            addMessage(data.reply || 'Sorry, something went wrong.', 'bot');
        } catch(err) {
            addMessage("Sorry, I'm having trouble connecting. Please try again.", 'bot');
        } finally {
            state.isLoading = false; showTyping(false);
            document.getElementById('chatbot-input').focus();
        }
    }

    function saveConversation() {
        sessionStorage.setItem('chatbot_conv', JSON.stringify({ id: state.sessionId, msgs: state.messages }));
    }
    function loadConversation() {
        try {
            const data = JSON.parse(sessionStorage.getItem('chatbot_conv') || 'null');
            if (!data) return;
            state.sessionId = data.id;
            state.messages = data.msgs || [];
            const container = document.getElementById('chatbot-messages');
            state.messages.forEach(m => {
                const div = document.createElement('div');
                div.className = m.sender === 'user' ? 'flex justify-end' : 'flex justify-start';
                div.innerHTML = '<div class="max-w-[85%] px-3 py-2 rounded-lg text-sm ' +
                    (m.sender === 'user' ? 'text-white rounded-br-none' : 'bg-white text-gray-800 rounded-bl-none shadow-sm') +
                    '" ' + (m.sender === 'user' ? 'style="background-color:var(--primary)"' : '') + '>' + esc(m.text) + '</div>';
                container.appendChild(div);
            });
        } catch(e) {}
    }

    window.chatbotWidget = { open: toggle, close };
})();
</script>
@endif
@endunless

{{-- Contact Modal Widget (always shown on public pages) --}}
@unless(View::hasSection('hide_contact_widget'))
@php $contactApiUrl = route('tenant.api.contact', $account); @endphp
<div class="hidden md:block"><div id="contact-widget-root"></div></div>
<script>
(function() {
    const CONTACT_URL = '{{ $contactApiUrl ?? "" }}';
    const STORAGE_KEY = 'contact_position';
    let state = { isOpen: false, propertyId: null, isDragging: false, position: null };

    document.addEventListener('DOMContentLoaded', init);

    function init() { loadPosition(); createWidget(); setupPropertyContext(); }

    function loadPosition() {
        try { const s = localStorage.getItem(STORAGE_KEY); if (s) state.position = JSON.parse(s); } catch(e) {}
        if (!state.position) state.position = { left: 16, bottom: 16 };
    }
    function savePosition() { localStorage.setItem(STORAGE_KEY, JSON.stringify(state.position)); }

    function createWidget() {
        const root = document.getElementById('contact-widget-root');
        root.innerHTML = `
<button id="contact-btn" class="fixed w-14 h-14 rounded-full shadow-xl flex items-center justify-center text-white z-50 cursor-grab active:cursor-grabbing select-none" style="background-color:var(--primary);left:16px;bottom:16px">
    <svg id="contact-icon-mail" class="w-6 h-6 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
    </svg>
    <svg id="contact-icon-x" class="w-6 h-6 pointer-events-none hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
    </svg>
</button>
<div id="contact-modal" class="fixed w-96 bg-white rounded-xl shadow-2xl z-50 hidden" style="max-width:calc(100vw - 2rem)">
    <div class="flex items-center justify-between p-4 border-b rounded-t-xl" style="background-color:var(--primary)">
        <div class="text-white">
            <div class="font-semibold">Contact Agent</div>
            <div class="text-xs opacity-90">We'll respond within 24 hours</div>
        </div>
        <button id="contact-close" class="text-white hover:bg-white/20 rounded p-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="p-4">
        <form id="contact-form" class="space-y-3">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Your Name *</label>
            <input type="text" id="contact-name" name="name" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-0"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
            <input type="email" id="contact-email" name="email" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
            <input type="tel" id="contact-phone" name="phone" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
            <textarea id="contact-message" name="message" rows="3" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none resize-none"></textarea></div>
            <input type="hidden" id="contact-property-id" name="property_id" value="">
            <p class="text-xs text-gray-400" style="margin-top:-4px">By providing your phone number, you consent to receive calls or texts regarding your inquiry. <a href="" id="widget-privacy-link" class="underline hover:text-gray-800" target="_blank">Privacy Policy</a>. <input type="checkbox" id="widget-consent" name="consent" class="w-3.5 h-3.5 rounded border-gray-400" style="accent-color:var(--primary);vertical-align:-3px"> <label for="widget-consent" class="cursor-pointer underline">I agree</label> <span class="text-red-500">*</span></p>

            <div id="contact-error" class="hidden text-red-600 text-sm"></div>
            <div id="contact-success" class="hidden text-green-600 text-sm font-medium"></div>
            <button type="submit" id="contact-submit" disabled class="w-full py-2.5 rounded-lg text-white font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed transition-opacity" style="background-color:var(--primary)">Send Message</button>
        </form>
    </div>
</div>`;
        const btn = document.getElementById('contact-btn');
        const modal = document.getElementById('contact-modal');
        applyPosition(btn);
        makeDraggable(btn, () => { if (state.isOpen) positionModal(btn, modal); });
        btn.addEventListener('click', () => { if (!state.isDragging) toggle(); });
        document.getElementById('contact-close').addEventListener('click', close);
        document.getElementById('contact-form').addEventListener('submit', handleSubmit);
        wireConsentCheckbox();
        var pl = document.getElementById('widget-privacy-link');
        if (pl) pl.href = '{{ route("tenant.privacy", $account) }}';
    }

    function applyPosition(el) {
        el.style.left   = state.position.left   !== undefined ? state.position.left   + 'px' : 'auto';
        el.style.right  = state.position.right  !== undefined ? state.position.right  + 'px' : 'auto';
        el.style.top    = state.position.top    !== undefined ? state.position.top    + 'px' : 'auto';
        el.style.bottom = state.position.bottom !== undefined ? state.position.bottom + 'px' : 'auto';
    }

    function makeDraggable(el, onMove) {
        let startX, startY, startLeft, startTop, moved;
        el.addEventListener('mousedown', ds);
        el.addEventListener('touchstart', ds, { passive: false });
        function ds(e) {
            moved = false; state.isDragging = false;
            const t = e.touches ? e.touches[0] : e;
            startX = t.clientX; startY = t.clientY;
            const r = el.getBoundingClientRect(); startLeft = r.left; startTop = r.top;
            document.addEventListener('mousemove', drag);
            document.addEventListener('touchmove', drag, { passive: false });
            document.addEventListener('mouseup', de);
            document.addEventListener('touchend', de);
        }
        function drag(e) {
            e.preventDefault();
            const t = e.touches ? e.touches[0] : e;
            const dx = t.clientX - startX, dy = t.clientY - startY;
            if (Math.abs(dx) > 5 || Math.abs(dy) > 5) { moved = true; state.isDragging = true; }
            if (!moved) return;
            let nl = Math.max(0, Math.min(startLeft + dx, window.innerWidth  - el.offsetWidth));
            let nt = Math.max(0, Math.min(startTop  + dy, window.innerHeight - el.offsetHeight));
            const mx = window.innerWidth / 2, my = window.innerHeight / 2;
            const cx = nl + el.offsetWidth / 2, cy = nt + el.offsetHeight / 2;
            state.position = {};
            if (cx < mx) state.position.left = nl; else state.position.right  = window.innerWidth  - nl - el.offsetWidth;
            if (cy < my) state.position.top  = nt; else state.position.bottom = window.innerHeight - nt - el.offsetHeight;
            applyPosition(el); if (onMove) onMove();
        }
        function de() {
            document.removeEventListener('mousemove', drag); document.removeEventListener('touchmove', drag);
            document.removeEventListener('mouseup', de);    document.removeEventListener('touchend', de);
            if (moved) { savePosition(); setTimeout(() => { state.isDragging = false; }, 10); }
        }
    }

    function positionModal(btn, modal) {
        const r = btn.getBoundingClientRect(), vw = window.innerWidth, vh = window.innerHeight;
        const cx = r.left + r.width / 2, cy = r.top + r.height / 2;
        modal.style.left = modal.style.right = modal.style.top = modal.style.bottom = 'auto';
        if (cx > vw / 2) modal.style.right = (vw - r.left + 8) + 'px'; else modal.style.left = (r.right + 8) + 'px';
        if (cy > vh / 2) modal.style.bottom = (vh - r.top  + 8) + 'px'; else modal.style.top  = (r.bottom + 8) + 'px';
        requestAnimationFrame(() => {
            const mr = modal.getBoundingClientRect();
            if (mr.left < 8) modal.style.left = '8px';
            if (mr.right  > vw - 8) { modal.style.left = 'auto'; modal.style.right  = '8px'; }
            if (mr.top  < 8) modal.style.top  = '8px';
            if (mr.bottom > vh - 8) { modal.style.top  = 'auto'; modal.style.bottom = '8px'; }
        });
    }

    function toggle() {
        state.isOpen = !state.isOpen;
        const btn = document.getElementById('contact-btn');
        const modal = document.getElementById('contact-modal');
        document.getElementById('contact-icon-mail').classList.toggle('hidden', state.isOpen);
        document.getElementById('contact-icon-x').classList.toggle('hidden', !state.isOpen);
        if (state.isOpen) { modal.classList.remove('hidden'); positionModal(btn, modal); document.getElementById('contact-name').focus(); }
        else modal.classList.add('hidden');
    }
    function close() {
        state.isOpen = false;
        document.getElementById('contact-icon-mail').classList.remove('hidden');
        document.getElementById('contact-icon-x').classList.add('hidden');
        document.getElementById('contact-modal').classList.add('hidden');
    }

    function setupPropertyContext() {
        const m = location.pathname.match(/\/property\/(\d+)/);
        if (m) {
            state.propertyId = m[1];
            document.getElementById('contact-property-id').value = m[1];
            const addr = document.querySelector('[data-property-address]')?.textContent;
            if (addr) document.getElementById('contact-message').value =
                `Hi, I'm interested in the property at ${addr}. I'd like to schedule a viewing.`;
        }
    }

    function wireConsentCheckbox() {
        const box = document.getElementById('widget-consent');
        const btn = document.getElementById('contact-submit');
        if (!box || !btn) return;
        box.addEventListener('change', () => { btn.disabled = !box.checked; });
    }

    async function handleSubmit(e) {
        e.preventDefault();
        const consentBox = document.getElementById('widget-consent');
        if (consentBox && !consentBox.checked) return;
        const submitBtn = document.getElementById('contact-submit');
        const errDiv = document.getElementById('contact-error');
        const okDiv  = document.getElementById('contact-success');
        errDiv.classList.add('hidden'); okDiv.classList.add('hidden');
        submitBtn.disabled = true; submitBtn.textContent = 'Sending...';
        const fd = new FormData(e.target);
        try {
            const res = await fetch(CONTACT_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
                body: JSON.stringify({ name: fd.get('name'), email: fd.get('email'), phone: fd.get('phone'), message: fd.get('message'), property_id: fd.get('property_id') || null })
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Failed to send');
            okDiv.textContent = "Message sent! We'll be in touch soon.";
            okDiv.classList.remove('hidden');
            e.target.reset();
            setTimeout(() => { close(); okDiv.classList.add('hidden'); }, 3000);
        } catch(err) {
            errDiv.textContent = err.message;
            errDiv.classList.remove('hidden');
        } finally { submitBtn.disabled = false; submitBtn.textContent = 'Send Message'; }
    }

    window.contactWidget = { open: toggle, close };
})();
</script>
@endunless

<script>
// Mobile nav — pure JS (no Alpine dependency)
function tenantNavToggle() {
    var heroMenu    = document.getElementById('tenant-mobile-menu');
    var defaultMenu = document.getElementById('tenant-default-mobile-menu');
    var menu = heroMenu || defaultMenu;
    if (!menu) return;
    var opening = menu.style.display === 'none' || menu.style.display === '';
    menu.style.display = opening ? 'block' : 'none';
}
function tenantNavClose() {
    var heroMenu    = document.getElementById('tenant-mobile-menu');
    var defaultMenu = document.getElementById('tenant-default-mobile-menu');
    if (heroMenu)    heroMenu.style.display    = 'none';
    if (defaultMenu) defaultMenu.style.display = 'none';
}
document.addEventListener('click', function(e) {
    var hero    = document.getElementById('tenant-hero-header');
    var def     = document.getElementById('tenant-default-header');
    var header  = hero || def;
    if (header && !header.contains(e.target)) tenantNavClose();
});

// Hero header scroll: pure JS, no Alpine dependency
(function() {
    var h = document.getElementById('tenant-hero-header');
    if (!h) return;
    var nav  = document.getElementById('tenant-nav');
    var ham  = document.getElementById('tenant-hamburger');
    var logo = document.getElementById('tenant-logo');
    function update() {
        var s = window.scrollY > 50;
        h.classList.toggle('is-scrolled', s);
        var vis = s ? 'opacity:1;pointer-events:auto' : 'opacity:0;pointer-events:none';
        if (nav)  nav.style.cssText  = vis;
        if (ham)  ham.style.cssText  = vis;
        if (logo) logo.style.filter  = s ? '' : 'drop-shadow(0 2px 4px rgba(0,0,0,0.3))';
        // Close mobile menu when scrolling back to top
        if (!s) tenantNavClose();
    }
    window.addEventListener('scroll', update, { passive: true });
})();

// Dark mode toggle: pure JS, no Alpine dependency
function updateThemeIcons() {
    var dark = document.documentElement.classList.contains('dark');
    var ids = [
        ['theme-icon-moon',              'theme-icon-sun'],
        ['default-theme-icon-moon',      'default-theme-icon-sun'],
        ['default-mobile-theme-icon-moon','default-mobile-theme-icon-sun'],
    ];
    ids.forEach(function(pair) {
        var moon = document.getElementById(pair[0]);
        var sun  = document.getElementById(pair[1]);
        if (moon) moon.style.display = dark ? 'none' : '';
        if (sun)  sun.style.display  = dark ? '' : 'none';
    });
    var lbl = document.getElementById('default-mobile-theme-label');
    if (lbl) lbl.textContent = dark ? 'Light Mode' : 'Dark Mode';
}
function themeToggle() {
    var dark = !document.documentElement.classList.contains('dark');
    document.documentElement.classList.toggle('dark', dark);
    localStorage.setItem('theme', dark ? 'dark' : 'light');
    updateThemeIcons();
}
updateThemeIcons();



@yield('scripts')
</script>
</body>
</html>