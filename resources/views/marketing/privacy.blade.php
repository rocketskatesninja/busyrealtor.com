@extends('layouts.marketing')
@section('title', 'Privacy Policy — BusyRealtor')
@section('description', 'Learn how BusyRealtor collects, uses, and protects your information.')

@section('content')

{{-- NAV --}}
<header class="bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="/" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                </div>
                <span class="font-bold text-gray-900 dark:text-white text-lg">BusyRealtor</span>
            </a>
            <div class="flex items-center gap-4">
                <a href="/" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">&larr; Back to Home</a>
                <a href="/register" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">Get Started Free</a>
            </div>
        </div>
    </div>
</header>

{{-- PAGE HERO --}}
<div style="background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 45%, #4338ca 100%);" class="py-16">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <p class="text-blue-300 text-sm font-medium uppercase tracking-widest mb-3">Legal</p>
        <h1 class="text-4xl font-black text-white mb-4">Privacy Policy</h1>
        <p class="text-blue-200 text-lg">We respect your privacy and are committed to protecting your personal data.</p>
        <p class="text-blue-300 text-sm mt-4">Last updated: March 1, 2026 &nbsp;·&nbsp; Effective: March 1, 2026</p>
    </div>
</div>

{{-- CONTENT --}}
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-16">
        <div class="flex gap-12 items-start">

            {{-- TOC SIDEBAR --}}
            <aside class="hidden lg:block w-64 flex-shrink-0 sticky top-24">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5">
                    <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4">Contents</p>
                    <nav class="space-y-1 text-sm">
                        @php $toc = [
                            'overview'        => 'Overview',
                            'data-collected'  => 'Information We Collect',
                            'how-we-use'      => 'How We Use It',
                            'sharing'         => 'How We Share It',
                            'cookies'         => 'Cookies & Tracking',
                            'security'        => 'Data Security',
                            'retention'       => 'Data Retention',
                            'your-rights'     => 'Your Rights',
                            'third-parties'   => 'Third-Party Services',
                            'children'        => "Children's Privacy",
                            'changes'         => 'Policy Changes',
                            'contact'         => 'Contact Us',
                        ]; @endphp
                        @foreach($toc as $anchor => $label)
                        <a href="#{{ $anchor }}" class="block px-3 py-1.5 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">{{ $label }}</a>
                        @endforeach
                    </nav>
                </div>
            </aside>

            {{-- MAIN CONTENT --}}
            <article class="flex-1 min-w-0 max-w-3xl">

                {{-- Intro card --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-6 mb-8">
                    <p class="text-blue-800 dark:text-blue-200 text-sm leading-relaxed">
                        This Privacy Policy describes how <strong>Punchlist Labs</strong> ("we," "our," or "us") collects, uses, and shares information when you use the <strong>BusyRealtor</strong> platform and related services (collectively, the "Service"). By accessing or using the Service, you agree to the practices described in this policy.
                    </p>
                </div>

                <div class="space-y-10">

                    {{-- 1 --}}
                    <section id="overview" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">1</span>
                            Overview
                        </h2>
                        <div class="prose prose-gray text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>BusyRealtor is a software-as-a-service (SaaS) platform that enables licensed real estate agents and brokerages ("Subscribers") to create and manage professional real estate websites. This policy applies to our Subscribers — the realtors and team members who create accounts on BusyRealtor.com.</p>
                            <p>If you are a visitor to a website <em>built using</em> BusyRealtor (i.e., a home buyer browsing a realtor's site), your data is handled by that realtor under their own privacy policy. We process it on their behalf as a data processor.</p>
                            <p>We do not sell your personal information to third parties. We are not in the business of advertising — we are in the business of building great software.</p>
                        </div>
                    </section>

                    {{-- 2 --}}
                    <section id="data-collected" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">2</span>
                            Information We Collect
                        </h2>
                        <div class="space-y-5 text-sm leading-relaxed text-gray-700">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Account Information</h3>
                                <p>When you register for BusyRealtor, we collect your name, email address, password (stored as a secure hash), and the name of your brokerage or agency. We use this to create and manage your account.</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Billing Information</h3>
                                <p>Subscription payments are processed by Stripe. We do not store your full credit card number, CVV, or bank account details on our servers. We receive and store a payment token, the last four digits of your card, your billing name, and transaction records.</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Content You Create</h3>
                                <p>This includes property listings, photos, staff profiles, site settings, chatbot configurations, and any other content you upload or generate through the platform. This content is stored on your behalf and is yours.</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Usage Data</h3>
                                <p>We collect standard server logs including your IP address, browser type, pages visited, and timestamps. This helps us diagnose issues, improve performance, and detect abuse.</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Data From Your Site Visitors</h3>
                                <p>When visitors contact you through your BusyRealtor site (contact forms, appointment requests, chatbot conversations), that data is stored in your account's database. You are the data controller for this information; we process it on your behalf.</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Communications</h3>
                                <p>If you contact us via email or a support channel, we retain those communications to assist you and improve our service.</p>
                            </div>
                        </div>
                    </section>

                    {{-- 3 --}}
                    <section id="how-we-use" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">3</span>
                            How We Use Your Information
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>We use the information we collect to:</p>
                            <ul class="list-none space-y-2 mt-3">
                                @php $uses = [
                                    'Provide, operate, and maintain the Service',
                                    'Process subscription payments and send billing-related communications',
                                    'Send transactional emails (account confirmations, password resets, billing receipts)',
                                    'Respond to support requests and troubleshoot issues',
                                    'Monitor platform health, detect errors, and prevent fraud or abuse',
                                    'Improve and develop new features based on aggregate usage patterns',
                                    'Comply with legal obligations',
                                ]; @endphp
                                @foreach($uses as $use)
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    <span>{{ $use }}</span>
                                </li>
                                @endforeach
                            </ul>
                            <p class="mt-4">We do <strong>not</strong> use your data or your clients' data for advertising purposes, and we do not sell data to brokers, marketers, or other third parties.</p>
                        </div>
                    </section>

                    {{-- 4 --}}
                    <section id="sharing" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">4</span>
                            How We Share Your Information
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-4">
                            <p>We share information only in the following circumstances:</p>
                            <div class="space-y-4 mt-2">
                                <div class="border-l-2 border-blue-200 dark:border-blue-700 pl-4">
                                    <p class="font-semibold text-gray-900 dark:text-white mb-1">Service Providers</p>
                                    <p>We engage trusted third-party vendors to help deliver the Service — including cloud hosting, payment processing, and transactional email. These vendors are contractually bound to process data only on our instructions and may not use it for their own purposes.</p>
                                </div>
                                <div class="border-l-2 border-blue-200 dark:border-blue-700 pl-4">
                                    <p class="font-semibold text-gray-900 dark:text-white mb-1">Legal Requirements</p>
                                    <p>We may disclose information if required by law, subpoena, court order, or if we believe in good faith that disclosure is necessary to protect our rights, your safety, or the safety of others.</p>
                                </div>
                                <div class="border-l-2 border-blue-200 dark:border-blue-700 pl-4">
                                    <p class="font-semibold text-gray-900 dark:text-white mb-1">Business Transfers</p>
                                    <p>If Punchlist Labs is acquired by or merges with another company, your information may be transferred as part of that transaction. We will notify you before your information becomes subject to a different privacy policy.</p>
                                </div>
                                <div class="border-l-2 border-blue-200 dark:border-blue-700 pl-4">
                                    <p class="font-semibold text-gray-900 dark:text-white mb-1">With Your Consent</p>
                                    <p>We may share information for any other purpose with your explicit consent.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- 5 --}}
                    <section id="cookies" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">5</span>
                            Cookies &amp; Tracking
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>We use cookies and similar technologies to operate the Service:</p>
                            <div class="grid gap-3 mt-3">
                                @php $cookies = [
                                    ['name' => 'Session Cookie', 'desc' => 'Keeps you logged in during your browser session. Expires when you close the browser or after a period of inactivity.'],
                                    ['name' => 'CSRF Token', 'desc' => 'Protects against cross-site request forgery attacks. Required for the application to function securely.'],
                                    ['name' => 'Preference Cookie', 'desc' => 'Stores lightweight UI preferences such as dark mode selection, stored in localStorage.'],
                                ]; @endphp
                                @foreach($cookies as $c)
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                                    <p class="font-semibold text-gray-900 dark:text-white text-xs mb-1">{{ $c['name'] }}</p>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $c['desc'] }}</p>
                                </div>
                                @endforeach
                            </div>
                            <p class="mt-3">We do not use advertising cookies or third-party tracking pixels. If you enable Google Analytics through the integrations panel, that is your own GA property and is governed by Google's privacy policy.</p>
                        </div>
                    </section>

                    {{-- 6 --}}
                    <section id="security" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">6</span>
                            Data Security
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>We implement industry-standard technical and organizational measures to protect your data against unauthorized access, alteration, disclosure, or destruction. These measures include:</p>
                            <ul class="list-none space-y-2 mt-2">
                                @php $sec = [
                                    'All data in transit is encrypted using TLS/HTTPS',
                                    'Passwords are hashed with bcrypt before storage — we never store plaintext passwords',
                                    'Payment card data is handled exclusively by Stripe (PCI DSS compliant)',
                                    'Database access is restricted to application servers on a private network',
                                    'Regular security updates applied to server software and dependencies',
                                ]; @endphp
                                @foreach($sec as $s)
                                <li class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    <span>{{ $s }}</span>
                                </li>
                                @endforeach
                            </ul>
                            <p class="mt-3">No method of transmission over the internet or electronic storage is 100% secure. While we strive to protect your information, we cannot guarantee absolute security. In the event of a data breach that affects your rights, we will notify you as required by applicable law.</p>
                        </div>
                    </section>

                    {{-- 7 --}}
                    <section id="retention" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">7</span>
                            Data Retention
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>We retain your account data and content for as long as your subscription is active. After cancellation or account closure:</p>
                            <ul class="list-none space-y-2 mt-2">
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg><span>Your account is deactivated and your public site is taken offline immediately.</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg><span>Your data is retained for 30 days in case you wish to reactivate or export.</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg><span>After 30 days, your account data and uploaded files are permanently deleted from our systems.</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg><span>Billing records and invoices are retained for 7 years as required by law.</span></li>
                            </ul>
                            <p class="mt-3">You can export all your data at any time from the <strong>Settings → Data</strong> section of your dashboard.</p>
                        </div>
                    </section>

                    {{-- 8 --}}
                    <section id="your-rights" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">8</span>
                            Your Rights
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>Depending on your location, you may have the following rights regarding your personal data:</p>
                            <div class="grid sm:grid-cols-2 gap-3 mt-3">
                                @php $rights = [
                                    ['title'=>'Access', 'desc'=>'Request a copy of the personal data we hold about you.'],
                                    ['title'=>'Correction', 'desc'=>'Request correction of inaccurate or incomplete data.'],
                                    ['title'=>'Deletion', 'desc'=>'Request deletion of your personal data, subject to legal obligations.'],
                                    ['title'=>'Portability', 'desc'=>'Receive your data in a structured, machine-readable format. Use the export tool in your dashboard.'],
                                    ['title'=>'Restriction', 'desc'=>'Request that we limit how we process your data in certain circumstances.'],
                                    ['title'=>'Objection', 'desc'=>'Object to processing of your data for specific purposes.'],
                                ]; @endphp
                                @foreach($rights as $r)
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                                    <p class="font-semibold text-gray-900 dark:text-white text-xs mb-1">{{ $r['title'] }}</p>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs leading-relaxed">{{ $r['desc'] }}</p>
                                </div>
                                @endforeach
                            </div>
                            <p class="mt-3">To exercise any of these rights, email us at <a href="mailto:contact@punchlistify.com" class="text-blue-600 hover:underline">contact@punchlistify.com</a>. We will respond within 30 days. We may need to verify your identity before acting on a request.</p>
                        </div>
                    </section>

                    {{-- 9 --}}
                    <section id="third-parties" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">9</span>
                            Third-Party Services
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                            <p class="mb-4">The Service integrates with the following third-party providers. Each has its own privacy policy:</p>
                            <div class="divide-y divide-gray-200 border border-gray-200 rounded-xl overflow-hidden">
                                @php $vendors = [
                                    ['name'=>'Stripe',           'purpose'=>'Payment processing and subscription billing',     'url'=>'https://stripe.com/privacy'],
                                    ['name'=>'Google Maps',      'purpose'=>'Interactive property map (requires API key)',      'url'=>'https://policies.google.com/privacy'],
                                    ['name'=>'Google Analytics', 'purpose'=>'Optional — configured per-account by Subscriber', 'url'=>'https://policies.google.com/privacy'],
                                    ['name'=>'OpenAI / Anthropic','purpose'=>'Optional AI chatbot and description generation (requires API key)', 'url'=>'https://openai.com/policies/privacy-policy'],
                                    ['name'=>'Google Fonts',     'purpose'=>'Typefaces loaded from Google CDN on public sites', 'url'=>'https://policies.google.com/privacy'],
                                ]; @endphp
                                @foreach($vendors as $v)
                                <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white text-xs">{{ $v['name'] }}</p>
                                        <p class="text-gray-500 dark:text-gray-400 text-xs">{{ $v['purpose'] }}</p>
                                    </div>
                                    <a href="{{ $v['url'] }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline text-xs flex-shrink-0 ml-4">Privacy Policy &rarr;</a>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    {{-- 10 --}}
                    <section id="children" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">10</span>
                            Children's Privacy
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                            <p>The Service is intended for business use by real estate professionals and is not directed at children under 13 years of age. We do not knowingly collect personal information from children. If we learn that we have inadvertently collected personal information from a child under 13, we will delete that information promptly. If you believe a child has provided us with personal information, please contact us at <a href="mailto:contact@punchlistify.com" class="text-blue-600 hover:underline">contact@punchlistify.com</a>.</p>
                        </div>
                    </section>

                    {{-- 11 --}}
                    <section id="changes" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">11</span>
                            Changes to This Policy
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>We may update this Privacy Policy from time to time to reflect changes in our practices or applicable law. When we make material changes, we will:</p>
                            <ul class="list-none space-y-2 mt-2">
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>Update the "Last updated" date at the top of this page</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>Send an email notification to all active Subscribers</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>Post a notice in the platform dashboard for 30 days</span></li>
                            </ul>
                            <p>Your continued use of the Service after the effective date of any changes constitutes your acceptance of the updated policy.</p>
                        </div>
                    </section>

                    {{-- 12 --}}
                    <section id="contact" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">12</span>
                            Contact Us
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>If you have questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:</p>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-5 mt-3">
                                <p class="font-semibold text-gray-900 dark:text-white mb-1">Punchlist Labs</p>
                                <p class="text-gray-600 dark:text-gray-400">Privacy Inquiries</p>
                                <a href="mailto:contact@punchlistify.com" class="text-blue-600 hover:underline mt-1 inline-block">contact@punchlistify.com</a>
                            </div>
                            <p>We aim to respond to all privacy-related inquiries within 5 business days.</p>
                        </div>
                    </section>

                </div>{{-- /space-y-10 --}}

                {{-- Also see --}}
                <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-6 flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white text-sm">Also see our Terms of Service</p>
                        <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">The rules governing your use of the BusyRealtor platform.</p>
                    </div>
                    <a href="/terms" class="flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors">View Terms &rarr;</a>
                </div>

            </article>
        </div>
    </div>
