@extends('layouts.tenant')
@section('show_footer')@endsection
@section('title', $settings->site_title ?? 'Home')

@section('head')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "RealEstateAgent",
  "name": {!! json_encode($settings->site_title ?? '') !!},
  "url": "{{ url('/' . $tenant->slug . '/') }}"
  @if(!empty($settings->logo_image))
  ,"logo": "{{ asset('storage/' . $settings->logo_image) }}"
  @endif
}
</script>
@endsection

@section('styles')

        /* ═══════════════════ ANIMATIONS ═══════════════════ */
        @keyframes heroIn {
            from { opacity: 0; transform: translateY(36px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes bobFloat {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-14px); }
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
        /* Dot-grid hero texture */
        .hero-dot-grid {
            background-image: radial-gradient(circle, rgba(255,255,255,0.13) 1px, transparent 1px);
            background-size: 30px 30px;
        }
        /* Ken Burns */
        @keyframes kenBurns {
            0%   { transform: scale(1)    translateX(0)      translateY(0); }
            33%  { transform: scale(1.06) translateX(-1%)    translateY(-0.5%); }
            66%  { transform: scale(1.04) translateX(1.5%)   translateY(0.5%); }
            100% { transform: scale(1.08) translateX(0)      translateY(-1%); }
        }
        .hero-ken-burns { animation: kenBurns 22s ease-in-out infinite alternate; }
        /* Particles */
        @keyframes particleRise {
            0%   { transform: translateY(0)   translateX(0);   opacity: 0; }
            8%   { opacity: 1; }
            92%  { opacity: 0.5; }
            100% { transform: translateY(-90vh) translateX(30px); opacity: 0; }
        }
        .hero-particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            pointer-events: none;
            animation: particleRise linear infinite;
        }
@endsection

@php
$account = $tenant->slug;
$heroEffects = array_merge([
    'entrance_animation' => true,
    'dot_grid'           => true,
    'dark_overlay'       => true,
    'overlay_opacity'    => 45,
    'cta_glow'           => true,
    'scroll_cue'         => true,
    'parallax'           => false,
    'ken_burns'          => false,
    'particles'          => false,
], (array)($settings->hero_effects ?? []));
$ea = $heroEffects['entrance_animation'] ?? true;
$sections = $settings->homepage_sections ?? [
    ['key' => 'hero', 'enabled' => true, 'order' => 0],
    ['key' => 'features', 'enabled' => true, 'order' => 1],
    ['key' => 'listings', 'enabled' => true, 'order' => 2],
    ['key' => 'stats', 'enabled' => true, 'order' => 3],
    ['key' => 'services', 'enabled' => false, 'order' => 4],
    ['key' => 'team', 'enabled' => true, 'order' => 5],
    ['key' => 'agent', 'enabled' => true, 'order' => 6],
    ['key' => 'testimonials', 'enabled' => false, 'order' => 7],
    ['key' => 'faq', 'enabled' => true, 'order' => 8],
    ['key' => 'contact', 'enabled' => true, 'order' => 9],
];
usort($sections, fn($a,$b) => ($a['order']??0) <=> ($b['order']??0));

$primaryColor = $settings->primary_color ?? '#3B82F6';
$featuresItems = $settings->features_items ?? [
    ['icon' => 'search', 'title' => 'Smart Search', 'description' => 'Advanced filters to find exactly what you need.'],
    ['icon' => 'home', 'title' => 'Quality Listings', 'description' => 'Verified properties with detailed photos and info.'],
    ['icon' => 'chat', 'title' => '24/7 Support', 'description' => 'Our AI assistant is always here to help.'],
    ['icon' => 'shield', 'title' => 'Trusted Service', 'description' => 'Licensed professionals dedicated to you.'],
];
$statsItems = $settings->stats_items ?? [
    ['value' => '500+', 'label' => 'Properties Sold'],
    ['value' => '15+', 'label' => 'Years Experience'],
    ['value' => '98%', 'label' => 'Client Satisfaction'],
    ['value' => '24/7', 'label' => 'Support Available'],
];
$testimonialsItems = $settings->testimonials_items ?? [
    ['name' => 'Sarah Johnson', 'text' => 'Working with this team made buying our home a breeze. Professional, knowledgeable, and always available.', 'rating' => 5],
    ['name' => 'Michael Chen', 'text' => 'Sold our house in two weeks! Excellent marketing strategy and got above asking price.', 'rating' => 5],
    ['name' => 'Emily Rodriguez', 'text' => 'From first meeting to closing day, everything was handled smoothly. Great communication throughout.', 'rating' => 5],
];
$faqItems = $settings->faq_items ?? [
    ['question' => 'How do I schedule a property viewing?', 'answer' => 'Click "Schedule Viewing" on any property listing, or contact us directly. We offer flexible viewing times including evenings and weekends.'],
    ['question' => 'What areas do you serve?', 'answer' => 'We serve the entire metropolitan area and surrounding communities. Contact us to discuss your specific location needs.'],
    ['question' => 'How long does the buying process take?', 'answer' => 'Typically 30-45 days from offer acceptance to closing. We guide you through every step.'],
    ['question' => 'Do you help first-time home buyers?', 'answer' => 'Absolutely! We specialize in first-time buyers and will explain everything clearly, help you understand financing, and ensure confidence every step of the way.'],
];
$servicesItems = $settings->services_items ?? [
    ['icon' => 'home', 'title' => 'Buying a Home', 'description' => 'We help you find your perfect home with personalized search and expert guidance.'],
    ['icon' => 'dollar', 'title' => 'Selling Your Property', 'description' => 'Get the best price with our marketing expertise and network.'],
    ['icon' => 'shield', 'title' => 'Property Management', 'description' => 'Professional management services for your rental properties.'],
];
$iconPaths = [
    'search' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    'home'   => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    'chat'   => 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z',
    'shield' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    'dollar' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    'star'   => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
];
@endphp

@section('content')
@foreach($sections as $section)
@if(!($section['enabled'] ?? true)) @continue @endif
@php $key = $section['key'] ?? ''; @endphp

{{-- HERO --}}
@if($key === 'hero')
<section id="hero-section" class="relative min-h-screen flex items-center justify-center overflow-hidden">
    {{-- Background layer — separate div so Ken Burns zoom doesn't scale the text --}}
    @php
        $heroBg = '';
        $heroType = $settings->hero_background_type ?? 'preset';
        if ($heroType === 'preset') {
            $preset = $settings->hero_preset ?? 'modern-home';
            $heroBg = "background: url('/assets/images/hero-presets/{$preset}.jpg') center/cover no-repeat;";
        } elseif ($heroType === 'gradient') {
            $heroBg = "background: linear-gradient(135deg, " . ($settings->hero_gradient_start ?? '#1e3a5f') . ", " . ($settings->hero_gradient_end ?? '#7c3aed') . ");";
        } elseif ($heroType === 'image' && $settings->hero_image) {
            $heroBg = "background: url('" . asset('storage/'.$settings->hero_image) . "') center/cover no-repeat;";
        } else {
            $preset = $settings->hero_preset ?? 'modern-home';
            $heroBg = "background: url('/assets/images/hero-presets/{$preset}.jpg') center/cover no-repeat;";
        }
    @endphp
    <div id="hero-bg" class="absolute inset-0 {{ ($heroEffects['ken_burns'] ?? false) ? 'hero-ken-burns' : '' }}" style="{{ $heroBg }}"></div>

    {{-- Dark overlay --}}
    @if($heroEffects['dark_overlay'] ?? true)
    <div class="absolute inset-0 pointer-events-none" style="background:rgba(0,0,0,{{ number_format(($heroEffects['overlay_opacity'] ?? 45)/100, 2) }});"></div>
    @endif

    {{-- Dot-grid texture --}}
    @if($heroEffects['dot_grid'] ?? true)
    <div class="absolute inset-0 hero-dot-grid pointer-events-none"></div>
    @endif

    {{-- Particles --}}
    @if($heroEffects['particles'] ?? false)
    <div id="hero-particles" class="absolute inset-0 overflow-hidden pointer-events-none z-[1]"></div>
    @endif

    {{-- Main content --}}
    <div class="relative z-10 text-center px-4 max-w-5xl mx-auto py-24">

        {{-- Trust badge --}}
        <div class="inline-flex items-center gap-2.5 bg-white/10 backdrop-blur border border-white/20 rounded-full px-5 py-2 text-white/90 text-sm font-medium mb-8 {{ $ea ? 'hero-animate hero-d1' : '' }}">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
            </span>
            Trusted Real Estate Experts
        </div>

        {{-- Headline --}}
        <h1 class="text-5xl md:text-7xl font-extrabold text-white mb-6 leading-tight drop-shadow-2xl {{ $ea ? 'hero-animate hero-d2' : '' }}">
            {{ $settings->hero_title ?? 'Find Your Dream Home' }}
        </h1>

        {{-- Subtitle --}}
        <p class="text-xl md:text-2xl text-white/85 mb-10 max-w-2xl mx-auto leading-relaxed {{ $ea ? 'hero-animate hero-d3' : '' }}">
            {{ $settings->hero_subtitle ?? 'Professional real estate services to help you buy, sell, or rent the perfect property.' }}
        </p>

        {{-- CTAs --}}
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-10 {{ $ea ? 'hero-animate hero-d4' : '' }}">
            <a href="{{ route('tenant.gallery', $account) }}"
               class="btn-primary {{ ($heroEffects['cta_glow'] ?? true) ? 'btn-glow-blue' : '' }} px-9 py-4 rounded-xl font-bold text-lg shadow-2xl hover:opacity-90 transition-all duration-200 hover:scale-105">
                View Gallery
            </a>
            <a href="{{ route('tenant.map', $account) }}"
               class="bg-white/15 backdrop-blur-sm text-white border border-white/30 px-9 py-4 rounded-xl font-bold text-lg hover:bg-white/25 transition-all duration-200 hover:scale-105">
                View Map
            </a>
        </div>

        {{-- Search form --}}
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl p-4 shadow-2xl max-w-3xl mx-auto {{ $ea ? 'hero-animate hero-d5' : '' }} ring-1 ring-white/10">
            <form action="{{ route('tenant.gallery', $account) }}" method="GET" class="flex flex-col sm:flex-row sm:flex-wrap gap-3 sm:items-end">
                <div class="flex-1 sm:min-w-40">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                    <input type="text" name="search" placeholder="Address, city, zip..." class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex gap-3 sm:contents">
                    <div class="flex-1 sm:w-36 sm:flex-none">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                        <select name="type" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Types</option>
                            <option value="house">House</option>
                            <option value="condo">Condo</option>
                            <option value="townhouse">Townhouse</option>
                            <option value="land">Land</option>
                            <option value="commercial">Commercial</option>
                        </select>
                    </div>
                    <div class="flex-1 sm:w-32 sm:flex-none">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Max Price</label>
                        <select name="max_price" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Any Price</option>
                            <option value="250000">$250K</option>
                            <option value="500000">$500K</option>
                            <option value="750000">$750K</option>
                            <option value="1000000">$1M</option>
                            <option value="2000000">$2M+</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-primary w-full sm:w-auto px-6 py-2.5 rounded-lg font-semibold text-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Search
                </button>
            </form>
        </div>

        {{-- Social proof row --}}
        <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 mt-7 text-white/65 text-sm {{ $ea ? 'hero-animate hero-d5' : '' }}" style="animation-delay:0.85s">
            <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Locally Owned &amp; Operated</span>
            </div>
            <div class="hidden sm:block w-px h-4 bg-white/20"></div>
            <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Licensed &amp; Insured
            </span>
            <div class="hidden sm:block w-px h-4 bg-white/20"></div>
            <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Same-Day Response
            </span>
        </div>
    </div>

    {{-- Scroll cue --}}
    @if($heroEffects['scroll_cue'] ?? true)
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 flex flex-col items-center gap-1 text-white/35 select-none pointer-events-none">
        <svg class="w-6 h-6 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>
    @endif
</section>

{{-- FEATURES --}}
@elseif($key === 'features')
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 reveal">Why Choose Us</h2>
            <p class="text-gray-500 max-w-2xl mx-auto">Everything you need to find, buy, or sell your perfect property.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach($featuresItems as $item)
            <div class="text-center group reveal" style="transition-delay: {{ $loop->index * 0.1 }}s">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5 transition-transform group-hover:scale-110" style="background-color: rgba(var(--primary-rgb), 0.1)">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary)">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPaths[$item['icon'] ?? 'star'] ?? $iconPaths['star'] }}"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $item['title'] ?? '' }}</h3>
                <p class="text-gray-500 text-sm leading-relaxed">{{ $item['description'] ?? '' }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- LISTINGS --}}
