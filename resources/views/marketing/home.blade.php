@extends('layouts.marketing')
@section('title', 'BusyRealtor — Real Estate Websites for Modern Agents')
@section('description', 'Real estate website builder for agents & brokers. Launch in minutes with AI chatbot, property listings, interactive map, and appointment booking. Free trial.')

@section('head')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@graph": [
    {
      "@@type": "SoftwareApplication",
      "name": "BusyRealtor",
      "url": "https://busyrealtor.com",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "Windows, macOS, iOS, Android, Web",
      "description": "Real estate website builder for agents and brokers. Launch a beautiful website with AI chatbot, property listings, interactive map, and appointment booking.",
      "offers": [
        {
          "@@type": "Offer",
          "name": "Starter",
          "price": "{{ number_format($settings->starter_price ?? 29, 2) }}",
          "priceCurrency": "USD",
          "availability": "https://schema.org/InStock",
          "url": "https://busyrealtor.com/register",
          "description": "Up to 10 active listings, public website, contact forms, admin dashboard, custom branding."
        },
        {
          "@@type": "Offer",
          "name": "Pro",
          "price": "{{ number_format($settings->pro_price ?? 59, 2) }}",
          "priceCurrency": "USD",
          "availability": "https://schema.org/InStock",
          "url": "https://busyrealtor.com/register",
          "description": "Unlimited listings, AI chatbot, appointment scheduling, Google Maps & Analytics, staff management."
        }
      ]
    },
    {
      "@@type": "Organization",
      "name": "BusyRealtor",
      "url": "https://busyrealtor.com",
      "description": "SaaS platform providing real estate websites for agents and brokers."
    }
  ]
}
</script>
@endsection

@section('content')

{{-- ═══════════════════════════════════════════════════════════ NAV ═══ --}}
<header id="main-header"
        class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
        style="background-color:transparent">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="/" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-orange-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                </div>
                <span style="font-size:1.875rem;font-weight:800;line-height:1;"><span style="color:#7dd3fc;">Busy</span><span style="color:#fb923c;">Realtor</span></span>
            </a>
            <nav class="hidden md:flex items-center gap-8">
                <a href="#features" class="nav-link text-sm font-medium transition-colors">Features</a>
                <a href="#demo" class="nav-link text-sm font-medium transition-colors">Demo</a>
                <a href="#pricing" class="nav-link text-sm font-medium transition-colors">Pricing</a>
            </nav>
            <div class="hidden md:flex items-center gap-3">
                <a href="/login" class="nav-link text-sm font-medium transition-colors">Sign In</a>
                <a href="/register" class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors shadow-sm">Get Started Free</a>
            </div>
            <button id="marketing-hamburger" onclick="marketingNavToggle()" class="hamburger-btn md:hidden p-2">
                <svg id="marketing-icon-open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg id="marketing-icon-close" class="w-6 h-6" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
    <div id="marketing-mobile-menu" style="display:none" class="md:hidden bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 shadow-lg">
        <nav class="px-4 py-3 space-y-1">
            <a href="#features" onclick="marketingNavClose()" class="flex items-center px-3 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 font-medium">
                <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Features
            </a>
            <a href="#demo" onclick="marketingNavClose()" class="flex items-center px-3 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 font-medium">
                <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Demo
            </a>
            <a href="#pricing" onclick="marketingNavClose()" class="flex items-center px-3 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 font-medium">
                <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Pricing
            </a>
            <div class="border-t my-2"></div>
            <a href="/login" onclick="marketingNavClose()" class="flex items-center px-3 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 font-medium">
                <svg class="w-5 h-5 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                Sign In
            </a>
            <a href="/register" onclick="marketingNavClose()" class="flex items-center px-3 py-2 rounded-lg text-white bg-orange-500 hover:bg-orange-600 font-semibold">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                Get Started Free
            </a>
        </nav>
    </div>
</header>
<script>
function marketingNavToggle() {
    var menu  = document.getElementById('marketing-mobile-menu');
    var open  = menu.style.display === 'none' || menu.style.display === '';
    menu.style.display = open ? 'block' : 'none';
    document.getElementById('marketing-icon-open').style.display  = open ? 'none'  : '';
    document.getElementById('marketing-icon-close').style.display = open ? '' : 'none';
}
function marketingNavClose() {
    document.getElementById('marketing-mobile-menu').style.display = 'none';
    document.getElementById('marketing-icon-open').style.display  = '';
    document.getElementById('marketing-icon-close').style.display = 'none';
}
document.addEventListener('click', function(e) {
    var header = document.getElementById('main-header');
    if (header && !header.contains(e.target)) marketingNavClose();
});
</script>

