@extends('layouts.admin')
@section('title', 'Setup Wizard')
@section('head')
<style>
[x-cloak] { display: none !important; }
.fade-enter { animation: fadeSlideIn .3s ease-out; }
@keyframes fadeSlideIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endsection
@section('content')
@php
$account = $tenant->slug;
$faviconPresets = [
    'house','key','pin','building','shield','star','search','door','chart','leaf','fence','garage','sofa',
    'house_outline','key_outline','pin_outline','building_outline','shield_outline','star_outline',
    'search_outline','door_outline','chart_outline','leaf_outline','fence_outline','garage_outline','sofa_outline','compass','compass_outline',
];
$heroPresets = [
    'modern-home'=>'Modern Home','luxury-estate'=>'Luxury Estate','suburban'=>'Suburban',
    'countryside'=>'Countryside','cozy-interior'=>'Cozy Interior','urban-loft'=>'Urban Loft',
    'minimalist'=>'Minimalist','beach'=>'Beach','mountain'=>'Mountain',
    'desert'=>'Desert','woods'=>'Woods','river'=>'River',
    'grassland'=>'Grassland','small-town'=>'Marshland','cityscape'=>'Cityscape',
];
@endphp

<div class="max-w-3xl mx-auto px-4 pb-12"
     x-data="setupWizard()"
     x-cloak>

    {{-- Header: progress + skip --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Setup Your Site</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Step <span x-text="step"></span> of 6</p>
        </div>
        <form method="POST" action="{{ route('tenant.admin.setup.skip', $account) }}">
            @csrf
            <button type="submit"
                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 underline underline-offset-2 transition">
                Skip Setup
            </button>
        </form>
    </div>

    {{-- Progress bar --}}
    <div class="flex gap-1.5 mb-8">
        <template x-for="i in 6" :key="i">
            <div class="h-1.5 rounded-full flex-1 transition-all duration-300"
                 :class="i <= step ? 'opacity-100' : 'bg-gray-200 dark:bg-gray-700 opacity-100'"
                 :style="i <= step ? 'background-color: var(--primary)' : ''"></div>
        </template>
    </div>

    {{-- Step 1: Branding --}}
    <div x-show="step === 1" x-transition class="fade-enter">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Branding</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Choose your icon, color, and header style.</p>
            </div>

            {{-- Favicon preset --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Site Icon</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($faviconPresets as $fp)
                    <label class="cursor-pointer" @click="data.favicon_preset = '{{ $fp }}'">
                        <div class="w-12 h-12 rounded-xl border-2 flex items-center justify-center p-2 transition-all bg-white dark:bg-gray-700"
                             :style="data.favicon_preset === '{{ $fp }}'
                                 ? 'border-color: var(--primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 20%, transparent)'
                                 : 'border-color: #e5e7eb'"
                             :class="data.favicon_preset === '{{ $fp }}' ? 'scale-110' : 'hover:border-gray-300'">
                            {!! \App\Models\SiteSettings::faviconSvg($fp, $settings->primary_color ?? '#3B82F6') !!}
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Primary color --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Primary Color</label>
                <div class="flex items-center gap-3">
                    <input type="color" x-model="data.primary_color"
                           @input="document.documentElement.style.setProperty('--primary', $event.target.value)"
                           class="w-12 h-10 border-0 rounded-lg cursor-pointer p-0"
                           value="{{ $settings->primary_color ?? '#3B82F6' }}">
                    <div class="flex gap-1.5">
                        @foreach(['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6','#F97316','#6366F1','#0EA5E9'] as $swatch)
                        <button type="button"
                                @click="data.primary_color = '{{ $swatch }}'; document.documentElement.style.setProperty('--primary', '{{ $swatch }}')"
                                class="w-7 h-7 rounded-full border-2 border-white dark:border-gray-700 shadow-sm hover:scale-110 transition"
                                style="background: {{ $swatch }}" title="{{ $swatch }}"></button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Header display mode --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Header Display</label>
                <div class="grid grid-cols-3 gap-3">
                    @foreach(['text' => 'Text Only', 'logo' => 'Logo Only', 'both' => 'Logo + Text'] as $mode => $modeLabel)
                    <div class="cursor-pointer" @click="data.header_display_mode = '{{ $mode }}'">
                        <div class="flex items-center justify-center p-3 border-2 rounded-xl text-sm font-medium transition-all"
                             :style="data.header_display_mode === '{{ $mode }}'
                                 ? 'border-color: var(--primary); color: var(--primary); background: color-mix(in srgb, var(--primary) 5%, transparent)'
                                 : 'border-color: #e5e7eb; color: #6b7280'"
                             :class="data.header_display_mode !== '{{ $mode }}' && 'hover:border-gray-300'">
                            {{ $modeLabel }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Step 2: Contact Info --}}
    <div x-show="step === 2" x-transition class="fade-enter">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Contact Information</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">How visitors can reach you.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Your Name</label>
                    <input type="text" x-model="data.owner_name" placeholder="Jane Smith"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Contact Email</label>
                    <input type="email" x-model="data.contact_email" placeholder="jane@example.com"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Phone Number</label>
                    <input type="tel" x-model="data.contact_phone" placeholder="(555) 123-4567"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Office Address</label>
                    <input type="text" x-model="data.contact_address" placeholder="123 Main St, City, ST 00000"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">License Number</label>
                    <input type="text" x-model="data.license_number" placeholder="DRE #01234567"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Brokerage Name</label>
                    <input type="text" x-model="data.brokerage_name" placeholder="RE/MAX Premier"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
            </div>
        </div>
    </div>

    {{-- Step 3: Hero Section --}}
    <div x-show="step === 3" x-transition class="fade-enter">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Hero Section</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">The first thing visitors see on your homepage.</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Headline</label>
                <input type="text" x-model="data.hero_title" placeholder="Find Your Dream Home"
                       class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Subtitle</label>
                <input type="text" x-model="data.hero_subtitle" placeholder="Professional real estate services you can trust"
                       class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            </div>

            {{-- Background type --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Background</label>
                <div class="flex gap-2 flex-wrap mb-3">
                    <template x-for="bt in [{v:'preset',l:'Preset Image'},{v:'image',l:'Custom Image'},{v:'gradient',l:'Gradient'}]" :key="bt.v">
                        <button type="button"
                                @click="data.hero_background_type = bt.v"
                                :class="data.hero_background_type === bt.v
                                    ? 'text-white'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                :style="data.hero_background_type === bt.v ? 'background-color: var(--primary)' : ''"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
                                x-text="bt.l"></button>
                    </template>
                </div>

                {{-- Preset grid --}}
                <div x-show="data.hero_background_type === 'preset'" class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                    @foreach($heroPresets as $pkey => $plabel)
                    <label class="cursor-pointer relative block">
                        <input type="radio" name="hero_preset" value="{{ $pkey }}" class="sr-only peer"
                               x-model="data.hero_preset"
                               {{ ($settings->hero_preset ?? 'modern-home') === $pkey ? 'checked' : '' }}>
                        <div class="rounded-lg overflow-hidden transition border-2 border-transparent peer-checked:border-[var(--primary)] hover:border-gray-300 dark:hover:border-gray-500">
                            <img src="/assets/images/hero-presets/{{ $pkey }}.jpg" alt="{{ $plabel }}" class="w-full h-14 object-cover">
                            <div class="text-xs text-center py-0.5 bg-white dark:bg-gray-700 dark:text-gray-300 text-gray-600 truncate px-1">{{ $plabel }}</div>
                        </div>
                        <div class="absolute top-1 right-1 bg-[var(--primary)] rounded-full p-0.5 hidden peer-checked:block pointer-events-none">
                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </div>
                    </label>
                    @endforeach
                </div>

                {{-- Custom image --}}
                <div x-show="data.hero_background_type === 'image'" class="space-y-2">
                    <input type="file" x-ref="heroImageInput" accept="image/*"
                           class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-[var(--primary)]/10 file:text-[var(--primary)] hover:file:bg-[var(--primary)]/20">
                    <p class="text-xs text-gray-400">Recommended: 1920x1080px or wider. JPG or PNG.</p>
                </div>

                {{-- Gradient --}}
                <div x-show="data.hero_background_type === 'gradient'" class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Start Color</label>
                        <input type="color" x-model="data.hero_gradient_start" class="w-full h-10 border-0 rounded cursor-pointer" value="{{ $settings->hero_gradient_start ?? '#1e3a5f' }}">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">End Color</label>
                        <input type="color" x-model="data.hero_gradient_end" class="w-full h-10 border-0 rounded cursor-pointer" value="{{ $settings->hero_gradient_end ?? '#7c3aed' }}">
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Step 4: Integrations --}}
    <div x-show="step === 4" x-transition class="fade-enter">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Connect Your Services</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">All optional — you can set these up later in Settings.</p>
            </div>

            @php
                $aiInteg = $integrations->get('ai_provider');
                $aiConfig = $aiInteg?->config ?? [];
                $mapsInteg = $integrations->get('google_maps');
                $gaInteg = $integrations->get('google_analytics');
                $fbInteg = $integrations->get('facebook');
                $fbConfig = $fbInteg?->config ?? [];
                $twInteg = $integrations->get('twitter');
                $twConfig = $twInteg?->config ?? [];
            @endphp

            {{-- AI Chatbot --}}
            <div x-data="{ open: false }" class="border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden">
                <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">AI Chatbot</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Anthropic or OpenAI for visitor chat</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-collapse class="px-4 pb-4 space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Preferred Provider</label>
                        <select x-model="data.ai_preferred" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                            <option value="anthropic">Anthropic (Claude)</option>
                            <option value="openai">OpenAI (GPT)</option>
                        </select>
                    </div>
                    <div x-show="data.ai_preferred === 'anthropic'" class="space-y-2">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Anthropic API Key</label>
                        <input type="password" x-model="data.ai_anthropic_key" placeholder="sk-ant-..."
                               class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Model</label>
                        <select x-model="data.ai_anthropic_model" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                            <option value="claude-haiku-4-5-20251001">Claude Haiku 4.5 (Fast, affordable)</option>
                            <option value="claude-sonnet-4-6">Claude Sonnet 4.6 (Balanced)</option>
                            <option value="claude-opus-4-6">Claude Opus 4.6 (Most capable)</option>
                        </select>
                    </div>
                    <div x-show="data.ai_preferred === 'openai'" class="space-y-2">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">OpenAI API Key</label>
                        <input type="password" x-model="data.ai_openai_key" placeholder="sk-..."
                               class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                        <label class="block text-xs text-gray-600 dark:text-gray-400">Model</label>
                        <select x-model="data.ai_openai_model" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                            <option value="gpt-4o-mini">GPT-4o Mini (Fast, affordable)</option>
                            <option value="gpt-4o">GPT-4o (Most capable)</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Google Maps --}}
            <div x-data="{ open: false }" class="border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden">
                <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Google Maps</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Property map on your homepage</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-collapse class="px-4 pb-4">
                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Google Maps API Key</label>
                    <input type="password" x-model="data.google_maps_key" placeholder="AIza..."
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
            </div>

            {{-- Google Analytics --}}
            <div x-data="{ open: false }" class="border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden">
                <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Google Analytics</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Track visitor behavior</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-collapse class="px-4 pb-4 space-y-2">
                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Measurement ID</label>
                    <input type="text" x-model="data.ga_measurement_id" placeholder="G-XXXXXXXXXX"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                    <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 cursor-pointer">
                        <input type="checkbox" x-model="data.ga_enabled" class="rounded border-gray-300 text-[var(--primary)] focus:ring-[var(--primary)]"> Enable tracking
                    </label>
                </div>
            </div>

            {{-- Facebook --}}
            <div x-data="{ open: false }" class="border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden">
                <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Facebook</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Auto-post listings to your page</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-collapse class="px-4 pb-4 space-y-2">
                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Access Token</label>
                    <input type="password" x-model="data.fb_access_token" placeholder="EAAx..."
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Page ID</label>
                    <input type="text" x-model="data.fb_page_id" placeholder="123456789"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                    <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 cursor-pointer">
                        <input type="checkbox" x-model="data.fb_enabled" class="rounded border-gray-300 text-[var(--primary)] focus:ring-[var(--primary)]"> Enable auto-posting
                    </label>
                </div>
            </div>

            {{-- Twitter/X --}}
            <div x-data="{ open: false }" class="border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden">
                <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-900 dark:text-gray-300" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">X (Twitter)</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Auto-post listings to X</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-collapse class="px-4 pb-4 space-y-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">API Key</label>
                            <input type="password" x-model="data.tw_api_key"
                                   class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">API Secret</label>
                            <input type="password" x-model="data.tw_api_secret"
                                   class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Access Token</label>
                            <input type="password" x-model="data.tw_access_token"
                                   class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Access Token Secret</label>
                            <input type="password" x-model="data.tw_access_token_secret"
                                   class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 cursor-pointer">
                        <input type="checkbox" x-model="data.tw_enabled" class="rounded border-gray-300 text-[var(--primary)] focus:ring-[var(--primary)]"> Enable auto-posting
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 5: Add First Property --}}
    <div x-show="step === 5" x-transition class="fade-enter">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Add Your First Property</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Optional — add one listing so your site isn't empty.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Property Title <span class="text-red-400">*</span></label>
                    <input type="text" x-model="data.prop_title" placeholder="Beautiful 3BR Ranch in Sunnydale"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Property Type <span class="text-red-400">*</span></label>
                    <select x-model="data.prop_type" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                        <option value="">Select...</option>
                        <option value="house">House / Single Family</option>
                        <option value="condo">Condo</option>
                        <option value="townhouse">Townhouse</option>
                        <option value="multi_family">Multi-Family</option>
                        <option value="land">Land</option>
                        <option value="commercial">Commercial</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Price</label>
                    <input type="number" x-model="data.prop_price" placeholder="450000" min="0"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Street Address</label>
                    <input type="text" x-model="data.prop_address" placeholder="123 Oak Lane"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">City</label>
                    <input type="text" x-model="data.prop_city" placeholder="Sunnydale"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">State</label>
                        <input type="text" x-model="data.prop_state" placeholder="CA" maxlength="2"
                               class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">ZIP</label>
                        <input type="text" x-model="data.prop_zip" placeholder="90210"
                               class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Bedrooms</label>
                    <input type="number" x-model="data.prop_bedrooms" placeholder="3" min="0"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Bathrooms</label>
                    <input type="number" x-model="data.prop_bathrooms" placeholder="2" min="0" step="0.5"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Sq Ft</label>
                    <input type="number" x-model="data.prop_sqft" placeholder="1800" min="0"
                           class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Photo</label>
                    <input type="file" x-ref="propImageInput" accept="image/*"
                           class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-[var(--primary)]/10 file:text-[var(--primary)] hover:file:bg-[var(--primary)]/20">
                </div>
            </div>
        </div>
    </div>

    {{-- Step 6: Review & Launch --}}
    <div x-show="step === 6" x-transition class="fade-enter">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-5">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Review & Launch</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Here's what you've configured. You can always change things later in Settings.</p>
            </div>

            <div class="space-y-3">
                {{-- Branding --}}
                <div class="flex items-center justify-between p-3 rounded-lg" :class="completedSteps.includes(1) ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-700/50'">
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center" :class="completedSteps.includes(1) ? 'bg-green-500 text-white' : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400'">
                            <svg x-show="completedSteps.includes(1)" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span x-show="!completedSteps.includes(1)" class="text-xs font-bold">1</span>
                        </div>
                        <span class="text-sm font-medium" :class="completedSteps.includes(1) ? 'text-green-800 dark:text-green-300' : 'text-gray-500 dark:text-gray-400'">Branding</span>
                    </div>
                    <button type="button" @click="step = 1" class="text-xs text-[var(--primary)] hover:underline">Edit</button>
                </div>

                {{-- Contact --}}
                <div class="flex items-center justify-between p-3 rounded-lg" :class="completedSteps.includes(2) ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-700/50'">
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center" :class="completedSteps.includes(2) ? 'bg-green-500 text-white' : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400'">
                            <svg x-show="completedSteps.includes(2)" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span x-show="!completedSteps.includes(2)" class="text-xs font-bold">2</span>
                        </div>
                        <span class="text-sm font-medium" :class="completedSteps.includes(2) ? 'text-green-800 dark:text-green-300' : 'text-gray-500 dark:text-gray-400'">Contact Info</span>
                    </div>
                    <button type="button" @click="step = 2" class="text-xs text-[var(--primary)] hover:underline">Edit</button>
                </div>

                {{-- Hero --}}
                <div class="flex items-center justify-between p-3 rounded-lg" :class="completedSteps.includes(3) ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-700/50'">
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center" :class="completedSteps.includes(3) ? 'bg-green-500 text-white' : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400'">
                            <svg x-show="completedSteps.includes(3)" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span x-show="!completedSteps.includes(3)" class="text-xs font-bold">3</span>
                        </div>
                        <span class="text-sm font-medium" :class="completedSteps.includes(3) ? 'text-green-800 dark:text-green-300' : 'text-gray-500 dark:text-gray-400'">Hero Section</span>
                    </div>
                    <button type="button" @click="step = 3" class="text-xs text-[var(--primary)] hover:underline">Edit</button>
                </div>

                {{-- Integrations --}}
                <div class="flex items-center justify-between p-3 rounded-lg" :class="completedSteps.includes(4) ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-700/50'">
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center" :class="completedSteps.includes(4) ? 'bg-green-500 text-white' : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400'">
                            <svg x-show="completedSteps.includes(4)" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span x-show="!completedSteps.includes(4)" class="text-xs font-bold">4</span>
                        </div>
                        <span class="text-sm font-medium" :class="completedSteps.includes(4) ? 'text-green-800 dark:text-green-300' : 'text-gray-500 dark:text-gray-400'">Services</span>
                    </div>
                    <button type="button" @click="step = 4" class="text-xs text-[var(--primary)] hover:underline">Edit</button>
                </div>

                {{-- Property --}}
                <div class="flex items-center justify-between p-3 rounded-lg" :class="completedSteps.includes(5) ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-700/50'">
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center" :class="completedSteps.includes(5) ? 'bg-green-500 text-white' : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400'">
                            <svg x-show="completedSteps.includes(5)" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span x-show="!completedSteps.includes(5)" class="text-xs font-bold">5</span>
                        </div>
                        <span class="text-sm font-medium" :class="completedSteps.includes(5) ? 'text-green-800 dark:text-green-300' : 'text-gray-500 dark:text-gray-400'">First Property</span>
                    </div>
                    <button type="button" @click="step = 5" class="text-xs text-[var(--primary)] hover:underline">Edit</button>
                </div>
            </div>

            {{-- Preview link --}}
            <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                <a href="{{ route('tenant.home', $account) }}" target="_blank"
                   class="inline-flex items-center gap-2 text-sm text-[var(--primary)] hover:underline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Preview your public site
                </a>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                Visit <strong>Settings</strong> for more customization options — homepage sections, SEO, legal pages, notifications, staff, and more.
            </p>
        </div>
    </div>

    {{-- Navigation buttons --}}
    <div class="flex items-center justify-between mt-6">
        <button type="button"
                x-show="step > 1"
                @click="step--; window.scrollTo({top: 0, behavior: 'smooth'})"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium
                       border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300
                       hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back
        </button>
        <div x-show="step <= 1"></div>

        {{-- Skip this step (steps 4 & 5) --}}
        <button type="button"
                x-show="step === 4 || step === 5"
                @click="step++; window.scrollTo({top: 0, behavior: 'smooth'})"
                class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 underline underline-offset-2 transition">
            Skip this step
        </button>

        {{-- Next / Save & Continue --}}
        <button type="button"
                x-show="step < 6"
                @click="saveStep()"
                :disabled="saving"
                class="btn-primary inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-semibold transition disabled:opacity-50">
            <span x-show="!saving" x-text="step === 5 && !data.prop_title ? 'Skip & Continue' : 'Save & Continue'"></span>
            <span x-show="saving">Saving...</span>
            <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>

        {{-- Launch button (step 6) --}}
        <button type="button"
                x-show="step === 6"
                @click="launch()"
                :disabled="saving"
                class="btn-primary inline-flex items-center gap-2 px-8 py-2.5 rounded-xl text-sm font-bold transition disabled:opacity-50">
            <span x-show="!saving">Launch Site</span>
            <span x-show="saving">Launching...</span>
            <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </button>
    </div>

    {{-- Error message --}}
    <div x-show="errorMsg" x-transition
         class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm rounded-xl">
        <span x-text="errorMsg"></span>
    </div>