@elseif($key === 'listings')
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2 reveal">Featured Listings</h2>
            <p class="text-gray-500">Handpicked properties just for you</p>
        </div>
        @if($featured->count())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($featured as $property)
            <a href="{{ route('tenant.property', [$account, $property->id]) }}" class="bg-white rounded-2xl overflow-hidden shadow border border-gray-200 hover:shadow-xl transition-shadow group reveal" style="transition-delay: {{ $loop->index * 0.1 }}s">
                <div class="relative h-52 bg-gray-200 overflow-hidden">
                    @if($property->primaryImage)
                        <img src="{{ asset('storage/' . $property->primaryImage->image_path) }}" alt="{{ $property->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </div>
                    @endif
                    <span class="absolute top-3 left-3 text-xs font-semibold text-white px-3 py-1 rounded-full" style="background-color: {{ $property->listing_status === 'active' ? '#10b981' : ($property->listing_status === 'pending' ? '#f59e0b' : '#6b7280') }}">
                        {{ ucfirst($property->listing_status) }}
                    </span>
                </div>
                <div class="p-5">
                    <p class="text-2xl font-bold mb-1" style="color: var(--primary)">${{ number_format($property->price) }}</p>
                    <h3 class="font-semibold text-gray-800 mb-1 truncate">{{ $property->title }}</h3>
                    <p class="text-gray-500 text-sm mb-3 truncate">{{ $property->address }}{{ $property->city ? ', ' . $property->city : '' }}</p>
                    <div class="flex items-center gap-4 text-sm text-gray-500 border-t pt-3">
                        @if($property->bedrooms) <span>{{ $property->bedrooms }} bed</span> @endif
                        @if($property->bathrooms) <span>{{ $property->bathrooms }} bath</span> @endif
                        @if($property->sqft) <span>{{ number_format($property->sqft) }} sqft</span> @endif
                        <span class="ml-auto text-xs capitalize text-gray-400">{{ str_replace('-', ' ', $property->property_type) }}</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        <div class="text-center mt-10">
            <a href="{{ route('tenant.gallery', $account) }}" class="btn-primary px-8 py-3 rounded-xl font-semibold inline-block hover:opacity-90 transition">View All Properties</a>
        </div>
        @else
        <div class="text-center py-16 text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <p class="text-lg font-medium">No featured listings yet</p>
            <p class="text-sm mt-1">Check back soon or <a href="{{ route('tenant.gallery', $account) }}" class="hover-primary">browse all properties</a>.</p>
        </div>
        @endif
    </div>
