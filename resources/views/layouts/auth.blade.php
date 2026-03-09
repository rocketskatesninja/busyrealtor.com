<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login') — BusyRealtor</title>
    <script>
        (function() {
            if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            }
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                e.matches ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark');
            });
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .dark .bg-white       { background-color: #1e293b !important; }
        .dark .bg-gray-50     { background-color: #0f172a !important; }
        .dark .border-gray-300 { border-color: #475569 !important; }
        .dark .text-gray-700  { color: #cbd5e1 !important; }
        .dark .text-gray-600  { color: #94a3b8 !important; }
        .dark .text-gray-800  { color: #e2e8f0 !important; }
        .dark input           { background-color: #0f172a !important; color: #f1f5f9 !important; }
        .dark .shadow-2xl     { box-shadow: 0 8px 30px rgba(0,0,0,0.7) !important; }
        .dark .google-btn     { background-color: #1e293b; border-color: #475569; color: #cbd5e1; }
        .dark .google-btn:hover { background-color: #334155 !important; border-color: #64748b; }
    </style>
</head>
<body class="bg-white dark:bg-gray-50 min-h-screen flex flex-col">
    {{-- Flash banners --}}
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" class="bg-red-100 border-l-4 border-red-700 text-red-700 p-4 dark:bg-red-600 dark:border-red-800 dark:text-white">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <span>{{ session('error') }}</span>
            <button @click="show = false" class="ml-4 opacity-70 hover:opacity-100">&times;</button>
        </div>
    </div>
    @endif
    @if(session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 dark:bg-green-600 dark:border-green-600 dark:text-white">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <span>{{ session('status') }}</span>
            <button @click="show = false" class="ml-4 opacity-70 hover:opacity-100">&times;</button>
        </div>
    </div>
    @endif

    {{-- Centered card --}}
    <div class="flex-1 flex items-center justify-center py-12 px-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <a href="/" class="text-3xl font-bold text-gray-900 dark:text-white">BusyRealtor</a>
                <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">Real Estate Management Platform</p>
            </div>
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
