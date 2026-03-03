@extends('layouts.admin')
@section('title', 'Settings')
@section('page-subtitle', 'Configure your site, branding, and integrations')
@section('head')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&family=Playfair+Display:wght@700&family=Montserrat:wght@700&family=Inter:wght@700&family=Lato:wght@700&family=Raleway:wght@700&family=Open+Sans:wght@700&family=Oswald:wght@700&family=Roboto:wght@700&display=swap" rel="stylesheet">
@endsection
@section('content')
@php
$account = $tenant->slug;
$groups = [
    'SITE' => [
        'general'       => ['label' => 'General',        'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
        'appearance'    => ['label' => 'Appearance',     'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'],
        'homepage'      => ['label' => 'Homepage',       'icon' => 'M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z'],
        'seo'           => ['label' => 'SEO',            'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
        'legal'         => ['label' => 'Legal',          'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    ],
    'ACCOUNT' => [
        'profile'       => ['label' => 'Profile',        'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        'notifications' => ['label' => 'Notifications',  'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
    ],
    'INTEGRATIONS' => [
        'connected'     => ['label' => 'Connected Apps', 'icon' => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z'],
        'chatbot'       => ['label' => 'Chatbot',        'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
    ],
    'TOOLS' => [
        'dashboard'     => ['label' => 'Dashboard',      'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        'data'          => ['label' => 'Data',           'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4'],
    ],
];
$tabs = array_merge(...array_values($groups));
@endphp
<div class="max-w-7xl mx-auto px-4">

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
        <aside class="hidden md:block w-64 flex-shrink-0 sticky top-6 self-start">
            <nav class="bg-gray-50 border-r border-gray-200 py-2">
                @foreach($groups as $groupLabel => $groupTabs)
                <div class="mt-4 first:mt-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 px-3 mb-1">{{ $groupLabel }}</p>
                    @foreach($groupTabs as $key => $info)
                    <a href="{{ route('tenant.admin.settings', $account) }}?tab={{ $key }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium mx-1 transition-colors {{ $tab === $key ? '' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
                       @if($tab === $key) style="background-color: var(--primary); color: #fff" @endif>
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $info['icon'] }}"/></svg>
                        {{ $info['label'] }}
                    </a>
                    @endforeach
                </div>
                @endforeach
            </nav>
        </aside>

        {{-- Settings Content --}}
        <div class="flex-1">
            <form method="POST" action="{{ route('tenant.admin.settings.update', $account) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                <input type="hidden" name="tab" value="{{ $tab }}">

                @if($tab === 'general')
                {{-- GENERAL TAB --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">General Settings</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
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
                            <input type="text" name="contact_address" value="{{ $settings->contact_address }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="mt-5 border-t pt-5">
                        <h3 class="font-medium text-gray-800 mb-3">Social Links</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach(['social_facebook'=>'Facebook URL','social_instagram'=>'Instagram URL','social_twitter'=>'Twitter/X URL','social_linkedin'=>'LinkedIn URL'] as $field=>$label)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                                <input type="url" name="{{ $field }}" value="{{ $settings->$field }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="https://...">
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                @elseif($tab === 'profile')
                {{-- PROFILE TAB --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">Profile</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                            <input type="text" name="name" value="{{ auth()->user()->name }}" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Your Email</label>
                            <input type="email" name="email" value="{{ auth()->user()->email }}" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                    <div class="mt-5 border-t pt-5">
                        <h3 class="font-medium text-gray-800 mb-3">Agent Profile</h3>
                        <p class="text-sm text-gray-500 mb-4">This public profile powers the Agent Spotlight section on your homepage and appears on property listings.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Agent Name</label>
                                <input type="text" name="owner_name" value="{{ $settings->owner_name ?? auth()->user()->name }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Your display name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Profile Photo</label>
                                <div class="flex items-center gap-3">
                                    @if($settings->owner_photo)
                                    <img src="{{ asset('storage/'.$settings->owner_photo) }}" class="h-10 w-10 rounded-full object-cover flex-shrink-0 border border-gray-200">
                                    @endif
                                    <input type="file" name="owner_photo" accept="image/*" class="flex-1 border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none">
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                                <textarea name="owner_bio" rows="4" maxlength="1000" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Share your expertise, years of experience, and the market areas you serve...">{{ $settings->owner_bio }}</textarea>
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
                    ['key'=>'agent',        'label'=>'Agent Spotlight','emoji'=>'🧑‍💼','desc'=>'Highlight the owner/broker with photo and bio (set in Profile settings)'],
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
                                <p class="text-sm text-gray-500">Displays an embedded map of your office location. Make sure you have a Google Maps API key set in <a href="?tab=connected" class="text-blue-600 hover:underline">Integrations</a>.</p>
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Office Photo <span class="text-gray-400 font-normal">(optional)</span>
                                    </label>
                                    <p class="text-xs text-gray-400 mb-3">Upload a photo of your office or team to display alongside the location map.</p>
                                    @if($settings->map_office_image)
                                    <div class="rounded-lg overflow-hidden h-28 relative mb-3 border border-gray-200">
                                        <img src="{{ asset('storage/' . $settings->map_office_image) }}" class="w-full h-full object-cover">
                                        <div class="absolute inset-0 bg-black/25 flex items-end px-3 py-2">
                                            <span class="text-white text-xs font-medium">Current office photo</span>
                                        </div>
                                    </div>
                                    @endif
                                    <input type="file" name="map_office_image" accept="image/*"
                                        class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                                    <p class="text-xs text-gray-400 mt-1">Leave blank to keep the current photo. Max 5MB.</p>
                                </div>

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
                                'views_month'      => 'Views (30 Days)',
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
                                'type_chart'        => 'Properties by Type',
                                'status_chart'      => 'Listing Status',
                                'views_chart'       => 'Views by Property',
                                'views_30days'      => 'Daily Views (30 Days)',
                                'messages_7days'    => 'Daily Messages (7 Days)',
                                'price_distribution'=> 'Price Range Distribution',
                                'listings_over_time'=> 'Listings Added (12 Mo)',
                                'revenue_trend'     => 'Revenue Trend (12 Mo)',
                                'appt_status'       => 'Appointment Status',
                                'message_sources'   => 'Message Sources',
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
                                'starred_messages' => 'Starred Messages',
                            ] as $widget => $label)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer bg-gray-50 hover:bg-gray-100 rounded-xl p-3 transition">
                                <input type="checkbox" name="dashboard_config[{{ $widget }}]" value="1" class="rounded" {{ ($dashConfig[$widget] ?? true) ? 'checked' : '' }}>
                                {{ $label }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                @elseif($tab === 'notifications')
                {{-- NOTIFICATIONS TAB --}}
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
                </div>

                @elseif($tab === 'chatbot')
                {{-- CHATBOT TAB --}}
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

                @elseif($tab === 'connected')
                {{-- CONNECTED APPS TAB --}}
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
                    @php
                    $fb = $integrations['facebook'] ?? null;
                    $tw = $integrations['twitter'] ?? null;
                    $fbConfig = $fb?->config ?? [];
                    $twConfig = $tw?->config ?? [];
                    @endphp
                    {{-- Facebook --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#1877F2">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Facebook</h2>
                                <p class="text-xs text-gray-500">Auto-post listings to your Facebook Page</p>
                            </div>
                            <div class="ml-auto flex items-center gap-2">
                                <span class="text-sm text-gray-600">Enable</span>
                                <input type="hidden" name="fb_enabled" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="fb_enabled" value="1" class="sr-only peer" {{ ($fb?->is_active) ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 peer-focus:ring-2 peer-focus:ring-blue-300 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
                                </label>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Page Access Token</label>
                                    <input type="password" name="fb_access_token" placeholder="{{ $fb ? '••••••••••••••••' : 'Paste your Page Access Token' }}"
                                        class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                                    <p class="text-xs text-gray-400 mt-1">Leave blank to keep existing token. Get a long-lived token from the Facebook Developer Console.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Facebook Page ID</label>
                                    <input type="text" name="fb_page_id" value="{{ $fbConfig['page_id'] ?? '' }}"
                                        class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono" placeholder="e.g. 123456789012345">
                                    <p class="text-xs text-gray-400 mt-1">Found in your Facebook Page &rarr; About &rarr; Page ID.</p>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700 mb-2">Auto-post on:</p>
                                <div class="flex flex-wrap gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="hidden" name="fb_post_new_listing" value="0">
                                        <input type="checkbox" name="fb_post_new_listing" value="1" class="rounded" {{ !empty($fbConfig['post_on_new_listing']) ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700">New Listings</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="hidden" name="fb_post_sold" value="0">
                                        <input type="checkbox" name="fb_post_sold" value="1" class="rounded" {{ !empty($fbConfig['post_on_sold']) ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700">Sold Properties</span>
                                    </label>
                                </div>
                            </div>
                            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                                <p class="text-xs text-blue-800 font-medium mb-1">How to get a Page Access Token:</p>
                                <ol class="text-xs text-blue-700 space-y-1 list-decimal list-inside">
                                    <li>Go to <strong>developers.facebook.com</strong> &rarr; My Apps &rarr; your app</li>
                                    <li>Open <strong>Tools &rarr; Graph API Explorer</strong></li>
                                    <li>Select your Page from the dropdown, grant <code>pages_manage_posts</code> &amp; <code>pages_read_engagement</code></li>
                                    <li>Click <strong>Generate Access Token</strong> and exchange it for a long-lived token via the token debugger</li>
                                    <li>For permanent tokens, use a <strong>System User</strong> in Business Manager</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    {{-- Twitter / X --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-black">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.734l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">X (Twitter)</h2>
                                <p class="text-xs text-gray-500">Auto-post listings to your X account</p>
                            </div>
                            <div class="ml-auto flex items-center gap-2">
                                <span class="text-sm text-gray-600">Enable</span>
                                <input type="hidden" name="tw_enabled" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="tw_enabled" value="1" class="sr-only peer" {{ ($tw?->is_active) ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-black peer-focus:ring-2 peer-focus:ring-gray-400 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
                                </label>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">API Key (Consumer Key)</label>
                                    <input type="password" name="tw_api_key" placeholder="{{ $tw ? '••••••••••••••••' : 'API Key' }}"
                                        class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">API Secret (Consumer Secret)</label>
                                    <input type="password" name="tw_api_secret" placeholder="{{ $tw ? '••••••••••••••••' : 'API Secret' }}"
                                        class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                                    <input type="password" name="tw_access_token" placeholder="{{ $tw ? '••••••••••••••••' : 'Access Token' }}"
                                        class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Access Token Secret</label>
                                    <input type="password" name="tw_access_token_secret" placeholder="{{ $tw ? '••••••••••••••••' : 'Access Token Secret' }}"
                                        class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">Leave fields blank to keep existing credentials. All 4 values are required when setting up for the first time.</p>
                            <div>
                                <p class="text-sm font-medium text-gray-700 mb-2">Auto-post on:</p>
                                <div class="flex flex-wrap gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="hidden" name="tw_post_new_listing" value="0">
                                        <input type="checkbox" name="tw_post_new_listing" value="1" class="rounded" {{ !empty($twConfig['post_on_new_listing']) ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700">New Listings</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="hidden" name="tw_post_sold" value="0">
                                        <input type="checkbox" name="tw_post_sold" value="1" class="rounded" {{ !empty($twConfig['post_on_sold']) ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700">Sold Properties</span>
                                    </label>
                                </div>
                            </div>
                            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                                <p class="text-xs text-gray-700 font-medium mb-1">How to get your X API credentials:</p>
                                <ol class="text-xs text-gray-600 space-y-1 list-decimal list-inside">
                                    <li>Go to <strong>developer.twitter.com</strong> &rarr; Projects &amp; Apps &rarr; your app</li>
                                    <li>Under <strong>Keys and Tokens</strong>, generate API Key &amp; Secret</li>
                                    <li>Generate Access Token &amp; Secret with <strong>Read and Write</strong> permissions</li>
                                    <li>Ensure your app has <strong>OAuth 1.0a</strong> enabled with Read/Write access</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                @elseif($tab === 'data')
                {{-- DATA TAB --}}
                <div class="space-y-6">

                    {{-- Export --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Export Data</h2>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('tenant.admin.api.export', [$account, 'properties']) }}?format=json" class="px-5 py-2.5 bg-blue-50 text-blue-700 rounded-xl text-sm font-semibold hover:bg-blue-100 transition">Export Properties (JSON)</a>
                            <a href="{{ route('tenant.admin.api.export', [$account, 'properties']) }}?format=csv" class="px-5 py-2.5 bg-green-50 text-green-700 rounded-xl text-sm font-semibold hover:bg-green-100 transition">Export Properties (CSV)</a>
                            <a href="{{ route('tenant.admin.api.export', [$account, 'messages']) }}?format=json" class="px-5 py-2.5 bg-purple-50 text-purple-700 rounded-xl text-sm font-semibold hover:bg-purple-100 transition">Export Messages (JSON)</a>
                            <a href="{{ route('tenant.admin.api.export', [$account, 'messages']) }}?format=csv" class="px-5 py-2.5 bg-orange-50 text-orange-700 rounded-xl text-sm font-semibold hover:bg-orange-100 transition">Export Messages (CSV)</a>
                        </div>
                    </div>

                    {{-- Backup & Restore --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-6">Backup & Restore</h2>

                        <div class="grid md:grid-cols-2 gap-6 mb-6">

                            {{-- Backup --}}
                            <div class="space-y-3">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Backup</h3>
                                </div>
                                <p class="text-sm text-gray-500 leading-relaxed">Download a <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded font-mono">.zip</code> containing all properties, staff, appointments, messages, site settings, and image files. <span class="text-amber-600 font-medium">The backup contains sensitive data (e.g. SMTP password) — store it securely.</span></p>
                                <button id="backup-btn" type="button" onclick="doBackup()"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 btn-primary rounded-xl text-sm font-semibold hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download Backup
                                </button>
                            </div>

                            {{-- Restore --}}
                            <div class="space-y-3 md:border-l md:border-gray-100 md:pl-6">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/></svg>
                                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Restore</h3>
                                </div>
                                <p class="text-sm text-gray-500 leading-relaxed">Upload a <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded font-mono">.zip</code> backup to restore all data and image files. Existing records are updated; new ones are created. <span class="text-amber-600 font-medium">Site settings will be fully overwritten.</span></p>
                                <div class="flex flex-wrap items-center gap-3">
                                    <label class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-semibold cursor-pointer transition">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        Choose .zip file
                                        <input type="file" id="restore-file" accept=".zip" class="sr-only" onchange="updateRestoreFile(this)">
                                    </label>
                                    <span id="restore-filename" class="text-sm text-gray-400 italic truncate max-w-[160px]">No file chosen</span>
                                </div>
                                <button id="restore-btn" type="button" onclick="doRestore()"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/></svg>
                                    Restore Backup
                                </button>
                            </div>
                        </div>

                        {{-- Console --}}
                        <div class="console-wrap rounded-xl overflow-hidden border border-gray-700/50">
                            <div id="data-console-hdr" class="bg-gray-800 px-4 py-2 flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-red-400/70 inline-block"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-yellow-400/70 inline-block"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-green-400/70 inline-block"></span>
                                    <span class="ml-2 text-gray-400 text-xs font-mono">console</span>
                                </div>
                                <button type="button" onclick="clearDataConsole()" class="text-gray-500 hover:text-gray-300 text-xs font-mono transition">clear</button>
                            </div>
                            <div id="data-console" class="bg-gray-950 px-4 py-3 h-44 overflow-y-auto font-mono text-xs leading-5">
                                <div style="color:#9ca3af">— Ready. No operations yet. —</div>
                            </div>
                        </div>
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Site Description</label>
                            <textarea name="site_description" rows="3" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ $settings->site_description }}</textarea>
                            <p class="text-xs text-gray-400 mt-1">Used as the meta description for search engines. Recommended: 150-160 characters.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Default Share Image</label>
                            <div class="flex items-center gap-3">
                                @if($settings->default_share_image)
                                <img src="{{ asset('storage/' . $settings->default_share_image) }}" class="h-14 w-auto rounded object-contain shrink-0 border border-gray-100 p-1 bg-white">
                                @endif
                                <input type="file" name="default_share_image" accept="image/*" class="flex-1 border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none">
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Shown when pages are shared on social media (Open Graph image). Recommended: 1200×630px.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Google Site Verification</label>
                            <input type="text" name="google_site_verification" value="{{ $settings->google_site_verification }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono" placeholder="abc123...">
                            <p class="text-xs text-gray-400 mt-1">The content value from the Google Search Console verification meta tag.</p>
                        </div>
                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="search_engine_visibility" value="0">
                                <input type="checkbox" name="search_engine_visibility" value="1" class="rounded" {{ ($settings->search_engine_visibility ?? true) ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700">Allow search engines to index this site</span>
                            </label>
                            <p class="text-xs text-gray-400 mt-1 ml-7">Uncheck to add a <code>noindex, nofollow</code> robots tag to all pages.</p>
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

// ── Backup & Restore ─────────────────────────────────────────────────────────
const _buUrl  = '{{ route('tenant.admin.api.backup',  $account) }}';
const _reUrl  = '{{ route('tenant.admin.api.restore', $account) }}';
const _csrf   = '{{ csrf_token() }}';
const _spin   = '<svg class="w-4 h-4 animate-spin inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>';

function dclog(msg, type) {
    const el   = document.getElementById('data-console');
    if (!el) return;
    const dark = document.documentElement.classList.contains('dark');
    const ts   = new Date().toTimeString().slice(0, 8);
    const tsColor = dark ? '#4b5563' : '#9ca3af';
    const cols = dark
        ? { info: '#94a3b8', success: '#4ade80', error: '#f87171', warn: '#fbbf24' }
        : { info: '#4b5563', success: '#16a34a', error: '#dc2626', warn: '#d97706' };
    const col = cols[type || 'info'];
    const pre = { success: '✓', error: '✗', warn: '⚠', info: '›' }[type || 'info'];
    const d = document.createElement('div');
    d.style.marginBottom = '1px';
    d.innerHTML = `<span style="color:${tsColor};user-select:none">[${ts}]</span> <span style="color:${col}">${pre} ${msg}</span>`;
    el.appendChild(d);
    el.scrollTop = el.scrollHeight;
}

function clearDataConsole() {
    const el = document.getElementById('data-console');
    if (el) el.innerHTML = '<div style="color:#9ca3af">— Cleared. —</div>';
}

function updateRestoreFile(input) {
    const el = document.getElementById('restore-filename');
    if (el) el.textContent = input.files[0] ? input.files[0].name : 'No file chosen';
}

async function doBackup() {
    const btn = document.getElementById('backup-btn');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = _spin + ' Creating...';
    clearDataConsole();
    dclog('Starting backup...');
    try {
        dclog('Requesting archive from server...');
        const r = await fetch(_buUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': _csrf } });
        if (!r.ok) throw new Error('Server error: ' + r.status);
        const blob = await r.blob();
        const kb   = (blob.size / 1024).toFixed(1);
        dclog('Archive ready — ' + kb + ' KB');
        const date     = new Date().toISOString().slice(0, 10);
        const filename = 'backup-' + date + '.zip';
        const url      = URL.createObjectURL(blob);
        const a        = document.createElement('a');
        a.href = url; a.download = filename;
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        URL.revokeObjectURL(url);
        dclog('Download started: ' + filename, 'success');
        dclog('Backup complete.', 'success');
    } catch (e) {
        dclog('Backup failed: ' + e.message, 'error');
    }
    btn.disabled = false;
    btn.innerHTML = orig;
}

async function doRestore() {
    const fileInput = document.getElementById('restore-file');
    const btn       = document.getElementById('restore-btn');
    if (!fileInput.files[0]) { dclog('No file selected — choose a .zip backup first.', 'warn'); return; }
    const file = fileInput.files[0];
    const orig = btn.innerHTML;
    btn.disabled  = true;
    btn.innerHTML = _spin + ' Restoring...';
    clearDataConsole();
    dclog('File: ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)');
    const fd = new FormData();
    fd.append('backup', file);
    fd.append('_token', _csrf);
    try {
        dclog('Uploading to server...');
        const r = await fetch(_reUrl, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
        dclog('Processing archive...');
        const d = await r.json();
        if (!r.ok || !d.success) throw new Error(d.message || 'Restore failed');
        dclog('Properties restored: ' + d.properties, 'success');
        if (d.images)       dclog('Property images restored: ' + d.images, 'success');
        if (d.staff)        dclog('Staff members restored: ' + d.staff, 'success');
        if (d.appointments) dclog('Appointments restored: ' + d.appointments, 'success');
        if (d.messages)     dclog('Messages restored: ' + d.messages, 'success');
        if (d.legal_pages)  dclog('Legal pages restored: ' + d.legal_pages, 'success');
        if (d.settings)     dclog('Site settings restored.', 'success');
        if (d.files)        dclog('Image files restored: ' + d.files, 'success');
        dclog('Restore complete.', 'success');
        fileInput.value = '';
        const fn = document.getElementById('restore-filename');
        if (fn) fn.textContent = 'No file chosen';
    } catch (e) {
        dclog('Restore failed: ' + e.message, 'error');
    }
    btn.disabled  = false;
    btn.innerHTML = orig;
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
