<!DOCTYPE html>
<html lang="en" class="scroll-smooth" x-data :class="{ 'dark': $store.theme.dark }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BusyRealtor — Real Estate Websites for Modern Agents')</title>
    <meta name="description" content="@yield('description', 'Launch a stunning real estate website with AI chatbot, interactive property map, and powerful admin tools — in minutes.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <meta name="robots" content="index, follow">

    {{-- Open Graph --}}
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="{{ url()->current() }}">
    <meta property="og:title"       content="@yield('title', 'BusyRealtor — Real Estate Websites for Modern Agents')">
    <meta property="og:description" content="@yield('description', 'Launch a stunning real estate website with AI chatbot, interactive property map, and powerful admin tools — in minutes.')">
    <meta property="og:site_name"   content="BusyRealtor">
    <meta property="og:locale"      content="en_US">
    @if(!empty($settings->og_image))
    <meta property="og:image" content="{{ asset('storage/' . $settings->og_image) }}">
    <meta name="twitter:image"  content="{{ asset('storage/' . $settings->og_image) }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="@yield('title', 'BusyRealtor — Real Estate Websites for Modern Agents')">
    <meta name="twitter:description" content="@yield('description', 'Launch a stunning real estate website with AI chatbot, interactive property map, and powerful admin tools — in minutes.')">
    <script>
        (function() {
            var t = localStorage.getItem('theme');
            if (t === 'dark' || (t !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
        /* Scroll-down bounce (reusable: copy this keyframe to any layout) */
        @keyframes scrollBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(8px); }
        }
        .hero-dot-grid {
            background-image: radial-gradient(circle, rgba(255,255,255,0.12) 1px, transparent 1px);
            background-size: 28px 28px;
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

        /* Header scroll states — driven by JS adding .is-scrolled class */
        #main-header .nav-link          { color: rgba(219,234,254,1); }
        #main-header .nav-link:hover    { color: #ffffff; }
        #main-header .nav-text          { color: #ffffff; }
        #main-header .hamburger-btn     { color: #ffffff; }
        #main-header.is-scrolled .nav-link       { color: #4b5563; }
        #main-header.is-scrolled .nav-link:hover { color: #2563eb; }
        #main-header.is-scrolled .nav-text       { color: #111827; }
        #main-header.is-scrolled .hamburger-btn  { color: #4b5563; }
        .dark #main-header.is-scrolled .nav-link       { color: #d1d5db; }
        .dark #main-header.is-scrolled .nav-link:hover { color: #2563eb; }
        .dark #main-header.is-scrolled .nav-text       { color: #f1f5f9; }
        .dark #main-header.is-scrolled .hamburger-btn  { color: #d1d5db; }
        /* Stats bar — lighter in light mode, dark in dark mode */
        .stats-bar { background-color: #374151 !important; }
        .dark .stats-bar { background-color: #020617 !important; }
        .stats-bar .text-gray-400,
        .dark .stats-bar .text-gray-400 { color: #9ca3af !important; }
        /* Footer nav link hover in dark mode */
        .dark footer ul a:hover,
        .dark footer ul button:hover { color: #ffffff !important; }
    </style>
    @yield('head')
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    @yield('content')

{{-- Cookie Consent Banner --}}
<div id="cookie-banner" style="display:none">
    <div class="cookie-banner-inner">
        <div class="cookie-banner-icon">🍪</div>
        <div class="cookie-banner-text">
            <strong>We use cookies</strong>
            <span>We use cookies to improve your experience and analyze traffic. See our <a href="/privacy-policy">Privacy Policy</a>.</span>
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
.cookie-banner-text strong { font-weight: 600; color: #111827; }
.dark .cookie-banner-text strong { color: #f1f5f9; }
.cookie-banner-text span { opacity: 0.8; }
.cookie-banner-text a { text-decoration: underline; color: #2563eb; }
.cookie-banner-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }
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
    background: #2563eb;
}
.cookie-btn-accept:hover { opacity: 0.88; }
</style>
<script>
(function() {
    var consent = localStorage.getItem('cookie_consent');
    if (!consent) {
        var banner = document.getElementById('cookie-banner');
        if (banner) banner.style.display = '';
    }
    document.addEventListener('DOMContentLoaded', updateCookiePrefsLink);
})();
function cookieConsent(val) {
    localStorage.setItem('cookie_consent', val);
    var banner = document.getElementById('cookie-banner');
    if (banner) banner.style.display = 'none';
    updateCookiePrefsLink();
}
function openCookiePrefs() {
    var banner = document.getElementById('cookie-banner');
    if (banner) { banner.style.display = ''; banner.scrollIntoView({ behavior: 'smooth', block: 'end' }); }
}
function updateCookiePrefsLink() {
    var link = document.getElementById('cookie-prefs-link');
    if (!link) return;
    var c = localStorage.getItem('cookie_consent');
    if (c === 'true')  link.textContent = 'Cookie Preferences ✓';
    else if (c === 'false') link.textContent = 'Cookie Preferences ✕';
    else link.textContent = 'Cookie Preferences';
}
</script>

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

    // Header scroll: pure JS, no Alpine/Tailwind dependency
    (function() {
        var header = document.getElementById('main-header');
        if (!header) return;
        var isDark = document.documentElement.classList.contains('dark');
        function updateHeader() {
            var scrolled = window.scrollY > 40;
            header.style.backgroundColor = scrolled
                ? (isDark ? '#1e293b' : '#ffffff')
                : 'transparent';
            header.classList.toggle('shadow-md', scrolled);
            header.classList.toggle('is-scrolled', scrolled);
        }
        window.addEventListener('scroll', updateHeader, { passive: true });
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            isDark = e.matches;
            if (window.scrollY > 40) updateHeader();
        });
    })();
})();

</script>
    {{-- Floating dark mode toggle --}}
    <button @click="$store.theme.toggle()" class="fixed bottom-4 right-4 z-50 p-2.5 rounded-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 shadow-lg hover:shadow-xl transition text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white" title="Toggle dark mode">
        <svg x-show="!$store.theme.dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        <svg x-show="$store.theme.dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
    </button>
</body>
</html>