</section>

{{-- STATS --}}
@elseif($key === 'stats')
<section class="py-16" style="background-color: var(--primary)">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-white text-center">
            @foreach($statsItems as $stat)
            <div x-data="{ count: 0, target: '{{ $stat['value'] ?? '0' }}' }" x-intersect="count = target">
                <div class="text-4xl md:text-5xl font-bold mb-2 count-up" x-text="target">{{ $stat['value'] }}</div>
                <div class="text-white/80 font-medium">{{ $stat['label'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- SERVICES --}}
@elseif($key === 'services')
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 reveal">Our Services</h2>
            <p class="text-gray-500 max-w-2xl mx-auto">Comprehensive real estate services tailored to your needs.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($servicesItems as $item)
            <div class="bg-white rounded-2xl p-8 shadow border border-gray-200 hover:shadow-lg transition-shadow text-center group reveal" style="transition-delay: {{ $loop->index * 0.12 }}s">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5 transition-all group-hover:scale-110" style="background-color: rgba(var(--primary-rgb), 0.1)">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary)">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPaths[$item['icon'] ?? 'star'] ?? $iconPaths['star'] }}"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-3">{{ $item['title'] ?? '' }}</h3>
                <p class="text-gray-500 leading-relaxed">{{ $item['description'] ?? '' }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- TEAM --}}