{{-- ══════════════════════════════════════════════════════════ HERO ═══ --}}
<section class="min-h-screen flex items-center pt-16 pb-12 overflow-hidden relative" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 40%, #0369a1 75%, #0ea5e9 100%);">
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-500 opacity-10 rounded-full blur-3xl blob"></div>
        <div class="absolute -bottom-20 -left-20 w-72 h-72 bg-indigo-400 opacity-10 rounded-full blur-3xl blob-2"></div>
        <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-blue-300 opacity-5 rounded-full blur-3xl blob-3"></div>
        <div class="absolute inset-0 hero-dot-grid pointer-events-none"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <div class="inline-flex items-center gap-2 bg-blue-800/50 border border-blue-500/30 text-blue-200 text-xs font-semibold px-3 py-1.5 rounded-full mb-6 hero-animate hero-d1">
                    <span class="w-1.5 h-1.5 bg-orange-400 rounded-full animate-pulse"></span>
                    A Punchlist Labs Product
                </div>
                <h1 class="text-5xl sm:text-6xl font-black text-white leading-tight mb-6 hero-animate hero-d2">Real Estate Websites for <span style="background: linear-gradient(135deg, #fdba74, #f97316); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Modern Agents</span></h1>
                <p class="text-blue-100 text-xl leading-relaxed mb-8 max-w-lg hero-animate hero-d3">
                    Launch a stunning, customizable real estate website for agents and brokers — AI chatbot, interactive map search, and a full admin suite included. No developers, no headaches.
                </p>
                <div class="flex flex-wrap gap-4 mb-6 hero-animate hero-d4">
                    <a href="/register" class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white font-bold px-7 py-4 rounded-xl shadow-lg transition-colors text-base">
                        Start Free Trial
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <a href="/demo-realty" target="_blank" class="inline-flex items-center gap-2 text-white font-semibold px-7 py-4 rounded-xl hover:bg-white/10 transition-colors text-base" style="border: 2px solid rgba(147,197,253,0.4);">
                        View Live Demo
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
                <p class="text-blue-300 text-sm hero-animate hero-d5">14-day free trial &nbsp;·&nbsp; No credit card required &nbsp;·&nbsp; Cancel anytime</p>
            </div>
            <div class="hidden lg:block">
                <div class="rounded-2xl overflow-hidden shadow-2xl" style="border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.05); backdrop-filter: blur(4px);">
                    <div class="flex items-center gap-2 px-4 py-3" style="background: rgba(0,0,0,0.3);">
                        <div class="flex gap-1.5">
                            <div class="w-3 h-3 rounded-full" style="background:#ef4444;opacity:0.7;"></div>
                            <div class="w-3 h-3 rounded-full" style="background:#eab308;opacity:0.7;"></div>
                            <div class="w-3 h-3 rounded-full" style="background:#22c55e;opacity:0.7;"></div>
                        </div>
                        <div class="flex-1 rounded-md px-3 py-1 text-xs font-mono ml-2" style="background:rgba(0,0,0,0.25); color:rgba(147,197,253,0.8);">
                            busyrealtor.com/demo-realty
                        </div>
                    </div>
                    <div class="relative" style="height:420px; overflow:hidden;">
                        <iframe src="/demo-realty"
                                class="border-0"
                                style="width:138.89%; height:138.89%; transform:scale(0.72); transform-origin:top left; pointer-events:none;"
                                loading="lazy" title="BusyRealtor Demo"></iframe>
                        <a href="/demo-realty" target="_blank"
                           class="absolute inset-0 flex items-end justify-center pb-5"
                           style="background: linear-gradient(to top, rgba(30,58,138,0.7) 0%, transparent 50%);">
                            <span class="bg-white text-orange-600 font-bold text-sm px-4 py-2 rounded-lg shadow-lg flex items-center gap-2">
                                Open Full Demo
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════════════ STATS BAR ═══ --}}
<section class="bg-gray-900 text-white py-10 stats-bar">
    <div class="max-w-5xl mx-auto px-4 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
        <div><div class="text-3xl font-black text-orange-400 mb-1"><span class="count-up">10 min</span></div><div class="text-gray-400 text-sm">Average setup time</div></div>
        <div><div class="text-3xl font-black text-orange-400 mb-1">24/7</div><div class="text-gray-400 text-sm">AI chatbot availability</div></div>
        <div><div class="text-3xl font-black text-orange-400 mb-1">∞</div><div class="text-gray-400 text-sm">Listings on Pro plan</div></div>
        <div><div class="text-3xl font-black text-orange-400 mb-1">14 days</div><div class="text-gray-400 text-sm">Free trial, no card needed</div></div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════ HOW IT WORKS ═══ --}}
