<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin') — BusyRealtor</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }

        /* ===================== DARK MODE — SUPER ADMIN ===================== */
        .dark, .dark body { color-scheme: dark; }
        .dark body { background-color: #0f172a !important; color: #f1f5f9; }

        /* Backgrounds */
        .dark .bg-white       { background-color: #1e293b !important; }
        .dark .bg-gray-50     { background-color: #0f172a !important; }
        .dark .bg-gray-100    { background-color: #1e293b !important; }
        .dark .bg-gray-200    { background-color: #334155 !important; }

        /* Text */
        .dark .text-gray-900  { color: #f1f5f9 !important; }
        .dark .text-gray-800  { color: #e2e8f0 !important; }
        .dark .text-gray-700  { color: #cbd5e1 !important; }
        .dark .text-gray-600  { color: #94a3b8 !important; }
        .dark .text-gray-500  { color: #64748b !important; }
        .dark .text-gray-400  { color: #475569 !important; }

        /* Borders */
        .dark .border-gray-50  { border-color: #1e293b !important; }
        .dark .border-gray-100 { border-color: #334155 !important; }
        .dark .border-gray-200 { border-color: #334155 !important; }
        .dark .border-gray-300 { border-color: #475569 !important; }
        .dark .border-t,
        .dark .border-b,
        .dark .border-l,
        .dark .border-r,
        .dark .border         { border-color: #334155; }
        .dark .divide-y > * + * { border-color: #334155; }

        /* Inputs */
        .dark input:not([type=checkbox]):not([type=radio]):not([type=range]),
        .dark select,
        .dark textarea {
            background-color: #334155 !important;
            color: #f1f5f9 !important;
            border-color: #475569 !important;
        }
        .dark input::placeholder,
        .dark textarea::placeholder { color: #64748b !important; }

        /* Hover */
        .dark .hover\:bg-gray-50:hover  { background-color: #1e293b !important; }
        .dark .hover\:bg-gray-100:hover { background-color: #334155 !important; }

        /* Tables */
        .dark table thead           { background-color: #1e293b !important; }
        .dark table tbody tr        { border-color: #334155; }
        .dark table tbody tr:hover  { background-color: #1e293b !important; }

        /* Status badges */
        .dark .bg-green-100  { background-color: rgba(16,185,129,0.15) !important; }
        .dark .bg-yellow-100 { background-color: rgba(234,179,8,0.15) !important; }
        .dark .bg-blue-100   { background-color: rgba(59,130,246,0.15) !important; }
        .dark .bg-red-100    { background-color: rgba(239,68,68,0.15) !important; }
        .dark .bg-purple-100 { background-color: rgba(168,85,247,0.15) !important; }
        .dark .bg-indigo-100 { background-color: rgba(99,102,241,0.15) !important; }
        .dark .text-green-800  { color: #6ee7b7 !important; }
        .dark .text-yellow-800 { color: #fde68a !important; }
        .dark .text-blue-800   { color: #93c5fd !important; }
        .dark .text-red-800    { color: #fca5a5 !important; }
        .dark .text-purple-800 { color: #d8b4fe !important; }

        /* Shadows */
        .dark .shadow-sm { box-shadow: 0 1px 2px rgba(0,0,0,0.5) !important; }
        .dark .shadow    { box-shadow: 0 1px 6px rgba(0,0,0,0.5) !important; }
        .dark .shadow-lg { box-shadow: 0 4px 20px rgba(0,0,0,0.6) !important; }

        /* Rings */
        .dark .ring-1, .dark .ring-2 { --tw-ring-color: #475569; }
        /* ===================== END DARK MODE ===================== */
        @yield('styles')
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen" data-theme="force-dark" x-data="{ open: false }">

{{-- Mobile top bar --}}
<div class="md:hidden bg-gray-800 border-b border-gray-700 px-4 py-3 flex items-center justify-between sticky top-0 z-50">
    <button @click="open = !open" class="p-1 text-gray-300 hover:text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <span class="text-white font-semibold text-sm">Super Admin</span>
    <span class="text-gray-400 text-sm">{{ now()->format('M j, Y') }}</span>
</div>

{{-- Mobile slide-down menu --}}
@php
    $superIsDash     = request()->routeIs('super.dashboard');
    $superIsTenants  = request()->routeIs('super.tenants*');
    $superIsFeedback = request()->routeIs('super.feedback*');
    $superIsSettings = request()->routeIs('super.settings*');
    $superIsMailer   = request()->routeIs('super.mailer*');
    $superIsActivity = request()->routeIs('super.activity*');
    $superFeedbackBadge = \App\Models\Feedback::where('status','new')->count();
@endphp
<div x-show="open" x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-2"
     class="md:hidden bg-gray-800 border-b border-gray-700 z-40">
    <nav class="px-4 py-3 space-y-1">
        <a href="{{ route('super.dashboard') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ $superIsDash ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>
        <a href="{{ route('super.tenants') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ $superIsTenants ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Tenants
        </a>
        <a href="{{ route('super.feedback') }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium {{ $superIsFeedback ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition-colors">
            <span class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                Feedback
            </span>
            @if($superFeedbackBadge > 0)
            <span class="bg-blue-500 text-white text-xs rounded-full px-1.5 py-0.5 font-semibold">{{ $superFeedbackBadge }}</span>
            @endif
        </a>
        <a href="{{ route('super.mailer') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ $superIsMailer ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Mailer
        </a>
        <a href="{{ route('super.activity') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ $superIsActivity ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Activity
        </a>
        <a href="{{ route('super.settings') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ $superIsSettings ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Settings
        </a>
        <div class="border-t border-gray-700 my-2"></div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center w-full px-3 py-2 rounded-lg text-sm font-medium text-red-400 hover:bg-gray-700 hover:text-red-300 transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Logout
            </button>
        </form>
    </nav>
</div>

{{-- Desktop layout --}}
<div class="flex flex-1 overflow-hidden">

{{-- Dark Sidebar --}}
<aside class="hidden md:flex w-64 min-h-screen bg-gray-800 flex-col flex-shrink-0">
    <div class="p-6 border-b border-gray-700">
        <h1><span style="font-size:1.25rem;font-weight:800;line-height:1;"><span style="color:#7dd3fc;">Busy</span><span style="color:#fb923c;">Realtor</span></span></h1>
        <p class="text-gray-400 text-xs mt-1">Super Admin Panel</p>
    </div>
    <nav class="flex-1 p-4 space-y-1">
        <a href="{{ route('super.dashboard') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('super.dashboard') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>
        <a href="{{ route('super.tenants') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('super.tenants*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Tenants
        </a>
        @php $feedbackBadge = \App\Models\Feedback::where('status','new')->count(); @endphp
        <a href="{{ route('super.feedback') }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('super.feedback*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition-colors">
            <span class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                Feedback
            </span>
            @if($feedbackBadge > 0)
            <span class="bg-blue-500 text-white text-xs rounded-full px-1.5 py-0.5 font-semibold">{{ $feedbackBadge }}</span>
            @endif
        </a>
        <a href="{{ route('super.mailer') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('super.mailer*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Mailer
        </a>
        <a href="{{ route('super.activity') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('super.activity*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Activity
        </a>
        <a href="{{ route('super.settings') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('super.settings*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Settings
        </a>
        <div class="border-t border-gray-700 my-3"></div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center w-full px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Logout
            </button>
        </form>
    </nav>
    <div class="p-4 border-t border-gray-700 text-xs text-gray-500">
        <p>{{ auth()->user()->first_name }}</p>
        <p class="mt-1">Super Administrator</p>
    </div>
</aside>

{{-- Main Content --}}
<div class="flex-1 flex flex-col min-h-screen min-w-0">
    {{-- Top bar --}}
    <div class="hidden md:flex bg-gray-800 border-b border-gray-700 px-6 py-3 items-center justify-between">
        <div class="flex items-center gap-3">
            <h2 class="text-white font-medium">@yield('page-title', 'Dashboard')</h2>
            @hasSection('page-description')
            <span class="text-gray-500 text-sm font-normal">&mdash; @yield('page-description')</span>
            @endif
        </div>
        <div class="flex items-center gap-4">
            <div class="relative" x-data="tenantSearch()" @click.outside="open = false">
                <div class="relative">
                    <svg class="w-4 h-4 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(results.length) open = true" placeholder="Jump to tenant..." class="w-56 bg-gray-700 border border-gray-600 rounded-lg pr-3 py-1.5 text-sm text-gray-200 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" style="padding-left: 2.25rem;">
                </div>
                <div x-show="open && results.length > 0" x-cloak class="absolute right-0 mt-1 w-72 bg-gray-800 border border-gray-600 rounded-xl shadow-xl z-50 overflow-hidden">
                    <template x-for="t in results" :key="t.slug">
                        <a :href="'/super-admin/tenants/' + t.slug" class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-700 transition-colors">
                            <div>
                                <p class="text-sm font-medium text-white" x-text="t.name"></p>
                                <p class="text-xs text-gray-400" x-text="t.slug"></p>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="t.plan === 'pro' ? 'bg-purple-900 text-purple-300' : (t.plan === 'starter' ? 'bg-blue-900 text-blue-300' : 'bg-yellow-900 text-yellow-300')" x-text="t.plan.charAt(0).toUpperCase() + t.plan.slice(1)"></span>
                        </a>
                    </template>
                </div>
            </div>
            <span class="text-gray-400 text-sm">{{ now()->format('M j, Y') }}</span>
        </div>
    </div>

    @include('partials.flash')

    <main class="flex-1 p-4 md:p-6 bg-gray-900 overflow-x-auto">
        @yield('content')
    </main>
</div>
</div>{{-- /desktop flex wrapper --}}

<script>
@yield('scripts')
</script>
<script>
function tenantSearch() {
    return {
        query: '',
        results: [],
        open: false,
        async search() {
            if (this.query.length < 2) { this.results = []; this.open = false; return; }
            try {
                const res = await fetch('/super-admin/api/tenants/search?q=' + encodeURIComponent(this.query));
                this.results = await res.json();
                this.open = this.results.length > 0;
            } catch (e) { this.results = []; this.open = false; }
        }
    };
}
</script>
</body>
</html>