@elseif($key === 'team')
@if($staff->count())
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 reveal">Meet Our Team</h2>
            <p class="text-gray-500 max-w-2xl mx-auto">Experienced professionals dedicated to your real estate success.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($staff as $member)
            <div class="bg-white rounded-2xl p-6 text-center shadow border border-gray-200 hover:shadow-lg transition-shadow reveal" style="transition-delay: {{ $loop->index * 0.12 }}s">
                <div class="w-24 h-24 rounded-full mx-auto mb-4 overflow-hidden bg-gray-100">
                    @if($member->photo_url)
                        <img src="{{ asset('storage/' . $member->photo_url) }}" alt="{{ $member->name }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center" style="background-color: rgba(var(--primary-rgb), 0.1)">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                    @endif
                </div>
                <h3 class="font-bold text-gray-800 text-lg mb-1">{{ $member->name }}</h3>
                @if($member->title) <p class="text-sm font-medium mb-2" style="color: var(--primary)">{{ $member->title }}</p> @endif
                @if($member->bio) <p class="text-gray-500 text-sm leading-relaxed mb-4">{{ Str::limit($member->bio, 120) }}</p> @endif
                <a href="{{ route('tenant.gallery', $account) }}" class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg text-sm font-medium border transition hover:opacity-90" style="color: var(--primary); border-color: var(--primary)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    View Listings
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- AGENT SPOTLIGHT --}}
@elseif($key === 'agent')
@if($settings->owner_photo || $settings->owner_bio || $settings->owner_name)
<section class="py-20 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header --}}
        <div class="text-center mb-14">
            <p class="text-sm font-semibold uppercase tracking-widest mb-2" style="color:var(--primary)">Meet Your Agent</p>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">Personal Service You Can Count On</h2>
            <p class="mt-3 text-gray-500 max-w-xl mx-auto">Real estate is personal. I'm here to guide you every step of the way.</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="flex flex-col md:flex-row">

                {{-- Photo column --}}
                <div class="md:w-72 flex-shrink-0 flex items-center justify-center p-10 md:p-12" style="background-color: rgba(var(--primary-rgb), 0.06)">
                    @if($settings->owner_photo)
                    <img src="{{ asset('storage/' . $settings->owner_photo) }}"
                         alt="{{ $settings->owner_name }}"
                         class="w-44 h-44 rounded-full object-cover shadow-xl ring-4 ring-white">
                    @else
                    <div class="w-44 h-44 rounded-full flex items-center justify-center shadow-xl ring-4 ring-white bg-gray-100">
                        <svg class="w-20 h-20 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                        </svg>
                    </div>
                    @endif
                </div>

                {{-- Info column --}}
                <div class="flex-1 p-8 md:p-10 flex flex-col justify-center">
                    @if($settings->owner_name)
                    <h3 class="text-2xl md:text-3xl font-black text-gray-900">{{ $settings->owner_name }}</h3>
                    @endif
                    <p class="text-sm font-semibold mt-1" style="color:var(--primary)">{{ $settings->site_title ?? 'Licensed Real Estate Agent' }}</p>

                    @if($settings->owner_bio)
                    <p class="mt-4 text-gray-600 leading-relaxed text-base">{{ $settings->owner_bio }}</p>
                    @endif

                    {{-- Credential badges --}}
                    <div class="flex flex-wrap gap-3 mt-6">
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium bg-gray-50 border border-gray-200 text-gray-700">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                            Licensed &amp; Certified
                        </span>
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium bg-gray-50 border border-gray-200 text-gray-700">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Local Market Expert
                        </span>
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium bg-gray-50 border border-gray-200 text-gray-700">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Free Consultation
                        </span>
                    </div>

                    {{-- Social links --}}
                    @if($settings->social_facebook || $settings->social_instagram || $settings->social_linkedin || $settings->social_twitter)
                    <div class="flex items-center gap-3 mt-4">
                        @if($settings->social_facebook)
                        <a href="{{ $settings->social_facebook }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-full flex items-center justify-center bg-gray-100 hover:bg-blue-600 hover:text-white text-gray-500 transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                        </a>
                        @endif
                        @if($settings->social_instagram)
                        <a href="{{ $settings->social_instagram }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-full flex items-center justify-center bg-gray-100 hover:bg-pink-500 hover:text-white text-gray-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                        </a>
                        @endif
                        @if($settings->social_linkedin)
                        <a href="{{ $settings->social_linkedin }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-full flex items-center justify-center bg-gray-100 hover:bg-blue-700 hover:text-white text-gray-500 transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
                        </a>
                        @endif
                        @if($settings->social_twitter)
                        <a href="{{ $settings->social_twitter }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-full flex items-center justify-center bg-gray-100 hover:bg-gray-900 hover:text-white text-gray-500 transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        @endif
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- TESTIMONIALS --}}
@elseif($key === 'testimonials')
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 reveal">What Our Clients Say</h2>
            <p class="text-gray-500 max-w-2xl mx-auto">Real experiences from real people we've helped find their homes.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($testimonialsItems as $t)
            <div class="bg-gray-50 rounded-2xl p-8 border border-gray-200 reveal" style="transition-delay: {{ $loop->index * 0.12 }}s">
                <div class="flex mb-4">
                    @for($s = 0; $s < ($t['rating'] ?? 5); $s++)
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <p class="text-gray-600 leading-relaxed mb-6 italic">"{{ $t['text'] ?? '' }}"</p>
                <p class="font-semibold text-gray-800">{{ $t['name'] ?? '' }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- FAQ --}}
