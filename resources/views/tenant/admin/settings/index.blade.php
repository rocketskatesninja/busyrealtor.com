@extends('layouts.admin')
@section('title', 'Settings')
@section('head')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&family=Playfair+Display:wght@700&family=Montserrat:wght@700&family=Inter:wght@700&family=Lato:wght@700&family=Raleway:wght@700&family=Open+Sans:wght@700&family=Oswald:wght@700&family=Roboto:wght@700&display=swap" rel="stylesheet">
@endsection
@section('content')
@php
$account = $tenant->slug;
$tabs = [
    'account'      => ['label' => 'Account',     'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
    'dashboard'    => ['label' => 'Dashboard',   'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
    'appearance'   => ['label' => 'Appearance',  'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'],
    'messages'     => ['label' => 'Messages',    'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
    'integrations' => ['label' => 'Third Party', 'icon' => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z'],
    'data'         => ['label' => 'Data',        'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4'],
    'legal'        => ['label' => 'Legal',       'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    'seo'          => ['label' => 'SEO',         'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
    'homepage'     => ['label' => 'Homepage',    'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
];
@endphp
<div class="max-w-7xl mx-auto px-4">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Settings</h1>

    {{-- Mobile: horizontal scrollable tab strip --}}
    <div class="md:hidden mb-4 -mx-4 px-4">
        <div class="flex overflow-x-auto gap-1 pb-2 scrollbar-hide">
            @foreach($tabs as $key => $info)
            <a href="{{ route('tenant.admin.settings', $account) }}?tab={{ $key }}"
               class="flex-shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium transition-colors whitespace-nowrap {{ $tab === $key ? 'text-white' : 'bg-white text-gray-600 border border-gray-200' }}"
               style="{{ $tab === $key ? 'background-color: var(--primary)' : '' }}">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $info['icon'] }}"/></svg>
                {{ $info['label'] }}
            </a>
            @endforeach
        </div>
    </div>

    <div class="flex gap-8">
        {{-- Settings Sidebar (desktop only) --}}
        <aside class="hidden md:block w-56 flex-shrink-0">
            <nav class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                @foreach($tabs as $key => $info)
                <a href="{{ route('tenant.admin.settings', $account) }}?tab={{ $key }}"
                   class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors border-l-4 {{ $tab === $key ? 'border-l-4 bg-blue-50 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                   style="{{ $tab === $key ? 'border-color: var(--primary); color: var(--primary); background-color: rgba(var(--primary-rgb), 0.07)' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $info['icon'] }}"/></svg>
                    {{ $info['label'] }}
                </a>
                @endforeach
            </nav>
        </aside>

        {{-- Settings Content --}}
        <div class="flex-1">
            <form method="POST" action="{{ route('tenant.admin.settings.update', $account) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                <input type="hidden" name="tab" value="{{ $tab }}">

                @if($tab === 'account')
                {{-- ACCOUNT TAB --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">Account Settings</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                            <input type="text" name="name" value="{{ auth()->user()->name }}" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Your Email</label>
                            <input type="email" name="email" value="{{ auth()->user()->email }}" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Site Title</label>
                            <input type="text" name="site_title" value="{{ $settings->site_title }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tagline</label>
                            <input type="text" name="tagline" value="{{ $settings->tagline }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                            <input type="email" name="contact_email" value="{{ $settings->contact_email }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                            <input type="text" name="contact_phone" value="{{ $settings->contact_phone }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Business Address</label>
                            <input type="text" name="address" value="{{ $settings->address }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="mt-5 border-t pt-5">
                        <h3 class="font-medium text-gray-800 mb-3">Social Media</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach(['facebook_url'=>'Facebook URL','instagram_url'=>'Instagram URL','twitter_url'=>'Twitter/X URL','linkedin_url'=>'LinkedIn URL'] as $field=>$label)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                                <input type="url" name="{{ $field }}" value="{{ $settings->$field }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="https://...">
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-5 border-t pt-5">
                        <h3 class="font-medium text-gray-800 mb-3">Change Password</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input type="password" name="new_password" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Leave blank to keep current">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                <input type="password" name="new_password_confirmation" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                @elseif($tab === 'appearance')
                {{-- APPEARANCE TAB --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">Appearance</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                            <div class="flex items-center gap-3">
                                @if($settings->logo_image)
                                <img src="{{ asset('storage/'.$settings->logo_image) }}" class="h-10 w-auto rounded object-contain shrink-0 border border-gray-100 p-1 bg-white">
                                @endif
                                <input type="file" name="logo" accept="image/*" class="flex-1 border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Primary Color</label>
                            <div class="flex gap-2 items-center">
                                <input type="color" name="primary_color" value="{{ $settings->primary_color ?? '#3B82F6' }}" class="w-12 h-10 border-0 rounded cursor-pointer">
                                <input type="text" id="primary_color_hex" value="{{ $settings->primary_color ?? '#3B82F6' }}" class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono" oninput="document.querySelector('[name=primary_color]').value=this.value">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Header Mode</label>
                            <select name="header_mode" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none">
                                <option value="hero" {{ ($settings->header_mode ?? 'hero') === 'hero' ? 'selected' : '' }}>Hero (Transparent → Solid on scroll)</option>
                                <option value="default" {{ ($settings->header_mode ?? '') === 'default' ? 'selected' : '' }}>Default (Always Solid)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Header Display</label>
                            <select name="header_display_mode" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none">
                                <option value="both" {{ ($settings->header_display_mode ?? 'both') === 'both' ? 'selected' : '' }}>Logo + Text</option>
                                <option value="text_only" {{ ($settings->header_display_mode ?? '') === 'text_only' ? 'selected' : '' }}>Text Only</option>
                                <option value="logo_only" {{ ($settings->header_display_mode ?? '') === 'logo_only' ? 'selected' : '' }}>Logo Only</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title Font</label>
                            @php $currentFont = $settings->title_font ?? 'Poppins'; @endphp
                            <div x-data="{ open: false, selected: '{{ $currentFont }}' }" class="relative">
                                <input type="hidden" name="title_font" :value="selected">
                                <button type="button" @click="open = !open"
                                        class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm text-left flex items-center justify-between bg-white focus:outline-none focus:ring-2 focus:ring-blue-300">
                                    <span :style="`font-family: '${selected}', sans-serif; font-weight: 700; font-size: 1rem;`" x-text="selected"></span>
                                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="open" x-cloak @click.outside="open = false"
                                     class="absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-y-auto max-h-72">
                                    @foreach(['Poppins','Playfair Display','Montserrat','Inter','Lato','Raleway','Open Sans','Oswald','Roboto'] as $font)
                                    <button type="button"
                                            @click="selected = '{{ $font }}'; open = false"
                                            :class="selected === '{{ $font }}' ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50'"
                                            class="w-full text-left px-4 py-2.5 transition"
                                            style="font-family: '{{ $font }}', sans-serif; font-weight: 700; font-size: 1rem;">
                                        {{ $font }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title Size</label>
                            <select name="site_title_font_size" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none">
                                <option value="xl"  {{ ($settings->site_title_font_size ?? '3xl') === 'xl'  ? 'selected' : '' }}>Small</option>
                                <option value="2xl" {{ ($settings->site_title_font_size ?? '3xl') === '2xl' ? 'selected' : '' }}>Medium</option>
                                <option value="3xl" {{ ($settings->site_title_font_size ?? '3xl') === '3xl' ? 'selected' : '' }}>Large</option>
                                <option value="4xl" {{ ($settings->site_title_font_size ?? '3xl') === '4xl' ? 'selected' : '' }}>X-Large</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title Weight</label>
                            <select name="site_title_font_weight" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none">
                                <option value="600" {{ ($settings->site_title_font_weight ?? '700') === '600' ? 'selected' : '' }}>Semi-Bold</option>
                                <option value="700" {{ ($settings->site_title_font_weight ?? '700') === '700' ? 'selected' : '' }}>Bold</option>
                                <option value="800" {{ ($settings->site_title_font_weight ?? '700') === '800' ? 'selected' : '' }}>Extra Bold</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Letter Spacing</label>
                            <select name="site_title_letter_spacing" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none">
                                <option value="tight"  {{ ($settings->site_title_letter_spacing ?? 'normal') === 'tight'  ? 'selected' : '' }}>Tight</option>
                                <option value="normal" {{ ($settings->site_title_letter_spacing ?? 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="wide"   {{ ($settings->site_title_letter_spacing ?? 'normal') === 'wide'   ? 'selected' : '' }}>Wide</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            @php $colorType = $settings->title_color_type ?? 'gradient'; @endphp
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title Color</label>
                            <div class="flex items-end gap-3">
                                {{-- Dropdown: stays at half the row width (3/6) --}}
                                <div style="width:50%">
                                    <select name="title_color_type" id="title_color_type" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none"
                                        onchange="var v=this.value;document.getElementById('gradient-fields').style.display=v==='gradient'?'flex':'none';document.getElementById('solid-color-field').style.display=v==='solid'?'flex':'none';">
                                        <option value="gradient" {{ $colorType === 'gradient' ? 'selected' : '' }}>Gradient</option>
                                        <option value="solid" {{ $colorType === 'solid' ? 'selected' : '' }}>Solid Color</option>
                                    </select>
                                </div>
                                {{-- Gradient pickers: 3 swatches each 1/6 of the row --}}
                                <div id="gradient-fields" style="{{ $colorType === 'gradient' ? 'display:flex' : 'display:none' }}" class="flex-1 flex gap-2">
                                    @foreach(['title_gradient_start'=>'Start','title_gradient_via'=>'Mid','title_gradient_end'=>'End'] as $f=>$l)
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-600 mb-1 text-center">{{ $l }}</label>
                                        <input type="color" name="{{ $f }}" value="{{ $settings->$f ?? '#3B82F6' }}" class="w-full h-10 border-0 rounded cursor-pointer">
                                    </div>
                                    @endforeach
                                </div>
                                {{-- Solid picker: full remaining width --}}
                                <div id="solid-color-field" style="{{ $colorType === 'solid' ? 'display:flex' : 'display:none' }}" class="flex-1 flex-col gap-1">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Color</label>
                                    <input type="color" name="title_color_solid" value="{{ $settings->title_color_solid ?? '#3B82F6' }}" class="w-full h-10 border-0 rounded cursor-pointer">
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dark Mode</label>
                            <label class="flex items-center gap-3 cursor-pointer mt-2">
                                <input type="hidden" name="dark_mode_enabled" value="0">
                                <input type="checkbox" name="dark_mode_enabled" value="1" class="rounded" {{ $settings->dark_mode_enabled ? 'checked' : '' }}>
                                <span class="text-sm text-gray-600">Enable dark mode toggle</span>
                            </label>
                        </div>
                    </div>
                </div>

                @elseif($tab === 'homepage')
                {{-- HOMEPAGE TAB --}}
                @php
                $sectionDefs = [
                    ['key'=>'hero',         'label'=>'Hero',         'emoji'=>'🏠', 'desc'=>'Main banner — headline, subtitle and call-to-action buttons', 'locked'=>true],
                    ['key'=>'features',     'label'=>'Features',     'emoji'=>'✨', 'desc'=>'Highlight your key selling points and why clients should choose you'],
                    ['key'=>'listings',     'label'=>'Listings',     'emoji'=>'🏡', 'desc'=>'Display featured properties from your portfolio'],
                    ['key'=>'stats',        'label'=>'Statistics',   'emoji'=>'📊', 'desc'=>'Show impressive numbers — properties sold, years of experience, etc.'],
                    ['key'=>'services',     'label'=>'Services',     'emoji'=>'🛠️', 'desc'=>'List all the real estate services you offer'],
                    ['key'=>'team',         'label'=>'Team',         'emoji'=>'👥', 'desc'=>'Showcase your staff (manage them in the Staff section)'],
                    ['key'=>'testimonials', 'label'=>'Testimonials', 'emoji'=>'💬', 'desc'=>'Build credibility with reviews from satisfied clients'],
                    ['key'=>'faq',          'label'=>'FAQ',          'emoji'=>'❓', 'desc'=>'Answer common questions visitors have about your services'],
                    ['key'=>'contact',      'label'=>'Contact',      'emoji'=>'📞', 'desc'=>'Contact form and office information'],
                    ['key'=>'map',          'label'=>'Map',          'emoji'=>'🗺️', 'desc'=>'Embedded location map for your office or service area'],
                ];
                $savedSections = collect($settings->homepage_sections ?? []);
                $orderedSections = collect($sectionDefs)->map(function($def) use ($savedSections) {
                    $saved = $savedSections->firstWhere('key', $def['key']);
                    return array_merge($def, [
                        'enabled' => $saved ? ($saved['enabled'] ?? ($def['key'] === 'hero')) : ($def['key'] === 'hero'),
                        'order'   => $saved ? ($saved['order'] ?? 99) : 99,
                    ]);
                })->sortBy('order')->values()->all();
                @endphp

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-lg font-bold text-gray-900 mb-1">Homepage Sections</h2>
                    <p class="text-sm text-gray-500 mb-5">Drag to reorder. Click a section to edit its content. Toggle to show/hide.</p>

                    <script>
                    function hpSectionData() {
                        return {
                            expandedSection: null,
                            features: {!! json_encode($settings->features_items ?: [['icon'=>'home','title'=>'Smart Search','description'=>'Find your perfect home with our advanced search tools.'],['icon'=>'shield','title'=>'Trusted Service','description'=>'Licensed professionals dedicated to your success.']]) !!},
                            services: {!! json_encode($settings->services_items ?: [['icon'=>'home','title'=>'Home Buying','description'=>'Expert guidance through every step of the buying process.'],['icon'=>'dollar','title'=>'Home Selling','description'=>'Get maximum value with our proven strategies.']]) !!},
                            testimonials: {!! json_encode($settings->testimonials_items ?: [['name'=>'','rating'=>5,'text'=>'']]) !!},
                            stats: {!! json_encode($settings->stats_items ?: [['value'=>'500+','label'=>'Homes Sold'],['value'=>'15+','label'=>'Years Experience']]) !!},
                            faq: {!! json_encode($settings->faq_items ?: [['question'=>'','answer'=>'']]) !!}
                        };
                    }
                    </script>
                    <div x-data="hpSectionData()">
                        {{-- Hidden inputs serialized to JSON on submit --}}
                        <input type="hidden" name="homepage_sections" id="hp_sections_input">
                        <input type="hidden" name="features_items"      :value="JSON.stringify(features)">
                        <input type="hidden" name="services_items"      :value="JSON.stringify(services)">
                        <input type="hidden" name="testimonials_items"  :value="JSON.stringify(testimonials)">
                        <input type="hidden" name="stats_items"         :value="JSON.stringify(stats)">
                        <input type="hidden" name="faq_items"           :value="JSON.stringify(faq)">

                        <div id="sections-container" class="space-y-2">
                        @foreach($orderedSections as $section)
                        @php $key = $section['key']; $isLocked = $section['locked'] ?? false; @endphp
                        <div class="section-item bg-gray-50 rounded-xl border-2 border-transparent hover:border-gray-200 transition-all {{ $isLocked ? 'locked-section' : '' }}"
                             data-section="{{ $key }}" data-locked="{{ $isLocked ? '1' : '0' }}">

                            {{-- Row Header --}}
                            <div class="flex items-center gap-3 p-4 cursor-pointer select-none"
                                 @click="expandedSection = expandedSection === '{{ $key }}' ? null : '{{ $key }}'">

                                {{-- Drag handle / Lock icon --}}
                                @if(!$isLocked)
                                <div class="drag-handle cursor-grab text-gray-400 hidden sm:block shrink-0 active:cursor-grabbing">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/></svg>
                                </div>
                                @else
                                <div class="shrink-0 text-gray-300 hidden sm:block">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                </div>
                                @endif

                                {{-- Emoji + Label + Description --}}
                                <span class="text-xl shrink-0">{{ $section['emoji'] }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 text-sm">{{ $section['label'] }}</p>
                                    <p class="text-xs text-gray-500 truncate hidden sm:block">{{ $section['desc'] }}</p>
                                </div>

                                {{-- Expand chevron --}}
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0"
                                     :class="expandedSection === '{{ $key }}' ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>

                                {{-- Toggle (not shown for locked sections) --}}
                                @if(!$isLocked)
                                <label class="relative inline-flex items-center cursor-pointer shrink-0" @click.stop>
                                    <input type="checkbox" class="sr-only peer section-toggle"
                                           data-section="{{ $key }}"
                                           {{ $section['enabled'] ? 'checked' : '' }}>
                                    <div class="relative w-9 h-5 rounded-full bg-gray-200 peer-checked:bg-blue-500 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4" style="--tw-peer-checked-bg: var(--primary)"></div>
                                </label>
                                @else
                                <span class="text-xs text-gray-400 bg-gray-200 px-2 py-1 rounded shrink-0">Always on</span>
                                @endif
                            </div>

                            {{-- Expandable Content Panel --}}
                            <div x-show="expandedSection === '{{ $key }}'" class="px-4 pb-4">
                                <div class="bg-white rounded-xl p-4 border border-gray-100 space-y-4">

                                @if($key === 'hero')
                                {{-- HERO EDITOR --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Hero Headline</label>
                                        <input type="text" name="hero_title" value="{{ $settings->hero_title }}"
                                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Find Your Dream Home">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Hero Subtitle</label>
                                        <input type="text" name="hero_subtitle" value="{{ $settings->hero_subtitle }}"
                                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Professional real estate services...">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">CTA Button 1 Text</label>
                                        <input type="text" name="cta_primary_text" value="{{ $settings->cta_primary_text }}"
                                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Browse Listings">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">CTA Button 1 URL</label>
                                        <input type="text" name="cta_primary_link" value="{{ $settings->cta_primary_link }}"
                                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="/gallery">
                                        <p class="text-xs text-gray-400 mt-1">Use <code>/gallery</code>, <code>/map</code>, <code>#contact</code>, or a full URL. Site paths are auto-prefixed with <code>/{{ $tenant->slug }}</code>.</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">CTA Button 2 Text</label>
                                        <input type="text" name="cta_secondary_text" value="{{ $settings->cta_secondary_text }}"
                                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Contact Us">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">CTA Button 2 URL</label>
                                        <input type="text" name="cta_secondary_link" value="{{ $settings->cta_secondary_link }}"
                                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="#contact">
                                        <p class="text-xs text-gray-400 mt-1">Use <code>/gallery</code>, <code>/map</code>, <code>#contact</code>, or a full URL. Site paths are auto-prefixed with <code>/{{ $tenant->slug }}</code>.</p>
                                    </div>
                                    <div class="md:col-span-2 border-t border-gray-100 pt-4"
                                         x-data="{ bgType: '' }"
                                         x-init="bgType = ($el.querySelector('input[name=hero_background_type]:checked') || {}).value || 'preset'">
                                        <label class="block text-xs font-medium text-gray-600 mb-2">Hero Background</label>
                                        {{-- Type selector: radio inputs handle form submission; peer-checked CSS handles visual state --}}
                                        <div class="flex gap-2 flex-wrap mb-3">
                                            @foreach(['preset'=>'Preset Image','image'=>'Custom Image','gradient'=>'Gradient'] as $bval=>$blabel)
                                            <label class="cursor-pointer" @click="bgType='{{ $bval }}'">
                                                <input type="radio" name="hero_background_type" value="{{ $bval }}"
                                                       class="sr-only peer"
                                                       {{ ($settings->hero_background_type ?? 'preset') === $bval ? 'checked' : '' }}>
                                                <span class="block px-3 py-1.5 rounded-lg text-xs font-medium transition
                                                             peer-checked:bg-blue-500 peer-checked:text-white
                                                             bg-gray-100 text-gray-600 hover:bg-gray-200">{{ $blabel }}</span>
                                            </label>
                                            @endforeach
                                        </div>
                                        {{-- Preset image grid — radio inputs + CSS peer-checked for selection highlight (no Alpine needed) --}}
                                        <div x-show="bgType==='preset'">
                                            @php
                                            $presets = [
                                                'modern-home'=>'Modern Home','luxury-estate'=>'Luxury Estate','suburban'=>'Suburban',
                                                'countryside'=>'Countryside','cozy-interior'=>'Cozy Interior','urban-loft'=>'Urban Loft',
                                                'minimalist'=>'Minimalist','beach'=>'Beach','mountain'=>'Mountain',
                                                'desert'=>'Desert','woods'=>'Woods','river'=>'River',
                                                'grassland'=>'Grassland','small-town'=>'Small Town','cityscape'=>'Cityscape',
                                            ];
                                            $savedPreset = $settings->hero_preset ?? 'modern-home';
                                            @endphp
                                            <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                                                @foreach($presets as $pkey=>$plabel)
                                                {{-- label wraps input+card+checkmark; peer-checked siblings all respond to radio state --}}
                                                <label class="cursor-pointer relative block">
                                                    <input type="radio" name="hero_preset" value="{{ $pkey }}"
                                                           class="sr-only peer"
                                                           {{ $savedPreset === $pkey ? 'checked' : '' }}>
                                                    <div class="rounded-lg overflow-hidden transition border-2 border-transparent peer-checked:border-blue-500 hover:border-gray-300">
                                                        <img src="/assets/images/hero-presets/{{ $pkey }}.jpg"
                                                             alt="{{ $plabel }}" class="w-full h-14 object-cover">
                                                        <div class="text-xs text-center py-0.5 bg-white dark:bg-gray-700 dark:text-gray-300 text-gray-600 truncate px-1">{{ $plabel }}</div>
                                                    </div>
                                                    {{-- Checkmark: sibling of the peer input, visible when checked --}}
                                                    <div class="absolute top-1 right-1 bg-blue-500 rounded-full p-0.5 hidden peer-checked:block pointer-events-none">
                                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                        </svg>
                                                    </div>
                                                </label>
                                                @endforeach
                                            </div>
                                        </div>
                                        {{-- Custom image upload --}}
                                        <div x-show="bgType==='image'" class="space-y-2">
                                            @if($settings->hero_image)
                                            <div class="rounded-lg overflow-hidden h-24 relative">
                                                <img src="{{ asset('storage/' . $settings->hero_image) }}" class="w-full h-full object-cover">
                                                <div class="absolute inset-0 bg-black/30 flex items-center justify-center">
                                                    <span class="text-white text-xs font-medium">Current hero image</span>
                                                </div>
                                            </div>
                                            @endif
                                            <input type="file" name="hero_image" accept="image/*"
                                                   class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                            <p class="text-xs text-gray-400">Recommended: 1920×1080px or wider. JPG or PNG.</p>
                                        </div>
                                        {{-- Gradient controls --}}
                                        <div x-show="bgType==='gradient'" class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Start Color</label>
                                                <input type="color" name="hero_gradient_start" value="{{ $settings->hero_gradient_start ?? '#1e3a5f' }}" class="w-full h-10 border-0 rounded cursor-pointer">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">End Color</label>
                                                <input type="color" name="hero_gradient_end" value="{{ $settings->hero_gradient_end ?? '#7c3aed' }}" class="w-full h-10 border-0 rounded cursor-pointer">
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                @elseif($key === 'features')
                                {{-- FEATURES EDITOR --}}
                                <template x-for="(feature, index) in features" :key="index">
                                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 relative">
                                        <button type="button" class="absolute top-2 right-2 text-red-400 hover:text-red-600" @click="features.splice(index,1)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pr-6">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Icon</label>
                                                <select x-model="feature.icon" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="home">🏠 Home</option>
                                            <option value="search">🔍 Search</option>
                                            <option value="star">⭐ Star</option>
                                            <option value="shield">🛡️ Shield</option>
                                            <option value="chat">💬 Chat</option>
                                            <option value="dollar">💵 Dollar</option>
                                            <option value="key">🔑 Key</option>
                                            <option value="map">📍 Map</option>
                                            <option value="chart">📈 Chart</option>
                                            <option value="building">🏢 Building</option>
                                        </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                                                <input type="text" x-model="feature.title" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Feature title">
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                                            <textarea x-model="feature.description" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Describe this feature..."></textarea>
                                        </div>
                                    </div>
                                </template>
                                <button type="button" @click="features.push({icon:'star',title:'',description:''})" class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors">+ Add Feature</button>

                                @elseif($key === 'services')
                                {{-- SERVICES EDITOR --}}
                                <template x-for="(service, index) in services" :key="index">
                                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 relative">
                                        <button type="button" class="absolute top-2 right-2 text-red-400 hover:text-red-600" @click="services.splice(index,1)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pr-6">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Icon</label>
                                                <select x-model="service.icon" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="home">🏠 Home</option>
                                            <option value="search">🔍 Search</option>
                                            <option value="star">⭐ Star</option>
                                            <option value="shield">🛡️ Shield</option>
                                            <option value="chat">💬 Chat</option>
                                            <option value="dollar">💵 Dollar</option>
                                            <option value="key">🔑 Key</option>
                                            <option value="map">📍 Map</option>
                                            <option value="chart">📈 Chart</option>
                                            <option value="building">🏢 Building</option>
                                        </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                                                <input type="text" x-model="service.title" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Service title">
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                                            <textarea x-model="service.description" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Describe this service..."></textarea>
                                        </div>
                                    </div>
                                </template>
                                <button type="button" @click="services.push({icon:'home',title:'',description:''})" class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors">+ Add Service</button>

                                @elseif($key === 'stats')
                                {{-- STATS EDITOR --}}
                                <template x-for="(stat, index) in stats" :key="index">
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                        <div class="flex-1 grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Value</label>
                                                <input type="text" x-model="stat.value" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="500+">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Label</label>
                                                <input type="text" x-model="stat.label" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Homes Sold">
                                            </div>
                                        </div>
                                        <button type="button" @click="stats.splice(index,1)" class="text-red-400 hover:text-red-600 shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="stats.push({value:'',label:'',icon:'star'})" class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors">+ Add Stat</button>

                                @elseif($key === 'testimonials')
                                {{-- TESTIMONIALS EDITOR --}}
                                <template x-for="(testimonial, index) in testimonials" :key="index">
                                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 relative">
                                        <button type="button" class="absolute top-2 right-2 text-red-400 hover:text-red-600" @click="testimonials.splice(index,1)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pr-6">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                                                <input type="text" x-model="testimonial.name" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Client name">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">Rating</label>
                                                <select x-model.number="testimonial.rating" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="5">⭐⭐⭐⭐⭐ (5 stars)</option>
                                                    <option value="4">⭐⭐⭐⭐ (4 stars)</option>
                                                    <option value="3">⭐⭐⭐ (3 stars)</option>
                                                    <option value="2">⭐⭐ (2 stars)</option>
                                                    <option value="1">⭐ (1 star)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Testimonial Text</label>
                                            <textarea x-model="testimonial.text" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="What the client said..."></textarea>
                                        </div>
                                    </div>
                                </template>
                                <button type="button" @click="testimonials.push({name:'',text:'',rating:5})" class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors">+ Add Testimonial</button>

                                @elseif($key === 'faq')
                                {{-- FAQ EDITOR --}}
                                <template x-for="(item, index) in faq" :key="index">
                                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 space-y-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex-1 space-y-3">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">Question</label>
                                                    <input type="text" x-model="item.question" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="How do I schedule a viewing?">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">Answer</label>
                                                    <textarea x-model="item.answer" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Your answer here..."></textarea>
                                                </div>
                                            </div>
                                            <button type="button" @click="faq.splice(index,1)" class="text-red-400 hover:text-red-600 shrink-0 mt-1">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <button type="button" @click="faq.push({question:'',answer:''})" class="w-full py-2 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-500 hover:border-gray-400 hover:text-gray-700 transition-colors">+ Add FAQ Item</button>

                                @elseif($key === 'listings')
                                <p class="text-sm text-gray-500">Displays properties marked as <strong>Featured</strong> from your portfolio. Manage your properties in the <a href="{{ route('tenant.admin.properties.index', $account) }}" class="text-blue-600 hover:underline">Properties section</a>.</p>

                                @elseif($key === 'team')
                                <p class="text-sm text-gray-500">Displays staff members with <strong>Display on Homepage</strong> enabled. Manage your team in the <a href="{{ route('tenant.admin.staff.index', $account) }}" class="text-blue-600 hover:underline">Staff section</a>.</p>

                                @elseif($key === 'contact')
                                <p class="text-sm text-gray-500">Shows your contact form and office details. Update your phone, email, and address in <a href="?tab=general" class="text-blue-600 hover:underline">General settings</a>.</p>

                                @elseif($key === 'map')
                                <p class="text-sm text-gray-500">Displays an embedded map of your office location. Make sure you have a Google Maps API key set in <a href="?tab=integrations" class="text-blue-600 hover:underline">Integrations</a>.</p>

                                @else
                                <p class="text-sm text-gray-500">This section displays on your homepage when enabled.</p>
                                @endif

                                </div>
                            </div>

                        </div>
                        @endforeach
                        </div>{{-- #sections-container --}}
                    </div>{{-- x-data --}}
                </div>

                @elseif($tab === 'dashboard')
                {{-- DASHBOARD CONFIG TAB --}}
                @php $dashConfig = $settings->dashboard_config ?? []; @endphp
                <div class="space-y-6">
                    <p class="text-gray-500 text-sm">Choose which widgets appear on your dashboard. Changes take effect immediately after saving.</p>

                    {{-- Stat Cards --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-base font-bold text-gray-900 mb-1">Stat Cards</h2>
                        <p class="text-xs text-gray-400 mb-4">Numeric summary tiles at the top of the dashboard.</p>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach([
                                'active_listings'  => 'Active Listings',
                                'portfolio_value'  => 'Portfolio Value',
                                'unread_messages'  => 'Unread Messages',
                                'pending_appts'    => 'Pending Appointments',
                                'total_properties' => 'Total Properties',
                                'sold_properties'  => 'Sold Properties',
                                'new_this_week'    => 'New This Week',
                                'avg_price'        => 'Avg List Price',
                                'total_revenue'    => 'Total Revenue (Sold)',
                                'response_rate'    => 'Response Rate',
                                'days_on_market'   => 'Avg Days Listed',
                                'pending_listings' => 'Pending Listings',
                            ] as $widget => $label)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer bg-gray-50 hover:bg-gray-100 rounded-xl p-3 transition">
                                <input type="checkbox" name="dashboard_config[{{ $widget }}]" value="1" class="rounded" {{ ($dashConfig[$widget] ?? true) ? 'checked' : '' }}>
                                {{ $label }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Charts --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-base font-bold text-gray-900 mb-1">Charts</h2>
                        <p class="text-xs text-gray-400 mb-4">Visual data breakdowns shown below the stat cards.</p>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach([
                                'type_chart'   => 'Properties by Type',
                                'status_chart' => 'Listing Status',
                                'views_chart'  => 'Views by Property',
                            ] as $widget => $label)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer bg-gray-50 hover:bg-gray-100 rounded-xl p-3 transition">
                                <input type="checkbox" name="dashboard_config[{{ $widget }}]" value="1" class="rounded" {{ ($dashConfig[$widget] ?? true) ? 'checked' : '' }}>
                                {{ $label }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tables --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-base font-bold text-gray-900 mb-1">Tables</h2>
                        <p class="text-xs text-gray-400 mb-4">Detailed list sections shown at the bottom of the dashboard.</p>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach([
                                'top_properties'   => 'Top Properties by Views',
                                'recent_messages'  => 'Recent Messages',
                                'upcoming_appts'   => 'Upcoming Appointments',
                                'recent_properties'=> 'Recently Added',
                                'needs_attention'  => 'Needs Attention',
                            ] as $widget => $label)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer bg-gray-50 hover:bg-gray-100 rounded-xl p-3 transition">
                                <input type="checkbox" name="dashboard_config[{{ $widget }}]" value="1" class="rounded" {{ ($dashConfig[$widget] ?? true) ? 'checked' : '' }}>
                                {{ $label }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                @elseif($tab === 'messages')
                {{-- MESSAGES & SMTP TAB --}}
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-5">Email Notifications</h2>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="notify_on_contact" value="1" class="rounded" {{ $settings->notify_on_contact ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700">Notify me on new contact messages</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="notify_on_appointment" value="1" class="rounded" {{ $settings->notify_on_appointment ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700">Notify me on new appointment requests</span>
                            </label>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-5">SMTP Configuration</h2>
                        @php $smtp = $integrations->get('smtp'); $smtpConfig = $smtp?->config ?? []; @endphp
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2 flex gap-3"><div class="flex-[2]"><label class="block text-xs font-medium text-gray-600 mb-1">SMTP Host</label><input type="text" name="smtp_host" value="{{ $smtpConfig['smtp_host'] ?? '' }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div><div class="flex-1"><label class="block text-xs font-medium text-gray-600 mb-1">Port</label><input type="number" name="smtp_port" value="{{ $smtpConfig['smtp_port'] ?? 587 }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div><div class="flex-1"><label class="block text-xs font-medium text-gray-600 mb-1">Encryption</label><select name="smtp_encryption" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><option value="tls" @selected(($smtpConfig['smtp_encryption'] ?? 'tls') === 'tls')>TLS / STARTTLS (port 587)</option><option value="ssl" @selected(($smtpConfig['smtp_encryption'] ?? '') === 'ssl')>SSL (port 465)</option><option value="" @selected(($smtpConfig['smtp_encryption'] ?? 'tls') === '')>None (port 25)</option></select></div></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Username</label><input type="text" name="smtp_username" value="{{ $smtpConfig['smtp_username'] ?? '' }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">Password</label><input type="password" name="smtp_password" value="{{ $smtpConfig['smtp_password'] ?? '' }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">From Email</label><input type="email" name="smtp_from_email" value="{{ $smtpConfig['smtp_from_email'] ?? '' }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">From Name</label><input type="text" name="smtp_from_name" value="{{ $smtpConfig['smtp_from_name'] ?? $settings->site_title }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                        </div>
                        <div class="mt-4">
                            <button type="button" id="test-email-btn" class="text-sm px-4 py-2 rounded-lg font-medium transition" style="background-color: rgba(var(--primary-rgb),0.1); color: var(--primary)">Send Test Email</button>
                            <span id="test-email-result" class="text-sm ml-3"></span>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-5">AI Chatbot</h2>
                        <div class="space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="chatbot_enabled" value="1" class="rounded" {{ $settings->chatbot_enabled ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700">Enable chatbot widget on public site</span>
                            </label>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Personality</label>
                                <select name="chatbot_personality" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none">
                                    @foreach(['friendly'=>'Friendly','professional'=>'Professional','concise'=>'Concise'] as $v=>$l)
                                    <option value="{{ $v }}" {{ ($settings->chatbot_personality ?? 'professional') === $v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Realtor Bio (provided as context to AI)</label>
                                <textarea name="chatbot_realtor_bio" rows="4" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none resize-none" placeholder="Describe yourself, your expertise, and your market area...">{{ $settings->chatbot_realtor_bio }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                @elseif($tab === 'integrations')
                {{-- THIRD PARTY TAB --}}
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-5">Google Maps</h2>
                        @php $maps = $integrations->get('google_maps'); @endphp
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Google Maps API Key</label>
                            <input type="text" name="google_maps_key" value="{{ $maps?->api_key }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono" placeholder="AIza...">
                            <p class="text-xs text-gray-500 mt-1">Required for the map page. Get a key at Google Cloud Console.</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-5">AI Provider</h2>
                        @php $ai = $integrations->get('ai_provider'); $aiConfig = $ai?->config ?? []; @endphp
                        <div class="space-y-4" x-data="{ provider: '{{ $ai?->provider ?? 'anthropic' }}' }">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                                <input type="password" name="ai_api_key" value="{{ $ai?->api_key }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                            </div>
                            <div class="flex gap-3">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Provider</label>
                                    <select name="ai_provider" x-model="provider" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="openai">OpenAI</option>
                                        <option value="anthropic">Anthropic</option>
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                                    <select name="ai_model" x-show="provider === 'openai'" :disabled="provider !== 'openai'" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="gpt-4o" @selected(($aiConfig['model'] ?? '') === 'gpt-4o')>GPT-4o</option>
                                        <option value="gpt-4o-mini" @selected(($aiConfig['model'] ?? 'gpt-4o-mini') === 'gpt-4o-mini')>GPT-4o Mini (recommended)</option>
                                    </select>
                                    <select name="ai_model" x-show="provider === 'anthropic'" :disabled="provider !== 'anthropic'" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="claude-opus-4-6" @selected(($aiConfig['model'] ?? '') === 'claude-opus-4-6')>Claude Opus 4.6</option>
                                        <option value="claude-sonnet-4-6" @selected(($aiConfig['model'] ?? '') === 'claude-sonnet-4-6')>Claude Sonnet 4.6</option>
                                        <option value="claude-haiku-4-5-20251001" @selected(($aiConfig['model'] ?? 'claude-haiku-4-5-20251001') === 'claude-haiku-4-5-20251001')>Claude Haiku 4.5 (recommended)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-5">Google Analytics</h2>
                        @php $ga = $integrations->get('google_analytics'); @endphp
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Measurement ID</label>
                                <input type="text" name="ga_measurement_id" value="{{ $ga?->api_key }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono" placeholder="G-XXXXXXXXXX">
                            </div>
                            <div class="mt-5">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="ga_enabled" value="1" class="rounded" {{ $ga?->is_active ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-700">Enabled</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                @elseif($tab === 'data')
                {{-- DATA TAB --}}
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-5">Export Data</h2>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('tenant.admin.api.export', [$account, 'properties']) }}?format=json" class="px-5 py-2.5 bg-blue-100 text-blue-700 rounded-xl text-sm font-semibold hover:bg-blue-200 transition">Export Properties (JSON)</a>
                            <a href="{{ route('tenant.admin.api.export', [$account, 'properties']) }}?format=csv" class="px-5 py-2.5 bg-green-100 text-green-700 rounded-xl text-sm font-semibold hover:bg-green-200 transition">Export Properties (CSV)</a>
                            <a href="{{ route('tenant.admin.api.export', [$account, 'messages']) }}?format=json" class="px-5 py-2.5 bg-purple-100 text-purple-700 rounded-xl text-sm font-semibold hover:bg-purple-200 transition">Export Messages (JSON)</a>
                            <a href="{{ route('tenant.admin.api.export', [$account, 'messages']) }}?format=csv" class="px-5 py-2.5 bg-orange-100 text-orange-700 rounded-xl text-sm font-semibold hover:bg-orange-200 transition">Export Messages (CSV)</a>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-5">Backup</h2>
                        <p class="text-gray-500 text-sm mb-4">Create a backup of your properties and messages data.</p>
                        <button type="button" onclick="createBackup()" class="px-5 py-2.5 btn-primary rounded-xl text-sm font-semibold hover:opacity-90 transition">Create Backup</button>
                        <span id="backup-status" class="ml-3 text-sm text-gray-500"></span>
                    </div>
                </div>

                @elseif($tab === 'legal')
                {{-- LEGAL TAB --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">Legal Pages</h2>
                    <div class="space-y-6">
                        @foreach(['privacy' => 'Privacy Policy', 'terms' => 'Terms of Service'] as $type => $title)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $title }}</label>
                            <textarea name="{{ $type }}" rows="10" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y">{{ $legal->get($type)?->content }}</textarea>
                        </div>
                        @endforeach
                    </div>
                </div>

                @elseif($tab === 'seo')
                {{-- SEO TAB --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">SEO Settings</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                            <input type="text" name="meta_title" value="{{ $settings->meta_title }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-400 mt-1">Recommended: 50-60 characters</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                            <textarea name="meta_description" rows="3" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ $settings->meta_description }}</textarea>
                            <p class="text-xs text-gray-400 mt-1">Recommended: 150-160 characters</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                            <input type="text" name="meta_keywords" value="{{ $settings->meta_keywords }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="real estate, homes for sale, ...">
                        </div>
                    </div>
                </div>
                @endif

                @if(!in_array($tab, ['data']))
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary px-8 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">Save Settings</button>
                </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
// Test email
document.getElementById('test-email-btn')?.addEventListener('click', async () => {
    const email = prompt('Send test to email:');
    if (!email) return;
    const btn = document.getElementById('test-email-btn');
    const res = document.getElementById('test-email-result');
    btn.textContent = 'Sending...';
    try {
        const r = await fetch('{{ route('tenant.admin.api.test-email', $account) }}', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ to: email })
        });
        const d = await r.json();
        res.textContent = d.message;
        res.className = 'text-sm ml-3 ' + (d.success ? 'text-green-600' : 'text-red-600');
    } catch(e) { res.textContent = 'Failed'; res.className = 'text-sm ml-3 text-red-600'; }
    btn.textContent = 'Send Test Email';
});

async function createBackup() {
    const btn = event.target;
    const status = document.getElementById('backup-status');
    btn.disabled = true; btn.textContent = 'Creating...';
    try {
        const r = await fetch('{{ route('tenant.admin.api.backup', $account) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
        const blob = await r.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'backup.zip'; a.click();
        status.textContent = 'Backup downloaded!';
    } catch(e) { status.textContent = 'Backup failed.'; }
    btn.disabled = false; btn.textContent = 'Create Backup';
}

// Test email
async function createBackup() {
    const btn = event.target;
    const status = document.getElementById('backup-status');
    btn.disabled = true; btn.textContent = 'Creating...';
    try {
        const r = await fetch('{{ route('tenant.admin.api.backup', $account) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
        const blob = await r.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'backup.zip'; a.click();
        status.textContent = 'Backup downloaded!';
    } catch(e) { status.textContent = 'Backup failed.'; }
    btn.disabled = false; btn.textContent = 'Create Backup';
}

// ── Homepage section ordering ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('sections-container');
    if (!container) return;

    // Init the hidden input on load
    updateSectionsData();

    // Re-sync on every toggle change
    container.addEventListener('change', function(e) {
        if (e.target.classList.contains('section-toggle')) updateSectionsData();
    });

    // SortableJS — disabled on mobile
    if (typeof Sortable !== 'undefined' && window.innerWidth >= 640) {
        new Sortable(container, {
            animation: 150,
            ghostClass: 'opacity-50',
            handle: '.drag-handle',
            filter: '.locked-section',
            preventOnFilter: false,
            onEnd: updateSectionsData
        });
    }

    function updateSectionsData() {
        const sections = [];
        container.querySelectorAll('.section-item').forEach(function(item, i) {
            const key     = item.dataset.section;
            const toggle  = item.querySelector('.section-toggle');
            const locked  = item.dataset.locked === '1';
            sections.push({
                key:     key,
                enabled: locked ? true : (toggle ? toggle.checked : true),
                order:   i,
                locked:  locked
            });
        });
        const input = document.getElementById('hp_sections_input');
        if (input) input.value = JSON.stringify(sections);
    }
});

@endsection
