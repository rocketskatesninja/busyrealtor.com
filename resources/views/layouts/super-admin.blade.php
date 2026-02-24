<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin') — BusyRealtor</title>
    <script>tailwind.config = { darkMode: "class" }</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
<body class="bg-gray-900 text-white min-h-screen flex">

{{-- Dark Sidebar --}}
<aside class="w-64 min-h-screen bg-gray-800 flex flex-col flex-shrink-0">
    <div class="p-6 border-b border-gray-700">
        <h1 class="text-xl font-bold text-white">BusyRealtor</h1>
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
        <div class="border-t border-gray-700 my-3"></div>
        <a href="{{ url('/') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Site
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center w-full px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Logout
            </button>
        </form>
    </nav>
    <div class="p-4 border-t border-gray-700 text-xs text-gray-500">
        <p>{{ auth()->user()->name }}</p>
        <p class="mt-1">Super Administrator</p>
    </div>
</aside>

{{-- Main Content --}}
<div class="flex-1 flex flex-col min-h-screen">
    {{-- Top bar --}}
    <div class="bg-gray-800 border-b border-gray-700 px-6 py-3 flex items-center justify-between">
        <h2 class="text-white font-medium">@yield('page-title', 'Dashboard')</h2>
        <span class="text-gray-400 text-sm">{{ now()->format('M j, Y') }}</span>
    </div>

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="bg-green-600 text-white px-6 py-3 flex items-center justify-between">
        <span>{{ session('success') }}</span>
        <button @click="show = false">&times;</button>
    </div>
    @endif
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" class="bg-red-600 text-white px-6 py-3 flex items-center justify-between">
        <span>{{ session('error') }}</span>
        <button @click="show = false">&times;</button>
    </div>
    @endif

    <main class="flex-1 p-6 bg-gray-900">
        @yield('content')
    </main>
</div>

<script>
document.addEventListener('alpine:init', () => {
});
@yield('scripts')
</script>
</body>
</html>
