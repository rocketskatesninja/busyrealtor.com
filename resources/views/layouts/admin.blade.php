<!DOCTYPE html>
<html lang="en" x-data x-bind:class="$store.theme.dark ? 'dark' : ''">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ $tenant->name ?? 'BusyRealtor' }}</title>
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: "class" }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @php
        $settings = $settings ?? \App\Models\SiteSettings::where('tenant_id', $tenant->id)->first();
        $titleFont = $settings->title_font ?? 'Poppins';
        $primaryColor = $settings->primary_color ?? '#3B82F6';
        $r = hexdec(substr(ltrim($primaryColor,'#'), 0, 2));
        $g = hexdec(substr(ltrim($primaryColor,'#'), 2, 2));
        $b = hexdec(substr(ltrim($primaryColor,'#'), 4, 2));
        $titleColorType = $settings->title_color_type ?? 'gradient';
        $gradStart = $settings->title_gradient_start ?? '#3B82F6';
        $gradVia = $settings->title_gradient_via ?? '#8B5CF6';
        $gradEnd = $settings->title_gradient_end ?? '#1E40AF';
        $solidColor = $settings->title_color_solid ?? '#3B82F6';
        $titleSize = match($settings->site_title_font_size ?? '3xl') { 'xl' => '1.25rem', '2xl' => '1.5rem', '4xl' => '2.25rem', default => '1.875rem' };
        $titleWeight = $settings->site_title_font_weight ?? '800';
        $titleStyle = "font-family: '{$titleFont}', sans-serif; font-size: {$titleSize}; font-weight: {$titleWeight};";
        if ($titleColorType === 'gradient') {
            $titleStyle .= " background: linear-gradient(135deg, {$gradStart}, {$gradVia}, {$gradEnd}); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;";
        } else {
            $titleStyle .= " color: {$solidColor};";
        }
        $headerDisplayMode = $settings->header_display_mode ?? 'both';
        $account = $tenant->slug;
    @endphp
    <link href="https://fonts.googleapis.com/css2?family={{ urlencode($titleFont) }}:wght@600;700;800&display=block" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        :root { --primary: {{ $primaryColor }}; --primary-rgb: {{ $r }}, {{ $g }}, {{ $b }}; }
        .nav-active { color: var(--primary) !important; }
        .hover-primary:hover { color: var(--primary) !important; }
        .btn-primary { background-color: var(--primary); color: white; }
        .btn-primary:hover { opacity: 0.9; }

        /* Light mode: make white panels stand out against the gray-50 background */
        html:not(.dark) .border-gray-100 {
            border-color: #d1d5db !important; /* gray-300 */
            box-shadow: 0 2px 8px rgba(0,0,0,0.07) !important;
        }

        /* ===================== DARK MODE ===================== */
        .dark, .dark body { color-scheme: dark; }
        .dark body { background-color: #0f172a !important; color: #f1f5f9; }

        /* Backgrounds */
        .dark .bg-white       { background-color: #1e293b !important; }
        .dark .bg-gray-50     { background-color: #0f172a !important; }
        .dark .bg-gray-100    { background-color: #1e293b !important; }
        .dark .bg-gray-200    { background-color: #334155 !important; }
        .dark .bg-gray-800    { background-color: #020617 !important; }
        .dark .bg-gray-900    { background-color: #020617 !important; }

        /* Text */
        .dark .text-gray-900  { color: #f1f5f9 !important; }
        .dark .text-gray-800  { color: #e2e8f0 !important; }
        .dark .text-gray-700  { color: #cbd5e1 !important; }
        .dark .text-gray-600  { color: #94a3b8 !important; }
        .dark .text-gray-500  { color: #64748b !important; }
        .dark .text-gray-400  { color: #475569 !important; }

        /* Borders */
        .dark .border-gray-50  { border-color: #1e293b !important; }
        .dark .border-gray-100 { border-color: #1e293b !important; }
        .dark .border-gray-200 { border-color: #334155 !important; }
        .dark .border-gray-300 { border-color: #475569 !important; }
        .dark .border-t,
        .dark .border-b,
        .dark .border-l,
        .dark .border-r,
        .dark .border         { border-color: #334155; }

        /* Divide */
        .dark .divide-y > * + *,
        .dark .divide-x > * + * { border-color: #334155; }

        /* Inputs, selects, textareas */
        .dark input:not([type=checkbox]):not([type=radio]):not([type=range]),
        .dark select,
        .dark textarea {
            background-color: #334155 !important;
            color: #f1f5f9 !important;
            border-color: #475569 !important;
        }
        .dark input::placeholder,
        .dark textarea::placeholder { color: #64748b !important; }

        /* Hover states */
        .dark .hover\:bg-gray-50:hover  { background-color: #1e293b !important; }
        .dark .hover\:bg-gray-100:hover { background-color: #334155 !important; }
        .dark .hover\:bg-gray-200:hover { background-color: #475569 !important; }
        .dark .hover\:bg-blue-50:hover  { background-color: rgba(59,130,246,0.15) !important; }
        .dark .hover\:bg-red-50:hover   { background-color: rgba(239,68,68,0.15) !important; }
        .dark .hover\:bg-green-50:hover { background-color: rgba(16,185,129,0.15) !important; }
        .dark .hover\:text-blue-600:hover { color: #93c5fd !important; }
        .dark .hover\:text-red-600:hover  { color: #fca5a5 !important; }
        .dark .hover\:text-gray-600:hover { color: #94a3b8 !important; }

        /* Tables */
        .dark table thead { background-color: #1e293b !important; }
        .dark table tbody tr { border-color: #334155; }
        .dark table tbody tr:hover { background-color: #1e293b !important; }

        /* Status badges — soften */
        .dark .bg-green-100  { background-color: rgba(16,185,129,0.15) !important; }
        .dark .bg-yellow-100 { background-color: rgba(234,179,8,0.15) !important; }
        .dark .bg-blue-100   { background-color: rgba(59,130,246,0.15) !important; }
        .dark .bg-red-100    { background-color: rgba(239,68,68,0.15) !important; }
        .dark .bg-purple-100 { background-color: rgba(168,85,247,0.15) !important; }
        .dark .bg-indigo-100 { background-color: rgba(99,102,241,0.15) !important; }

        /* Shadows become softer */
        .dark .shadow-sm   { box-shadow: 0 1px 2px rgba(0,0,0,0.5) !important; }
        .dark .shadow      { box-shadow: 0 1px 6px rgba(0,0,0,0.5) !important; }
        .dark .shadow-lg   { box-shadow: 0 4px 20px rgba(0,0,0,0.6) !important; }
        .dark .shadow-xl   { box-shadow: 0 8px 30px rgba(0,0,0,0.7) !important; }

        /* Rings */
        .dark .ring-1,
        .dark .ring-2 { --tw-ring-color: #475569; }

        /* Footer */
        .dark footer { background-color: #0f172a !important; border-color: #1e293b !important; }
        .dark footer .text-gray-800 { color: #e2e8f0 !important; }
        .dark footer .text-gray-600 { color: #94a3b8 !important; }
        .dark footer .text-gray-500 { color: #64748b !important; }
        .dark footer .bg-gray-100   { background-color: #1e293b !important; }
        .dark footer .bg-gray-200   { background-color: #334155 !important; }

        /* Nav */
        .dark header.bg-white { background-color: #1e293b !important; }
        .dark nav a.text-gray-700 { color: #cbd5e1 !important; }

        /* Colored bg-50 variants (export buttons etc.) */
        .dark .bg-blue-50   { background-color: rgba(59,130,246,0.12) !important; }
        .dark .bg-green-50  { background-color: rgba(16,185,129,0.12) !important; }
        .dark .bg-purple-50 { background-color: rgba(168,85,247,0.12) !important; }
        .dark .bg-orange-50 { background-color: rgba(249,115,22,0.12)  !important; }

        /* Colored text-700 variants (export buttons) */
        .dark .text-blue-700   { color: #93c5fd !important; }
        .dark .text-green-700  { color: #86efac !important; }
        .dark .text-purple-700 { color: #c4b5fd !important; }
        .dark .text-orange-700 { color: #fdba74 !important; }

        /* Colored hover-bg-100 variants (export button hovers) */
        .dark .hover\:bg-blue-100:hover   { background-color: rgba(59,130,246,0.22) !important; }
        .dark .hover\:bg-green-100:hover  { background-color: rgba(16,185,129,0.22) !important; }
        .dark .hover\:bg-purple-100:hover { background-color: rgba(168,85,247,0.22) !important; }
        .dark .hover\:bg-orange-100:hover { background-color: rgba(249,115,22,0.22)  !important; }
        /* ===================== END DARK MODE ===================== */

        /* ── Console (data tab): light mode overrides ── */
        html:not(.dark) .console-wrap     { border-color: #e2e8f0 !important; }
        html:not(.dark) #data-console-hdr { background-color: #f1f5f9 !important; }
        html:not(.dark) #data-console     { background-color: #f9fafb !important; }
        @yield('styles')
    </style>
    @yield('head')
</head>
<body class="bg-gray-50 min-h-screen">

@php $impersonating = session()->has('impersonating_tenant_id'); @endphp
@if($impersonating)
<div class="bg-yellow-500 text-white text-center py-2 px-4 text-sm font-medium">
    You are viewing <strong>{{ $tenant->name }}</strong> as super admin —
    <form method="POST" action="{{ route('super.stop-impersonate') }}" class="inline">
        @csrf
        <button type="submit" class="underline font-bold ml-1">Stop Impersonating</button>
    </form>
</div>
@endif

{{-- TOP NAV --}}
<header class="bg-white shadow-lg sticky top-0 z-50" x-data="{ open: false, userOpen: false }">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between py-4">
            <a href="{{ route('tenant.home', $account) }}" target="_blank" class="flex items-center space-x-3 drop-shadow-lg hover:opacity-80 transition-opacity">
                @if($headerDisplayMode !== 'text_only' && $settings->logo_image)
                    <img src="{{ asset('storage/' . $settings->logo_image) }}" alt="Logo" class="h-10 w-auto object-contain">
                @endif
                @if($headerDisplayMode !== 'logo_only')
                    <span style="{{ $titleStyle }}">{{ $settings->site_title ?? $tenant->name }}</span>
                @endif
            </a>

            {{-- Desktop Nav --}}
            <div class="hidden md:flex items-center space-x-6">
                <nav class="flex items-center space-x-6">
                    <a href="{{ route('tenant.admin.dashboard', $account) }}" class="{{ request()->routeIs('tenant.admin.dashboard') ? 'nav-active' : 'text-gray-700 hover-primary' }} font-medium transition-colors">Dashboard</a>
                    <a href="{{ route('tenant.admin.properties.index', $account) }}" class="{{ request()->routeIs('tenant.admin.properties.*') ? 'nav-active' : 'text-gray-700 hover-primary' }} font-medium transition-colors">Properties</a>
                    @if($tenant->isPro())
                    <a href="{{ route('tenant.admin.staff.index', $account) }}" class="{{ request()->routeIs('tenant.admin.staff.*') ? 'nav-active' : 'text-gray-700 hover-primary' }} font-medium transition-colors">Staff</a>
                    @endif
                    @if($tenant->isPro())
                    <a href="{{ route('tenant.admin.appointments.index', $account) }}" class="relative {{ request()->routeIs('tenant.admin.appointments.*') ? 'nav-active' : 'text-gray-700 hover-primary' }} font-medium transition-colors" title="Appointments">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        @if(($pendingAppointments ?? 0) > 0)
                            <span class="absolute -top-1 -right-1 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center" style="background-color: #F59E0B;">{{ $pendingAppointments > 9 ? '9+' : $pendingAppointments }}</span>
                        @endif
                    </a>
                    @endif
                    <a href="{{ route('tenant.admin.messages.index', $account) }}" class="relative {{ request()->routeIs('tenant.admin.messages.*') ? 'nav-active' : 'text-gray-700 hover-primary' }} transition-colors" title="Messages">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        @if(($unreadMessages ?? 0) > 0)
                            <span class="absolute -top-1 -right-1 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center" style="background-color: var(--primary);">{{ $unreadMessages > 9 ? '9+' : $unreadMessages }}</span>
                        @endif
                    </a>
                </nav>
                <div class="relative">
                    <button @click="userOpen = !userOpen" x-on:click.away="userOpen = false" class="flex items-center space-x-2 text-gray-700 hover-primary focus:outline-none">
                        <span class="font-medium">{{ explode(' ', auth()->user()->name)[0] }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="userOpen" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 border">
                        <a href="{{ route('tenant.admin.settings', $account) }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm">
                            <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Settings
                        </a>
                        <a href="{{ route('tenant.admin.billing', $account) }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm">
                            <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            Billing
                        </a>
                        <button @click="$store.theme.toggle()" class="flex items-center w-full px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm text-left">
                            <svg x-show="!$store.theme.dark" class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            <svg x-show="$store.theme.dark" x-cloak class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span x-text="$store.theme.dark ? 'Light Mode' : 'Dark Mode'"></span>
                        </button>
                        <a href="{{ route('tenant.admin.feedback', $account) }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm">
                            <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                            Submit Feedback
                        </a>
                        <hr class="my-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center w-full px-4 py-2 text-red-600 hover:bg-gray-100 text-sm">
                                <svg class="w-4 h-4 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Mobile menu button --}}
            <button @click="open = !open" class="md:hidden p-2 rounded text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>

    {{-- Mobile menu --}}
    @php
        $isDash     = request()->routeIs('tenant.admin.dashboard');
        $isProps    = request()->routeIs('tenant.admin.properties.*');
        $isStaff    = request()->routeIs('tenant.admin.staff.*');
        $isAppts    = request()->routeIs('tenant.admin.appointments.*');
        $isMsgs     = request()->routeIs('tenant.admin.messages.*');
        $isSettings = request()->routeIs('tenant.admin.settings*');
    @endphp
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden border-t bg-white shadow-lg">
        <nav class="px-4 py-3 space-y-1">
            <a href="{{ route('tenant.admin.dashboard', $account) }}" class="flex items-center px-3 py-2 rounded-lg font-medium {{ $isDash ? '' : 'text-gray-700 hover:bg-gray-100' }}" @if($isDash) style="background-color: rgba({{ $r }},{{ $g }},{{ $b }},0.1); color: var(--primary);" @endif>
                <svg class="w-5 h-5 mr-3 {{ $isDash ? '' : 'text-gray-500' }}" style="{{ $isDash ? 'color:var(--primary)' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                Dashboard
            </a>
            <a href="{{ route('tenant.admin.properties.index', $account) }}" class="flex items-center px-3 py-2 rounded-lg font-medium {{ $isProps ? '' : 'text-gray-700 hover:bg-gray-100' }}" @if($isProps) style="background-color: rgba({{ $r }},{{ $g }},{{ $b }},0.1); color: var(--primary);" @endif>
                <svg class="w-5 h-5 mr-3 {{ $isProps ? '' : 'text-gray-500' }}" style="{{ $isProps ? 'color:var(--primary)' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Properties
            </a>
            @if($tenant->isPro())
            <a href="{{ route('tenant.admin.staff.index', $account) }}" class="flex items-center px-3 py-2 rounded-lg font-medium {{ $isStaff ? '' : 'text-gray-700 hover:bg-gray-100' }}" @if($isStaff) style="background-color: rgba({{ $r }},{{ $g }},{{ $b }},0.1); color: var(--primary);" @endif>
                <svg class="w-5 h-5 mr-3 {{ $isStaff ? '' : 'text-gray-500' }}" style="{{ $isStaff ? 'color:var(--primary)' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Staff
            </a>
            @endif
            @if($tenant->isPro())
            <a href="{{ route('tenant.admin.appointments.index', $account) }}" class="flex items-center justify-between px-3 py-2 rounded-lg font-medium {{ $isAppts ? '' : 'text-gray-700 hover:bg-gray-100' }}" @if($isAppts) style="background-color: rgba({{ $r }},{{ $g }},{{ $b }},0.1); color: var(--primary);" @endif>
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 {{ $isAppts ? '' : 'text-gray-500' }}" style="{{ $isAppts ? 'color:var(--primary)' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Appointments
                </span>
                @if(($pendingAppointments ?? 0) > 0)
                <span class="bg-yellow-500 text-white text-xs rounded-full px-2 py-0.5">{{ $pendingAppointments }}</span>
                @endif
            </a>
            @endif
            <a href="{{ route('tenant.admin.messages.index', $account) }}" class="flex items-center justify-between px-3 py-2 rounded-lg font-medium {{ $isMsgs ? '' : 'text-gray-700 hover:bg-gray-100' }}" @if($isMsgs) style="background-color: rgba({{ $r }},{{ $g }},{{ $b }},0.1); color: var(--primary);" @endif>
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 {{ $isMsgs ? '' : 'text-gray-500' }}" style="{{ $isMsgs ? 'color:var(--primary)' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Messages
                </span>
                @if(($unreadMessages ?? 0) > 0)
                <span class="text-white text-xs rounded-full px-2 py-0.5" style="background-color:var(--primary)">{{ $unreadMessages }}</span>
                @endif
            </a>
            <div class="border-t my-2"></div>
            <a href="{{ route('tenant.admin.settings', $account) }}" class="flex items-center px-3 py-2 rounded-lg font-medium {{ $isSettings ? '' : 'text-gray-700 hover:bg-gray-100' }}" @if($isSettings) style="background-color: rgba({{ $r }},{{ $g }},{{ $b }},0.1); color: var(--primary);" @endif>
                <svg class="w-5 h-5 mr-3 {{ $isSettings ? '' : 'text-gray-500' }}" style="{{ $isSettings ? 'color:var(--primary)' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>
            <button @click="$store.theme.toggle()" class="flex items-center w-full px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
                <svg x-show="!$store.theme.dark" class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg x-show="$store.theme.dark" x-cloak class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span x-text="$store.theme.dark ? 'Light Mode' : 'Dark Mode'"></span>
            </button>
            <div class="border-t my-2"></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center w-full px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 font-medium">
                    <svg class="w-5 h-5 mr-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Logout
                </button>
            </form>
        </nav>
    </div>
</header>

{{-- Flash messages --}}
@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 dark:bg-green-600 dark:border-green-600 dark:text-white">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <span>{{ session('success') }}</span>
        <button @click="show = false" class="ml-4 opacity-70 hover:opacity-100">&times;</button>
    </div>
</div>
@endif
@if(session('error'))
<div x-data="{ show: true }" x-show="show" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 dark:bg-red-600 dark:border-red-600 dark:text-white">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <span>{{ session('error') }}</span>
        <button @click="show = false" class="ml-4 opacity-70 hover:opacity-100">&times;</button>
    </div>
</div>
@endif

<main class="min-h-screen bg-gray-50 py-6">
    @yield('content')
</main>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        dark: document.documentElement.classList.contains('dark'),
        toggle() { this.dark = !this.dark; document.documentElement.classList.toggle('dark', this.dark); localStorage.setItem('theme', this.dark ? 'dark' : 'light'); }
    });
});
@yield('scripts')
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</body>
</html>