</div>

<script>
function setupWizard() {
    return {
        step: 1,
        saving: false,
        errorMsg: '',
        completedSteps: [],
        data: {
            // Step 1
            favicon_preset: @json($settings->favicon_preset ?? ''),
            primary_color: @json($settings->primary_color ?? '#3B82F6'),
            header_display_mode: @json($settings->header_display_mode ?? 'text'),
            // Step 2
            owner_name: @json($settings->owner_name ?? ''),
            contact_email: @json($settings->contact_email ?? ''),
            contact_phone: @json($settings->contact_phone ?? ''),
            contact_address: @json($settings->contact_address ?? ''),
            license_number: @json($settings->license_number ?? ''),
            brokerage_name: @json($settings->brokerage_name ?? ''),
            // Step 3
            hero_title: @json($settings->hero_title ?? ''),
            hero_subtitle: @json($settings->hero_subtitle ?? ''),
            hero_background_type: @json($settings->hero_background_type ?? 'preset'),
            hero_preset: @json($settings->hero_preset ?? 'modern-home'),
            hero_gradient_start: @json($settings->hero_gradient_start ?? '#1e3a5f'),
            hero_gradient_end: @json($settings->hero_gradient_end ?? '#7c3aed'),
            // Step 4 — integrations
            ai_preferred: @json($aiConfig['preferred'] ?? 'anthropic'),
            ai_anthropic_key: '',
            ai_anthropic_model: @json($aiConfig['anthropic_model'] ?? 'claude-haiku-4-5-20251001'),
            ai_openai_key: '',
            ai_openai_model: @json($aiConfig['openai_model'] ?? 'gpt-4o-mini'),
            google_maps_key: '',
            ga_measurement_id: @json($gaInteg->api_key ?? ''),
            ga_enabled: @json((bool)($gaInteg->is_active ?? false)),
            fb_access_token: '',
            fb_page_id: @json($fbConfig['page_id'] ?? ''),
            fb_enabled: @json((bool)($fbInteg->is_active ?? false)),
            tw_api_key: '',
            tw_api_secret: '',
            tw_access_token: '',
            tw_access_token_secret: '',
            tw_enabled: @json((bool)($twInteg->is_active ?? false)),
            // Step 5 — property
            prop_title: '',
            prop_type: '',
            prop_price: '',
            prop_address: '',
            prop_city: '',
            prop_state: '',
            prop_zip: '',
            prop_bedrooms: '',
            prop_bathrooms: '',
            prop_sqft: '',
        },

        async saveStep() {
            this.saving = true;
            this.errorMsg = '';

            try {
                let body;
                const headers = { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' };

                if (this.step === 3 || this.step === 5) {
                    // Use FormData for file uploads
                    body = new FormData();
                    body.append('step', this.step);

                    if (this.step === 3) {
                        ['hero_title','hero_subtitle','hero_background_type','hero_preset','hero_gradient_start','hero_gradient_end'].forEach(k => body.append(k, this.data[k] || ''));
                        const heroFile = this.$refs.heroImageInput?.files?.[0];
                        if (heroFile) body.append('hero_image', heroFile);
                    }

                    if (this.step === 5) {
                        if (!this.data.prop_title) {
                            // Skip — no property to add
                            this.step++;
                            this.saving = false;
                            window.scrollTo({top: 0, behavior: 'smooth'});
                            return;
                        }
                        body.append('title', this.data.prop_title);
                        body.append('property_type', this.data.prop_type || 'house');
                        body.append('price', this.data.prop_price);
                        body.append('address', this.data.prop_address);
                        body.append('city', this.data.prop_city);
                        body.append('state', this.data.prop_state);
                        body.append('zip', this.data.prop_zip);
                        body.append('bedrooms', this.data.prop_bedrooms);
                        body.append('bathrooms', this.data.prop_bathrooms);
                        body.append('sqft', this.data.prop_sqft);
                        const propFile = this.$refs.propImageInput?.files?.[0];
                        if (propFile) body.append('images[]', propFile);
                    }
                } else {
                    headers['Content-Type'] = 'application/json';
                    let payload = { step: this.step };

                    if (this.step === 1) {
                        Object.assign(payload, {
                            favicon_preset: this.data.favicon_preset,
                            primary_color: this.data.primary_color,
                            header_display_mode: this.data.header_display_mode,
                        });
                    } else if (this.step === 2) {
                        Object.assign(payload, {
                            owner_name: this.data.owner_name,
                            contact_email: this.data.contact_email,
                            contact_phone: this.data.contact_phone,
                            contact_address: this.data.contact_address,
                            license_number: this.data.license_number,
                            brokerage_name: this.data.brokerage_name,
                        });
                    } else if (this.step === 4) {
                        Object.assign(payload, {
                            ai_preferred: this.data.ai_preferred,
                            ai_anthropic_key: this.data.ai_anthropic_key,
                            ai_anthropic_model: this.data.ai_anthropic_model,
                            ai_openai_key: this.data.ai_openai_key,
                            ai_openai_model: this.data.ai_openai_model,
                            google_maps_key: this.data.google_maps_key,
                            ga_measurement_id: this.data.ga_measurement_id,
                            ga_enabled: this.data.ga_enabled,
                            fb_access_token: this.data.fb_access_token,
                            fb_page_id: this.data.fb_page_id,
                            fb_enabled: this.data.fb_enabled,
                            tw_api_key: this.data.tw_api_key,
                            tw_api_secret: this.data.tw_api_secret,
                            tw_access_token: this.data.tw_access_token,
                            tw_access_token_secret: this.data.tw_access_token_secret,
                            tw_enabled: this.data.tw_enabled,
                        });
                    }
                    body = JSON.stringify(payload);
                }

                const resp = await fetch(@json(route('tenant.admin.setup.save', $account)), { method: 'POST', headers, body });

                if (!resp.ok) {
                    const err = await resp.json().catch(() => null);
                    throw new Error(err?.message || Object.values(err?.errors || {}).flat().join(' ') || 'Save failed');
                }

                if (!this.completedSteps.includes(this.step)) {
                    this.completedSteps.push(this.step);
                }
                this.step++;
                window.scrollTo({top: 0, behavior: 'smooth'});
            } catch (e) {
                this.errorMsg = e.message;
            } finally {
                this.saving = false;
            }
        },

        async launch() {
            this.saving = true;
            this.errorMsg = '';
            try {
                const resp = await fetch(@json(route('tenant.admin.setup.save', $account)), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ step: 'complete' }),
                });
                const result = await resp.json();
                if (result.redirect) {
                    window.location.href = result.redirect;
                }
            } catch (e) {
                this.errorMsg = e.message;
                this.saving = false;
            }
        },
    };
}
</script>
@endsection