@elseif($key === 'faq')
<section class="py-20 bg-gray-50">
    <div class="max-w-3xl mx-auto px-4">
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h2>
            <p class="text-gray-500">Got questions? We've got answers.</p>
        </div>
        <div class="space-y-4" x-data="{ open: null }">
            @foreach($faqItems as $i => $faq)
            <div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
                <button @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                        class="w-full text-left px-6 py-5 flex items-center justify-between font-semibold text-gray-800 hover:bg-gray-50 transition-colors">
                    {{ $faq['question'] ?? '' }}
                    <svg class="w-5 h-5 text-gray-400 transition-transform flex-shrink-0 ml-4" :class="open === {{ $i }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open === {{ $i }}" x-collapse x-cloak>
                    <div class="px-6 pb-5 text-gray-600 leading-relaxed border-t pt-4">{{ $faq['answer'] ?? '' }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CONTACT --}}
@elseif($key === 'contact')
<section id="contact" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Get in Touch</h2>
            <p class="text-gray-500 max-w-2xl mx-auto">Ready to find your dream property? Contact us today.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-5xl mx-auto">
            <div>
                <h3 class="text-xl font-semibold text-gray-800 mb-6">Contact Information</h3>
                <div class="space-y-4">
                    @if($settings->contact_phone)
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: rgba(var(--primary-rgb), 0.1)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <div><p class="text-sm text-gray-500">Phone</p><p class="font-medium text-gray-800">{{ $settings->contact_phone }}</p></div>
                    </div>
                    @endif
                    @if($settings->contact_email)
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: rgba(var(--primary-rgb), 0.1)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div><p class="text-sm text-gray-500">Email</p><p class="font-medium text-gray-800">{{ $settings->contact_email }}</p></div>
                    </div>
                    @endif
                    @if($settings->address)
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: rgba(var(--primary-rgb), 0.1)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div><p class="text-sm text-gray-500">Address</p><p class="font-medium text-gray-800">{{ $settings->address }}</p></div>
                    </div>
                    @endif
                </div>
            </div>
            <div x-data="{ sent: false, sending: false, name: '', email: '', phone: '', message: '' }">
                <div x-show="!sent">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6">Send a Message</h3>
                    <form x-on:submit.prevent="
                        if (!name || !email || !message) return;
                        sending = true;
                        fetch('{{ route('tenant.api.contact', $account) }}', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                            body: JSON.stringify({ name, email, phone, message })
                        }).then(r => r.json()).then(d => { if(d.success) { sent = true; } }).catch(() => {}).finally(() => sending = false);
                    " class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                <input x-model="name" type="text" required class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition" style="--tw-ring-color: var(--primary)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input x-model="phone" type="tel" class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 transition">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input x-model="email" type="email" required class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                            <textarea x-model="message" rows="4" required class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 transition resize-none"></textarea>
                        </div>
                        <button type="submit" :disabled="sending" class="btn-primary w-full py-3 rounded-xl font-semibold transition hover:opacity-90 disabled:opacity-70">
                            <span x-show="!sending">Send Message</span>
                            <span x-show="sending" x-cloak>Sending...</span>
                        </button>
                    </form>
                </div>
                <div x-show="sent" x-cloak class="text-center py-12">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background-color: rgba(16, 185, 129, 0.1)">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h4 class="text-xl font-semibold text-gray-800 mb-2">Message Sent!</h4>
                    <p class="text-gray-500">We'll get back to you shortly.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- MAP --}}