<section class="py-16 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-5xl mx-auto px-4">
        <div class="text-center mb-16">
            <p class="text-orange-500 font-semibold text-sm uppercase tracking-widest mb-3 reveal">Simple by design</p>
            <h2 class="text-4xl font-black text-gray-900 dark:text-white reveal" style="transition-delay:0.1s">Up and running in minutes</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-8 relative">
            @foreach([
                ['step'=>'1','data-delay'=>'0','title'=>'Create your account','desc'=>'Sign up, pick your site URL, upload your logo, and set your brand colors. Your public website goes live immediately.','icon'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['step'=>'2','data-delay'=>'0.15','title'=>'Add your listings','desc'=>'Upload photos, set pricing, and fill in property details. Let AI write compelling descriptions for you in one click.','icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['step'=>'3','data-delay'=>'0.3','title'=>'Connect with clients','desc'=>'Share your link. Clients search properties, chat with your AI assistant, and book showings — all on autopilot.','icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ] as $s)
            <div class="relative reveal" style="transition-delay: {{ $s['data-delay'] ?? '0' }}s">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-200 h-full">
                    <div class="w-12 h-12 bg-orange-500 text-white rounded-xl flex items-center justify-center font-black text-lg mb-5 shadow-md">{{ $s['step'] }}</div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-3">{{ $s['title'] }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">{{ $s['desc'] }}</p>
                </div>
                @if(!$loop->last)
                <div class="hidden md:flex absolute top-10 -right-5 z-10 items-center justify-center w-10 h-10 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full shadow-sm text-gray-400 dark:text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════ FEATURES ═══ --}}
<section id="features" class="py-16 bg-white dark:bg-gray-950">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-16">
            <p class="text-orange-500 font-semibold text-sm uppercase tracking-widest mb-3 reveal">Starter &amp; Pro</p>
            <h2 class="text-4xl font-black text-gray-900 dark:text-white mb-4 reveal" style="transition-delay:0.1s">All the tools you need to grow</h2>
            <p class="text-gray-500 dark:text-gray-400 text-lg max-w-2xl mx-auto reveal" style="transition-delay:0.2s">One platform for your entire online presence — from the client-facing website to your back-office admin tools.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @php
            $features = [
                ['icon'=>'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z','title'=>'Stunning Website','desc'=>'Hero, gallery, map, FAQ, testimonials, contact forms — all built-in and ready to customize.','bg'=>'bg-blue-50 dark:bg-blue-900/20','ic'=>'text-blue-600'],
                ['icon'=>'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z','title'=>'Property Listings','desc'=>'Upload photos, set status badges, manage details, and let buyers filter and search with ease.','bg'=>'bg-indigo-50 dark:bg-indigo-900/20','ic'=>'text-indigo-600'],
                ['icon'=>'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7','title'=>'Interactive Map','desc'=>'Google Maps with clickable property pins, live price filters, and smooth info window pop-ups.','bg'=>'bg-violet-50 dark:bg-violet-900/20','ic'=>'text-violet-600'],
                ['icon'=>'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z','title'=>'AI Chatbot','pro'=>true,'desc'=>'A 24/7 chatbot powered by Claude or OpenAI — knows your listings and answers every question.','bg'=>'bg-purple-50 dark:bg-purple-900/20','ic'=>'text-purple-600'],
                ['icon'=>'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z','title'=>'Social Media Auto-Post','pro'=>true,'desc'=>'Automatically share new listings and sold properties to Facebook and X (Twitter) the moment they go live.','bg'=>'bg-sky-50 dark:bg-sky-900/20','ic'=>'text-sky-600'],
                ['icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z','title'=>'Appointment Booking','pro'=>true,'desc'=>'Clients request showings from any listing. You confirm, manage, and track from your dashboard.','bg'=>'bg-emerald-50 dark:bg-emerald-900/20','ic'=>'text-emerald-600'],
                ['icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z','title'=>'Admin Dashboard','desc'=>'Track messages, appointments, and property values with charts and customizable stat widgets.','bg'=>'bg-orange-50 dark:bg-orange-900/20','ic'=>'text-orange-600'],
                ['icon'=>'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4','title'=>'Full Customization','desc'=>'Logo, colors, fonts, hero images, section order — make it yours without touching any code.','bg'=>'bg-rose-50 dark:bg-rose-900/20','ic'=>'text-rose-600'],
            ];
            @endphp
            @foreach($features as $f)
            <div class="bg-white dark:bg-gray-800 border border-gray-300 rounded-2xl p-6 hover:shadow-md hover:-translate-y-0.5 transition-all reveal" style="transition-delay: {{ ($loop->index % 4) * 0.1 }}s">
                <div class="flex items-start justify-between mb-4">
                    <div class="{{ $f['bg'] }} w-11 h-11 rounded-xl flex items-center justify-center dark:opacity-80">
                        <svg class="w-5 h-5 {{ $f['ic'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $f['icon'] }}"/></svg>
                    </div>
                    @if(!empty($f['pro']))
                    <span class="inline-flex items-center gap-1 text-xs font-semibold bg-orange-50 text-orange-600 border border-orange-200 px-2 py-0.5 rounded-full dark:bg-orange-900/20 dark:border-orange-700 dark:text-orange-400">Pro</span>
                    @endif
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-2">{{ $f['title'] }}</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed">{{ $f['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════ DEMO ═══ --}}
<section id="demo" class="py-16 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-xl overflow-hidden reveal-left">
                <div class="bg-gray-100 dark:bg-gray-700 px-4 py-3 flex items-center gap-2 border-b border-gray-200 dark:border-gray-600">
                    <div class="flex gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                        <div class="w-3 h-3 rounded-full bg-green-400"></div>
                    </div>
                    <div class="flex-1 bg-white dark:bg-gray-600 rounded-md px-3 py-1 text-xs text-gray-500 dark:text-gray-300 font-mono ml-2 border border-gray-200 dark:border-gray-500">busyrealtor.com/demo-realty</div>
                </div>
                <div class="relative" style="height:420px; overflow:hidden;">
                    <iframe src="/demo-realty" class="border-0"
                            style="width:138.89%; height:138.89%; transform:scale(0.72); transform-origin:top left; pointer-events:none;"
                            loading="lazy" title="Demo Site"></iframe>
                    <a href="/demo-realty" target="_blank" class="absolute inset-0"></a>
                </div>
            </div>
            <div>
                <p class="text-orange-500 font-semibold text-sm uppercase tracking-widest mb-3 reveal">Live preview</p>
                <h2 class="text-4xl font-black text-gray-900 dark:text-white mb-5 reveal" style="transition-delay:0.1s">See it in action</h2>
                <p class="text-gray-500 dark:text-gray-400 text-lg leading-relaxed mb-6 reveal" style="transition-delay:0.2s">Explore Demo Realty Group — a fully working BusyRealtor site with real listings, an interactive map, AI chatbot, and everything your future clients will experience.</p>
                <ul class="space-y-3 mb-8">
                    @foreach([
                        'Browse the property gallery with search & filters',
                        'Explore listings on the interactive map',
                        'Chat with the AI assistant',
                        'View full property detail pages',
                        'Request a showing appointment',
                    ] as $item)
                    <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        {{ $item }}
                    </li>
                    @endforeach
                </ul>
                <a href="/demo-realty" target="_blank" class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white font-bold px-6 py-3.5 rounded-xl transition-colors shadow-md">
                    Open Live Demo
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════════════ PRICING ═══ --}}
<section id="pricing" class="py-16 bg-white dark:bg-gray-950">
    <div class="max-w-4xl mx-auto px-4">
        <div class="text-center mb-16">
            <p class="text-orange-500 font-semibold text-sm uppercase tracking-widest mb-3 reveal">Simple pricing</p>
            <h2 class="text-4xl font-black text-gray-900 dark:text-white mb-4 reveal" style="transition-delay:0.1s">Pick the plan that fits</h2>
            <p class="text-gray-500 dark:text-gray-400 text-lg">Start free for 14 days. No credit card required.</p>
        </div>
        <div class="grid md:grid-cols-2 gap-8 items-start">
            <div class="border-2 border-gray-200 dark:border-gray-700 rounded-2xl p-8 reveal dark:bg-gray-900" style="transition-delay:0.1s">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Starter</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-5">Perfect for solo agents getting started online.</p>
                <div class="flex items-end gap-1 mb-6">
                    <span class="text-5xl font-black text-gray-900 dark:text-white">${{ number_format($settings->starter_price ?? 29, 2) }}</span>
                    <span class="text-gray-500 dark:text-gray-400 mb-2">/month</span>
                </div>
                <a href="/register" class="block text-center bg-gray-900 hover:bg-gray-800 text-white font-bold py-3.5 rounded-xl mb-7 transition-colors">Start Free Trial</a>
                <ul class="space-y-3">
                    @foreach(['Up to 10 active listings','Public website with gallery & map','Contact & inquiry forms','Lead capture & messaging','Admin dashboard & analytics','SMTP custom email settings','Custom branding & colors','Email support'] as $item)
                    <li class="flex items-center gap-3 text-gray-600 dark:text-gray-300 text-sm"><svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-2xl p-8 relative shadow-xl reveal dark:bg-gray-900" style="border: 2px solid #2563eb; transition-delay:0.25s">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-orange-500 text-white text-xs font-bold px-4 py-1.5 rounded-full shadow">Most Popular</div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Pro</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-5">For agents ready to scale with AI-powered tools.</p>
                <div class="flex items-end gap-1 mb-6">
                    <span class="text-5xl font-black text-blue-600">${{ number_format($settings->pro_price ?? 59, 2) }}</span>
                    <span class="text-gray-500 dark:text-gray-400 mb-2">/month</span>
                </div>
                <a href="/register" class="block text-center text-white font-bold py-3.5 rounded-xl mb-7 transition-colors shadow-md" style="background: #f97316;">Start Free Trial</a>
                <ul class="space-y-3">
                    @foreach(['Unlimited active listings','Everything in Starter','Appointment scheduling & management','AI-powered chatbot (Claude / OpenAI)','Social media auto-posting (Facebook & X)','Google Maps & Analytics integration','Staff management & profiles','Priority support'] as $item)
                    <li class="flex items-center gap-3 text-gray-600 dark:text-gray-300 text-sm"><svg class="w-4 h-4 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <p class="text-center text-gray-400 dark:text-gray-500 text-sm mt-8">All plans include a 14-day free trial with full Pro features. No credit card required to start.</p>
        <p class="text-center text-gray-400 dark:text-gray-600 text-xs mt-2">Pricing is subject to change at any time.</p>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════ FINAL CTA ═══ --}}
<section class="py-20 bg-blue-700">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <h2 class="text-4xl font-black text-white mb-4 reveal">Ready to launch your real estate website?</h2>
        <p class="text-blue-200 text-lg mb-8 reveal" style="transition-delay:0.15s">Join agents already using BusyRealtor to win more clients online.</p>
        <div class="flex flex-wrap justify-center gap-4 reveal" style="transition-delay:0.3s">
            <a href="/register" class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white font-bold px-8 py-4 rounded-xl shadow-lg transition-colors text-lg">
                Start Free Trial
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
            <a href="/demo-realty" target="_blank" class="inline-flex items-center gap-2 text-white font-semibold px-8 py-4 rounded-xl hover:bg-white/10 transition-colors text-lg" style="border: 2px solid rgba(147,197,253,0.4);">View Demo</a>
        </div>
        <p class="text-blue-300 text-sm mt-5">14-day free trial &nbsp;·&nbsp; No credit card required &nbsp;·&nbsp; Cancel anytime</p>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════ FOOTER ═══ --}}