</div>

{{-- FOOTER --}}
<footer class="bg-gray-950 text-gray-400 py-12">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid md:grid-cols-4 gap-8 mb-10">
            <div class="md:col-span-2">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    </div>
                    <span class="text-white font-bold text-lg">BusyRealtor</span>
                </div>
                <p class="text-sm leading-relaxed max-w-xs">The complete real estate website platform for modern agents. Built by <a href="https://punchlistlabs.com" class="text-blue-400 hover:text-blue-300 transition-colors">Punchlist Labs</a>.</p>
                <div class="mt-4 text-xs text-gray-500">
                    Sister products:
                    <a href="https://routepilot.pro" class="text-blue-400 hover:text-blue-300 transition-colors ml-1">RoutePilot</a>
                    <span class="mx-1">·</span>
                    <a href="https://punchlistify.com" class="text-blue-400 hover:text-blue-300 transition-colors">Punchlistify</a>
                </div>
            </div>
            <div>
                <h4 class="text-white font-semibold text-sm mb-4">Product</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/#features" class="hover:text-white transition-colors">Features</a></li>
                    <li><a href="/#pricing" class="hover:text-white transition-colors">Pricing</a></li>
                    <li><a href="/demo-realty" target="_blank" class="hover:text-white transition-colors">Live Demo</a></li>
                    <li><a href="/register" class="hover:text-white transition-colors">Get Started</a></li>
                    <li><a href="/login" class="hover:text-white transition-colors">Sign In</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold text-sm mb-4">Legal</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/privacy-policy" class="text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="/terms" class="hover:text-white transition-colors">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 pt-6 flex flex-col md:flex-row justify-between items-center gap-3 text-xs">
            <p>&copy; {{ date('Y') }} BusyRealtor &nbsp;·&nbsp; A Punchlist Labs Product</p>
            <p>Made with &#9749; for hard-working realtors everywhere</p>
        </div>
    </div>
</footer>

@endsection