@elseif($key === 'map')
@php
    $mapAddress  = trim($settings->contact_address ?? '');
    $officePhoto = $settings->map_office_image ?? null;
    $hasMap      = (bool) $mapAddress;
    $hasPhoto    = (bool) $officePhoto;
@endphp
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-gray-900 mb-3">Our Location</h2>
            @if($mapAddress)
            <p class="text-gray-500">{{ $mapAddress }}</p>
            @endif
        </div>

        @if($hasMap || $hasPhoto)
        <div class="{{ ($hasMap && $hasPhoto) ? 'grid grid-cols-1 md:grid-cols-2 gap-6' : '' }}">

            @if($hasMap)
            <div class="rounded-2xl overflow-hidden shadow border border-gray-200" style="height: 420px">
                <iframe width="100%" height="100%" frameborder="0" style="border:0"
                    src="https://maps.google.com/maps?q={{ urlencode($mapAddress) }}&output=embed&iwloc=&z=14"
                    allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            @endif

            @if($hasPhoto)
            <div class="rounded-2xl overflow-hidden shadow border border-gray-200" style="height: 420px">
                <img src="{{ asset('storage/' . $officePhoto) }}"
                     alt="Our office"
                     class="w-full h-full object-cover">
            </div>
            @endif

        </div>
        @else
        <div class="rounded-2xl bg-gray-50 border border-gray-200 shadow flex flex-col items-center justify-center py-20 text-center">
            <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-gray-500 mb-1 font-medium">No address configured</p>
            <p class="text-gray-400 text-sm mb-4">Add your office address in Settings &rarr; Contact to display the map.</p>
            <a href="{{ route('tenant.map', $account) }}" class="btn-primary px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">Browse Property Map</a>
        </div>
        @endif
    </div>
