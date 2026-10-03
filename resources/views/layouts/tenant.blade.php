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
    {{-- preconnect before @vite: the hint has to be read before the requests it warms --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $titleFont = $settings->title_font ?? 'Poppins';
        $primaryColor = $settings->primary_color ?? '#3B82F6';
        $r = hexdec(substr(ltrim($primaryColor,'#'), 0, 2));
        $g = hexdec(substr(ltrim($primaryColor,'#'), 2, 2));
        $b = hexdec(substr(ltrim($primaryColor,'#'), 4, 2));
    @endphp
    <link href="https://fonts.googleapis.com/css2?family={{ urlencode($titleFont) }}:wght@400;600;700;800&display=swap" rel="stylesheet">
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
        .dark footer .text-gray-500 { color: #64748b !important; }
        .dark footer .social-icon        { background-color: #1f2937 !important; }
        .dark footer .social-icon:hover  { background-color: #374151 !important; }
        .dark footer .text-gray-600      { color: #94a3b8 !important; }
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
    <meta name="ga-id" content="{{ $ga->api_key }}">
    @endif
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">

@php
    $headerMode = $settings->header_mode ?? 'default';
    // Only the homepage uses hero mode. Everywhere else takes the sticky default
    // header, because hero's bar is fixed and transparent and would sit on top of the
    // page's content rather than above it.
    if (! request()->routeIs('tenant.home')) {
        $headerMode = 'default';
    }
    $headerDisplayMode = $settings->header_display_mode ?? 'both';
    $titleColorType = $settings->titleColor('title_color_type');
    $gradStart = $settings->titleColor('title_gradient_start');
    $gradVia = $settings->titleColor('title_gradient_via');
    $gradEnd = $settings->titleColor('title_gradient_end');
    $solidColor = $settings->titleColor('title_color_solid');
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
    // Enabled is not the same as usable: the widget also needs the Pro plan and a working
    // provider key, or a visitor opens it and is told the agent has not set it up.
    $chatbotReady = app('tenant')->chatbotReady();
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
                    <img src="{{ url('/' . $account . '/favicon.svg') . '?v=' . optional($settings->updated_at)->timestamp }}" alt="{{ $settings->site_title ?: $tenant->name }}" width="32" height="32" class="h-8 w-8 object-contain rounded-lg">
                @endif
                @if(in_array($headerDisplayMode, ['text_only', 'favicon_text', 'both']))
                    <span id="site-title-text" style="{{ $titleStyle }}">{{ $settings->site_title ?: 'Your Agency Name Here' }}</span>
                @endif
            </a>
            <nav id="tenant-nav" class="hidden md:flex items-center space-x-6 transition-all duration-300" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3))">
                <a href="{{ $galleryUrl }}" class="nav-link font-medium transition-colors hover-primary" @if($isGallery) style="color: var(--primary);" @endif>Gallery</a>
                <a href="{{ $mapUrl }}" class="nav-link font-medium transition-colors hover-primary" @if($isMap) style="color: var(--primary);" @endif>Map</a>
                <a href="{{ route('login') }}" class="nav-link font-medium transition-colors hover-primary" @if($isLogin) style="color: var(--primary);" @endif>Login</a>
                <button id="theme-toggle-btn" data-theme-toggle class="theme-btn p-1 rounded-full">
                    <svg width="20" height="20" id="theme-icon-moon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg width="20" height="20" id="theme-icon-sun" class="w-5 h-5" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
            </nav>
            <button data-nav-toggle id="tenant-hamburger" class="hamburger-btn md:hidden p-2 rounded transition-all duration-300" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3))">
                <x-icon name="bars" class="w-6 h-6" />
            </button>
        </div>
    </div>
    <div id="tenant-mobile-menu" style="display:none"
         class="md:hidden border-t bg-white shadow-lg">
        <nav class="px-4 py-3 space-y-1">
            <a href="{{ $galleryUrl }}" class="flex items-center px-3 py-2 rounded-lg font-medium @if($isGallery) text-white @else text-gray-700 hover:bg-gray-100 @endif" @if($isGallery) style="background-color: rgba({{ $pr }},{{ $pg }},{{ $pb }},0.1); color: var(--primary);" @endif>
                <svg width="20" height="20" class="w-5 h-5 mr-3 @if($isGallery) @else text-gray-500 @endif" style="@if($isGallery) color: var(--primary); @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Gallery
            </a>
            <a href="{{ $mapUrl }}" class="flex items-center px-3 py-2 rounded-lg font-medium @if($isMap) @else text-gray-700 hover:bg-gray-100 @endif" @if($isMap) style="background-color: rgba({{ $pr }},{{ $pg }},{{ $pb }},0.1); color: var(--primary);" @endif>
                <svg width="20" height="20" class="w-5 h-5 mr-3 @if($isMap) @else text-gray-500 @endif" style="@if($isMap) color: var(--primary); @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                Map
            </a>
            <div class="border-t my-2"></div>
            <a href="{{ route('tenant.contact', $account) }}" class="flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
                <x-icon name="envelope" class="w-5 h-5 mr-3 text-gray-500" />
                Contact Us
            </a>
            @if($chatbotReady)
            <a href="{{ route('tenant.chat', $account) }}" class="flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
                <x-icon name="chat-bubble" class="w-5 h-5 mr-3 text-gray-500" />
                Chat Assistant
            </a>
            @endif
            <div class="border-t my-2"></div>
            <a href="{{ route('login') }}" class="flex items-center px-3 py-2 rounded-lg font-medium @if($isLogin) @else text-gray-700 hover:bg-gray-100 @endif" @if($isLogin) style="background-color: rgba({{ $pr }},{{ $pg }},{{ $pb }},0.1); color: var(--primary);" @endif>
                <svg width="20" height="20" class="w-5 h-5 mr-3 @if($isLogin) @else text-gray-500 @endif" style="@if($isLogin) color: var(--primary); @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
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
                    <img src="{{ url('/' . $account . '/favicon.svg') . '?v=' . optional($settings->updated_at)->timestamp }}" alt="{{ $settings->site_title ?: $tenant->name }}" width="32" height="32" class="h-8 w-8 object-contain rounded-lg">
                @endif
                @if(in_array($headerDisplayMode, ['text_only', 'favicon_text', 'both']))
                    <span id="site-title-text" style="{{ $titleStyle }}">{{ $settings->site_title ?: 'Your Agency Name Here' }}</span>
                @endif
            </a>
            <nav class="hidden md:flex items-center space-x-6">
                <a href="{{ $galleryUrl }}" class="font-medium transition-colors hover-primary @if(!$isGallery) text-gray-700 @endif" @if($isGallery) style="color: var(--primary);" @endif>Gallery</a>
                <a href="{{ $mapUrl }}" class="font-medium transition-colors hover-primary @if(!$isMap) text-gray-700 @endif" @if($isMap) style="color: var(--primary);" @endif>Map</a>
                <a href="{{ route('login') }}" class="font-medium transition-colors hover-primary @if(!$isLogin) text-gray-700 @endif" @if($isLogin) style="color: var(--primary);" @endif>Login</a>
                <button data-theme-toggle class="theme-btn p-1 rounded-full text-gray-600 hover:text-gray-900">
                    <svg width="20" height="20" id="default-theme-icon-moon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg width="20" height="20" id="default-theme-icon-sun" class="w-5 h-5" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
            </nav>
            <button data-nav-toggle id="tenant-default-hamburger" class="md:hidden p-2 rounded text-gray-700">
                <x-icon name="bars" class="w-6 h-6" />
            </button>
        </div>
    </div>
    <div id="tenant-default-mobile-menu" style="display:none"
         class="md:hidden border-t bg-white">
        <nav class="px-4 py-3 space-y-1">
            <a href="{{ $galleryUrl }}" data-nav-close class="flex items-center px-3 py-2 rounded-lg font-medium @if($isGallery) @else text-gray-700 hover:bg-gray-100 @endif" @if($isGallery) style="background-color: rgba({{ $pr }},{{ $pg }},{{ $pb }},0.1); color: var(--primary);" @endif>
                <svg width="20" height="20" class="w-5 h-5 mr-3 @if($isGallery) @else text-gray-500 @endif" style="@if($isGallery) color: var(--primary); @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Gallery
            </a>
            <a href="{{ $mapUrl }}" class="flex items-center px-3 py-2 rounded-lg font-medium @if($isMap) @else text-gray-700 hover:bg-gray-100 @endif" @if($isMap) style="background-color: rgba({{ $pr }},{{ $pg }},{{ $pb }},0.1); color: var(--primary);" @endif>
                <svg width="20" height="20" class="w-5 h-5 mr-3 @if($isMap) @else text-gray-500 @endif" style="@if($isMap) color: var(--primary); @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                Map
            </a>
            <div class="border-t my-2"></div>
            <a href="{{ route('tenant.contact', $account) }}" class="flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
                <x-icon name="envelope" class="w-5 h-5 mr-3 text-gray-500" />
                Contact Us
            </a>
            @if($chatbotReady)
            <a href="{{ route('tenant.chat', $account) }}" class="flex items-center px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
                <x-icon name="chat-bubble" class="w-5 h-5 mr-3 text-gray-500" />
                Chat Assistant
            </a>
            @endif
            <div class="border-t my-2"></div>
            <button data-theme-toggle class="flex items-center w-full px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
                <svg width="20" height="20" id="default-mobile-theme-icon-moon" class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg width="20" height="20" id="default-mobile-theme-icon-sun" class="w-5 h-5 mr-3 text-gray-500" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span id="default-mobile-theme-label">Dark Mode</span>
            </button>
            <div class="border-t my-2"></div>
            <a href="{{ route('login') }}" class="flex items-center px-3 py-2 rounded-lg font-medium @if($isLogin) @else text-gray-700 hover:bg-gray-100 @endif" @if($isLogin) style="background-color: rgba({{ $pr }},{{ $pg }},{{ $pb }},0.1); color: var(--primary);" @endif>
                <svg width="20" height="20" class="w-5 h-5 mr-3 @if($isLogin) @else text-gray-500 @endif" style="@if($isLogin) color: var(--primary); @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
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
            <button data-cookie-consent="false" class="cookie-btn-decline">Decline</button>
            <button data-cookie-consent="true" class="cookie-btn-accept">Accept All</button>
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
{{-- The cookie banner lives in a bundle; it was duplicated in both layouts. --}}
@vite('resources/js/cookie-consent.js')

@if(View::hasSection('show_footer'))
<footer class="bg-gray-100 text-gray-600 mt-auto">
    <div class="max-w-7xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-8 mb-10">
            {{-- Brand --}}
            <div class="md:col-span-2">
                <div class="flex items-center space-x-3 mb-4">
                    @if(!empty($settings->favicon_preset))
                        <img src="{{ url('/' . $account . '/favicon.svg') . '?v=' . optional($settings->updated_at)->timestamp }}" alt="{{ $settings->site_title ?: $tenant->name }}" width="32" height="32" class="h-8 w-8 object-contain rounded-lg flex-shrink-0">
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
                            <svg width="16" height="16" class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="{{ $social['path'] }}"/></svg>
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
                    <li><button data-cookie-prefs class="footer-link transition-colors text-left" id="cookie-prefs-link">Cookie Preferences</button></li>
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
@if($chatbotReady)
@php $chatbotApiUrl = route('tenant.api.chatbot', $account); @endphp
<div class="hidden md:block"><div id="chatbot-root" data-api-url="{{ $chatbotApiUrl }}"></div></div>
@endif
@endunless

{{-- Contact Modal Widget (always shown on public pages) --}}
@unless(View::hasSection('hide_contact_widget'))
@php $contactApiUrl = route('tenant.api.contact', $account); @endphp
<div class="hidden md:block"><div id="contact-widget-root"
     data-api-url="{{ $contactApiUrl ?? '' }}"
     data-privacy-url="{{ route('tenant.privacy', $account) }}"></div></div>

{{-- The two floating widgets. 24.8 KB of inline script until 2026-10-01. --}}
@vite('resources/js/tenant-widgets.js')
@endunless

{{-- Bottom-of-body slot for bundles. The block below wraps @yield('scripts') in a
     <script> tag, so a @vite tag has to be emitted before it, not inside it. --}}
@yield('foot')

@vite('resources/js/tenant-chrome.js')

{{-- Only for the views that still carry one. Without the guard, every page that has
     been bundled still emits an empty <script> element. --}}
@hasSection('scripts')
<script>
@yield('scripts')
</script>
@endif
</body>
</html>