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
    </style>
    @yield('head')
</head>
<body class="bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased">
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