</section>
@endif
@endforeach

@endsection

@section('scripts')
@if($heroEffects['parallax'] ?? false)
(function () {
    const bg = document.getElementById('hero-bg');
    if (!bg) return;
    window.addEventListener('scroll', function () {
        bg.style.backgroundPositionY = 'calc(50% + ' + (window.scrollY * 0.35) + 'px)';
    }, { passive: true });
})();
@endif
@if($heroEffects['particles'] ?? false)
(function () {
    const container = document.getElementById('hero-particles');
    if (!container) return;
    for (let i = 0; i < 22; i++) {
        const el = document.createElement('div');
        el.className = 'hero-particle';
        const size = 2 + Math.random() * 4;
        el.style.cssText = [
            'left:'             + (Math.random() * 100) + '%',
            'bottom:'           + (Math.random() * 40)  + '%',
            'width:'            + size + 'px',
            'height:'           + size + 'px',
            'animation-duration:'+ (10 + Math.random() * 14) + 's',
            'animation-delay:-' + (Math.random() * 18)  + 's',
            'opacity:'          + (0.15 + Math.random() * 0.45),
        ].join(';');
        container.appendChild(el);
    }
})();
@endif
// ── Scroll reveal (Intersection Observer) ──────────────────────────────────
(function () {
    const targets = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
    if (!targets.length) return;
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });
    targets.forEach(el => observer.observe(el));
})();

// ── Count-up animation ─────────────────────────────────────────────────────
(function () {
    const els = document.querySelectorAll('.count-up');
    if (!els.length) return;
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            observer.unobserve(entry.target);
            const el = entry.target;
            const raw = el.textContent.trim();
            const num = parseFloat(raw.replace(/[^0-9.]/g, ''));
            const suffix = raw.replace(/[0-9.,]/g, '');
            if (isNaN(num)) return;
            const duration = 1600;
            const start = performance.now();
            function step(now) {
                const progress = Math.min((now - start) / duration, 1);
                const ease = 1 - Math.pow(1 - progress, 3);
                const value = Math.round(ease * num);
                el.textContent = value.toLocaleString() + suffix;
                if (progress < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        });
    }, { threshold: 0.3 });
    els.forEach(el => observer.observe(el));
})();

// ── Alpine FAQ collapse helper ─────────────────────────────────────────────
document.querySelectorAll('[x-collapse]').forEach(el => {
    el.style.overflow = 'hidden';
});
@endsection
