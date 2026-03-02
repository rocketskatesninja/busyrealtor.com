<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BusyRealtor — Real Estate Websites for Modern Agents')</title>
    <meta name="description" content="@yield('description', 'Launch a stunning real estate website with AI chatbot, interactive property map, and powerful admin tools — in minutes.')">
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
        .gradient-text {
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-gradient {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 40%, #4338ca 100%);
        }
        .feature-icon {
            background: linear-gradient(135deg, #dbeafe, #ede9fe);
        }
        /* ═══════════════════ ANIMATIONS ═══════════════════ */
        @keyframes heroIn {
            from { opacity: 0; transform: translateY(36px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes bobFloat {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-14px); }
        }
        @keyframes blobDrift {
            0%   { transform: translate(0,0) scale(1); }
            33%  { transform: translate(28px,-32px) scale(1.07); }
            66%  { transform: translate(-18px,22px) scale(0.94); }
            100% { transform: translate(0,0) scale(1); }
        }
        @keyframes glowPulseWhite {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255,255,255,0.35); }
            50%       { box-shadow: 0 0 0 12px rgba(255,255,255,0); }
        }
        @keyframes glowPulseBlue {
            0%, 100% { box-shadow: 0 4px 14px rgba(37,99,235,0.4); }
            50%       { box-shadow: 0 4px 28px rgba(37,99,235,0.7); }
        }
        @keyframes shimmer {
            0%   { background-position: -200% center; }
            100% { background-position:  200% center; }
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.92); }
            to   { opacity: 1; transform: scale(1); }
        }
        /* Hero entrance (fires on page load) */
        .hero-animate { animation: heroIn 0.75s cubic-bezier(.22,1,.36,1) both; opacity: 0; }
        .hero-d1 { animation-delay: 0.05s; }
        .hero-d2 { animation-delay: 0.2s; }
        .hero-d3 { animation-delay: 0.35s; }
        .hero-d4 { animation-delay: 0.5s; }
        .hero-d5 { animation-delay: 0.68s; }
        /* Background blobs */
        .blob   { animation: blobDrift 11s ease-in-out infinite; }
        .blob-2 { animation: blobDrift 15s ease-in-out infinite reverse; animation-delay: -4s; }
        .blob-3 { animation: blobDrift 13s ease-in-out infinite; animation-delay: -7s; }
        /* Floating */
        .float       { animation: bobFloat 4.5s ease-in-out infinite; }
        .float-delay { animation: bobFloat 4.5s ease-in-out infinite; animation-delay: -2.25s; }
        /* CTA glow */
        .btn-glow-white { animation: glowPulseWhite 2.8s ease-in-out infinite; }
        .btn-glow-blue  { animation: glowPulseBlue  2.8s ease-in-out infinite; }
        /* Scroll reveal */
        .reveal, .reveal-left, .reveal-right, .reveal-scale {
            opacity: 0;
            transition: opacity 0.65s cubic-bezier(.22,1,.36,1), transform 0.65s cubic-bezier(.22,1,.36,1);
        }
        .reveal       { transform: translateY(28px); }
        .reveal-left  { transform: translateX(-28px); }
        .reveal-right { transform: translateX(28px); }
        .reveal-scale { transform: scale(0.92); }
        .reveal.is-visible,
        .reveal-left.is-visible,
        .reveal-right.is-visible,
        .reveal-scale.is-visible { opacity: 1; transform: none; }

        /* ═══════════════════ DARK MODE — SLATE PALETTE (matches admin) ═══ */
        .dark .bg-white       { background-color: #1e293b !important; }
        .dark .bg-gray-50     { background-color: #0f172a !important; }
        .dark .bg-gray-100    { background-color: #1e293b !important; }
        .dark .bg-gray-200    { background-color: #334155 !important; }
        .dark .bg-gray-800    { background-color: #020617 !important; }
        .dark .bg-gray-900    { background-color: #020617 !important; }
        .dark .bg-gray-950    { background-color: #020617 !important; }
        .dark .text-gray-900  { color: #f1f5f9 !important; }
        .dark .text-gray-800  { color: #e2e8f0 !important; }
        .dark .text-gray-700  { color: #cbd5e1 !important; }
        .dark .text-gray-600  { color: #94a3b8 !important; }
        .dark .text-gray-500  { color: #64748b !important; }
        .dark .text-gray-400  { color: #475569 !important; }
        .dark .border-gray-50  { border-color: #1e293b !important; }
        .dark .border-gray-100 { border-color: #1e293b !important; }
        .dark .border-gray-200 { border-color: #334155 !important; }
        .dark .border-gray-300 { border-color: #475569 !important; }
        .dark .border-t, .dark .border-b,
        .dark .border-l, .dark .border-r,
        .dark .border         { border-color: #334155; }
        .dark .divide-y > * + *, .dark .divide-x > * + * { border-color: #334155; }
        .dark .hover\:bg-gray-50:hover  { background-color: #1e293b !important; }
        .dark .hover\:bg-gray-100:hover { background-color: #334155 !important; }
        .dark .hover\:bg-gray-200:hover { background-color: #475569 !important; }
        .dark .hover\:bg-blue-50:hover  { background-color: rgba(59,130,246,0.15) !important; }
        .dark .bg-blue-50     { background-color: rgba(59,130,246,0.12) !important; }
        .dark .bg-blue-100    { background-color: rgba(59,130,246,0.15) !important; }
        .dark .bg-indigo-50   { background-color: rgba(99,102,241,0.12) !important; }
        .dark .bg-violet-50   { background-color: rgba(139,92,246,0.12) !important; }
        .dark .bg-purple-50   { background-color: rgba(168,85,247,0.12) !important; }
        .dark .bg-sky-50      { background-color: rgba(14,165,233,0.12)  !important; }
        .dark .bg-emerald-50  { background-color: rgba(16,185,129,0.12)  !important; }
        .dark .bg-orange-50   { background-color: rgba(249,115,22,0.12)  !important; }
        .dark .bg-rose-50     { background-color: rgba(244,63,94,0.12)   !important; }
        .dark .bg-green-50    { background-color: rgba(16,185,129,0.12)  !important; }
        .dark .bg-red-100     { background-color: rgba(239,68,68,0.15)   !important; }
        .dark .text-blue-700  { color: #93c5fd !important; }
        .dark .text-blue-800  { color: #bfdbfe !important; }
        .dark .shadow-sm  { box-shadow: 0 1px 2px rgba(0,0,0,0.5) !important; }
        .dark .shadow     { box-shadow: 0 1px 6px rgba(0,0,0,0.5) !important; }
        .dark .shadow-lg  { box-shadow: 0 4px 20px rgba(0,0,0,0.6) !important; }
        .dark .shadow-xl  { box-shadow: 0 8px 30px rgba(0,0,0,0.7) !important; }
        .dark .shadow-2xl { box-shadow: 0 12px 40px rgba(0,0,0,0.8) !important; }
    </style>
    @yield('head')
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    @yield('content')
<script>
(function () {
    // Scroll reveal
    var ro = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('is-visible'); ro.unobserve(e.target); }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -36px 0px' });
    document.querySelectorAll('.reveal,.reveal-left,.reveal-right,.reveal-scale')
            .forEach(function (el) { ro.observe(el); });

    // Count-up animation
    function countUp(el) {
        var raw = el.dataset.target || el.textContent.trim();
        var m = raw.match(/^([^0-9]*)([0-9][0-9,]*)(\+?)(.*)$/);
        if (!m) return;
        var pre = m[1], numStr = m[2].replace(/,/g,''), plus = m[3], suf = m[4];
        var target = parseInt(numStr, 10);
        if (isNaN(target)) return;
        var dur = 1800, t0 = performance.now();
        (function tick(now) {
            var p = Math.min((now - t0) / dur, 1);
            var ease = 1 - Math.pow(1 - p, 3);
            el.textContent = pre + Math.round(ease * target).toLocaleString() + (p >= 1 ? plus : '') + suf;
            if (p < 1) requestAnimationFrame(tick);
            else el.textContent = raw;
        })(t0);
    }
    var co = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { countUp(e.target); co.unobserve(e.target); }
        });
    }, { threshold: 0.5 });
    document.querySelectorAll('.count-up').forEach(function (el) {
        el.dataset.target = el.textContent.trim(); co.observe(el);
    });
})();
</script>
</body>
</html>