<footer class="bg-gray-100 dark:bg-gray-950 text-gray-500 dark:text-gray-400 py-12">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid md:grid-cols-5 gap-8 mb-10">
            <div class="md:col-span-2">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-orange-500 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    </div>
                    <span style="font-size:1.875rem;font-weight:800;line-height:1;"><span style="color:#7dd3fc;">Busy</span><span style="color:#fb923c;">Realtor</span></span>
                </div>
                <p class="text-sm leading-relaxed max-w-xs">The complete real estate website platform for modern agents. Built by <a href="https://punchlistlabs.com" rel="noopener noreferrer" class="text-orange-500 dark:text-orange-400 hover:text-orange-600 dark:hover:text-orange-300 transition-colors">Punchlist Labs</a>.</p>
                @if($settings->social_facebook || $settings->social_instagram || $settings->social_x || $settings->social_linkedin || $settings->social_youtube)
                <div class="flex items-center gap-2 mt-4">
                    @if($settings->social_facebook)
                    <a href="{{ $settings->social_facebook }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="w-9 h-9 rounded-full flex items-center justify-center bg-gray-200 dark:bg-gray-800 hover:bg-blue-600 hover:text-white text-gray-500 dark:text-gray-400 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    @endif
                    @if($settings->social_instagram)
                    <a href="{{ $settings->social_instagram }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="w-9 h-9 rounded-full flex items-center justify-center bg-gray-200 dark:bg-gray-800 hover:bg-pink-500 hover:text-white text-gray-500 dark:text-gray-400 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                    @endif
                    @if($settings->social_x)
                    <a href="{{ $settings->social_x }}" target="_blank" rel="noopener noreferrer" aria-label="X" class="w-9 h-9 rounded-full flex items-center justify-center bg-gray-200 dark:bg-gray-800 hover:bg-gray-900 hover:text-white dark:hover:bg-white dark:hover:text-gray-900 text-gray-500 dark:text-gray-400 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    @endif
                    @if($settings->social_linkedin)
                    <a href="{{ $settings->social_linkedin }}" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" class="w-9 h-9 rounded-full flex items-center justify-center bg-gray-200 dark:bg-gray-800 hover:bg-blue-700 hover:text-white text-gray-500 dark:text-gray-400 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    @endif
                    @if($settings->social_youtube)
                    <a href="{{ $settings->social_youtube }}" target="_blank" rel="noopener noreferrer" aria-label="YouTube" class="w-9 h-9 rounded-full flex items-center justify-center bg-gray-200 dark:bg-gray-800 hover:bg-red-600 hover:text-white text-gray-500 dark:text-gray-400 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                    @endif
                </div>
                @endif
            </div>
            <div>
                <h4 class="text-gray-900 dark:text-white font-semibold text-sm mb-4">Product</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="#features" class="hover:text-gray-900 transition-colors">Features</a></li>
                    <li><a href="#pricing" class="hover:text-gray-900 transition-colors">Pricing</a></li>
                    <li><a href="/demo-realty" target="_blank" class="hover:text-gray-900 transition-colors">Live Demo</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-gray-900 dark:text-white font-semibold text-sm mb-4">Legal</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/privacy-policy" class="hover:text-gray-900 transition-colors">Privacy Policy</a></li>
                    <li><a href="/terms" class="hover:text-gray-900 transition-colors">Terms of Service</a></li>
                    <li><button onclick="openCookiePrefs()" class="hover:text-gray-900 transition-colors text-left" id="cookie-prefs-link">Cookie Preferences</button></li>
                </ul>
            </div>
            <div>
                <h4 class="text-gray-900 dark:text-white font-semibold text-sm mb-4">Affiliates</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="https://routepilot.pro" target="_blank" rel="noopener noreferrer" class="hover:text-gray-900 transition-colors">RoutePilot</a></li>
                    <li><a href="https://punchlistify.com" target="_blank" rel="noopener noreferrer" class="hover:text-gray-900 transition-colors">Punchlistify</a></li>
                    <li><a href="https://punchlistlabs.com" target="_blank" rel="noopener noreferrer" class="hover:text-gray-900 transition-colors">Punchlist Labs</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-800 pt-6 flex flex-col md:flex-row justify-between items-center gap-3 text-xs">
            <p>&copy; {{ date('Y') }} BusyRealtor. All rights reserved.</p>
            <p>Made with &#9749; for hard-working realtors everywhere</p>
        </div>
    </div>
</footer>

@endsection
