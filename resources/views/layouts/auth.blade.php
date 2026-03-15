<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BusyRealtor — @yield('title', 'Login')</title>
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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
    @include('partials.flash')

    {{-- Centered card --}}
    <div class="flex-1 flex items-center justify-center py-12 px-4">
        <div class="w-full max-w-md">

            <div class="bg-white rounded-2xl shadow-2xl p-8">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
