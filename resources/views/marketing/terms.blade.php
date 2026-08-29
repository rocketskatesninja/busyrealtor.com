@extends('layouts.marketing')
@section('title', 'Terms of Service — BusyRealtor')
@section('description', 'The terms and conditions governing your use of the BusyRealtor platform.')

@section('content')

{{-- NAV --}}
<header class="bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="/" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-orange-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                </div>
                <span style="font-size:1.875rem;font-weight:800;line-height:1;"><span style="color:#7dd3fc;">Busy</span><span style="color:#fb923c;">Realtor</span></span>
            </a>
            <div class="flex items-center gap-4">
                <a href="/" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">&larr; Back to Home</a>
                <a href="/register" class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">Get Started Free</a>
            </div>
        </div>
    </div>
</header>

{{-- PAGE HERO --}}
<div style="background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 45%, #4338ca 100%);" class="py-16">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <p class="text-blue-300 text-sm font-medium uppercase tracking-widest mb-3">Legal</p>
        <h1 class="text-4xl font-black text-white mb-4">Terms of Service</h1>
        <p class="text-blue-200 text-lg">Please read these terms carefully before using the BusyRealtor platform.</p>
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
                            'acceptance'    => 'Acceptance of Terms',
                            'description'   => 'Service Description',
                            'accounts'      => 'Accounts & Registration',
                            'billing'       => 'Billing & Subscriptions',
                            'acceptable'    => 'Acceptable Use',
                            'your-content'  => 'Your Content & Data',
                            'ip'            => 'Intellectual Property',
                            'privacy'       => 'Privacy',
                            'disclaimers'   => 'Disclaimers',
                            'liability'     => 'Limitation of Liability',
                            'indemnity'     => 'Indemnification',
                            'termination'   => 'Termination',
                            'governing-law' => 'Governing Law',
                            'changes'       => 'Changes to Terms',
                            'contact'       => 'Contact Us',
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
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-2xl p-6 mb-8">
                    <p class="text-amber-900 dark:text-amber-200 text-sm leading-relaxed">
                        <strong>Agreement to Terms.</strong> These Terms of Service ("Terms") constitute a legally binding agreement between you ("Subscriber," "you," or "your") and <strong>Punchlist Labs</strong> ("Company," "we," "our," or "us") governing your access to and use of the BusyRealtor platform. By creating an account or using the Service, you agree to be bound by these Terms.
                    </p>
                </div>

                <div class="space-y-10">

                    {{-- 1 --}}
                    <section id="acceptance" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">1</span>
                            Acceptance of Terms
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>By registering for an account, clicking "Get Started," or otherwise accessing or using the BusyRealtor platform, you confirm that:</p>
                            <ul class="list-none space-y-2 mt-2">
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>You are at least 18 years of age and have legal capacity to enter into contracts</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>You have read, understood, and agree to be bound by these Terms and our Privacy Policy</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>If you are accepting on behalf of a company or brokerage, you have the authority to bind that entity to these Terms</span></li>
                            </ul>
                            <p>If you do not agree to these Terms, you must not access or use the Service.</p>
                        </div>
                    </section>

                    {{-- 2 --}}
                    <section id="description" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">2</span>
                            Service Description
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>BusyRealtor is a multi-tenant software-as-a-service platform that enables licensed real estate professionals to:</p>
                            <ul class="list-none space-y-2 mt-2">
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>Publish and manage property listings</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>Host a professional, branded real estate website</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>Manage client inquiries, appointments, and staff profiles</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span>Deploy optional AI-powered tools such as a client-facing chatbot</span></li>
                            </ul>
                            <p>We reserve the right to modify, suspend, or discontinue any feature of the Service at any time with reasonable notice. We will not materially degrade the core functionality of the Service without providing Subscribers an opportunity to export their data and cancel with a pro-rated refund.</p>
                        </div>
                    </section>

                    {{-- 3 --}}
                    <section id="accounts" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">3</span>
                            Accounts &amp; Registration
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>To access the Service, you must register and create an account. You agree to:</p>
                            <ul class="list-none space-y-2 mt-2">
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg><span>Provide accurate, current, and complete registration information</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg><span>Maintain the security of your password and not share your login credentials</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg><span>Promptly notify us of any unauthorized access to your account</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg><span>Accept responsibility for all activity that occurs under your account</span></li>
                            </ul>
                            <p>One account corresponds to one real estate business or team. You may add staff members as sub-users within your account, but sharing a single account across multiple unaffiliated businesses is prohibited.</p>
                        </div>
                    </section>

                    {{-- 4 --}}
                    <section id="billing" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">4</span>
                            Billing &amp; Subscriptions
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-4">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Subscription Plans</h3>
                                <p>BusyRealtor is offered on a recurring subscription basis. Current pricing is displayed on our <a href="/#pricing" class="text-blue-600 hover:underline">pricing page</a>. All prices are in US dollars and are subject to applicable taxes.</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Free Trial</h3>
                                <p>New accounts receive a 14-day free trial with full access to all features. No credit card is required to start a trial. At the end of the trial period, you must subscribe to continue using the Service. Unpaid accounts after the trial are suspended and subject to the data retention policy described in our Privacy Policy.</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Auto-Renewal</h3>
                                <p>Your subscription automatically renews at the end of each billing period (monthly or annual, depending on your plan). You authorize us to charge your payment method on file for each renewal period. We will send a billing receipt to your account email after each successful charge.</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Cancellation</h3>
                                <p>You may cancel your subscription at any time from the <strong>Settings → Billing</strong> section of your dashboard. Cancellation takes effect at the end of the current billing period — you will not be charged for the next period, and you retain full access until the period ends. We do not offer partial-period refunds except as required by law or described in our refund policy below.</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Refund Policy</h3>
                                <p>If you are unsatisfied with BusyRealtor within the first 14 days of a paid subscription (not the trial), contact us and we will issue a full refund — no questions asked. After 14 days, subscriptions are non-refundable except in cases of duplicate billing or technical errors on our part.</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Price Changes</h3>
                                <p>We may change subscription pricing with at least 30 days' written notice to active Subscribers. Price changes take effect at the start of your next billing period after the notice period.</p>
                            </div>
                        </div>
                    </section>

                    {{-- 5 --}}
                    <section id="acceptable" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">5</span>
                            Acceptable Use
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-4">
                            <p>You agree to use the Service only for lawful purposes and in accordance with these Terms. You must not:</p>
                            <div class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-xl p-5 space-y-2">
                                @php $prohibited = [
                                    'Publish false, misleading, or fraudulent property listings',
                                    'Violate fair housing laws, anti-discrimination regulations, or any applicable real estate licensing requirements',
                                    'Collect or harvest personal information from visitors without proper disclosure and consent',
                                    'Transmit spam, unsolicited communications, or malware through any Service feature',
                                    'Attempt to gain unauthorized access to other accounts, systems, or networks',
                                    'Reverse engineer, decompile, or extract the source code of the Service',
                                    'Resell or sublicense access to the Service without written permission from Punchlist Labs',
                                    'Use the Service to compete directly with BusyRealtor by building a competing SaaS product',
                                    'Upload content that infringes any third-party intellectual property rights',
                                    'Use AI features in a manner that violates the terms of the underlying AI provider',
                                ]; @endphp
                                @foreach($prohibited as $p)
                                <div class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-red-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                    <span class="text-red-800 dark:text-red-300">{{ $p }}</span>
                                </div>
                                @endforeach
                            </div>
                            <p>Violation of these acceptable use restrictions may result in immediate suspension or termination of your account without refund.</p>
                        </div>
                    </section>

                    {{-- 6 --}}
                    <section id="your-content" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">6</span>
                            Your Content &amp; Data
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p><strong>Ownership.</strong> You retain full ownership of all content you create, upload, or submit through the Service — including property listings, photographs, text, and client data. We do not claim any ownership rights in your content.</p>
                            <p><strong>License to us.</strong> By uploading content to the Service, you grant Punchlist Labs a limited, worldwide, royalty-free license to store, display, and transmit your content solely for the purpose of providing and improving the Service. We will not use your property listing photographs or client data for marketing our own services without your explicit consent.</p>
                            <p><strong>Your responsibility.</strong> You are solely responsible for all content you upload. You represent and warrant that: (a) you have all rights necessary to grant us the above license; (b) your content does not infringe any third party's intellectual property, privacy, or other rights; and (c) your content does not violate any applicable law.</p>
                            <p><strong>Data portability.</strong> You can export all your data at any time from the Settings dashboard. We support export in JSON and CSV formats.</p>
                        </div>
                    </section>

                    {{-- 7 --}}
                    <section id="ip" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">7</span>
                            Intellectual Property
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>The BusyRealtor platform — including its software, design, user interface, logos, trademarks, and documentation — is the exclusive property of Punchlist Labs and is protected by copyright, trademark, and other intellectual property laws.</p>
                            <p>These Terms grant you a limited, non-exclusive, non-transferable, revocable license to access and use the Service for your internal business purposes. You may not copy, modify, distribute, sell, or create derivative works from any part of the Service without our prior written consent.</p>
                            <p>The "BusyRealtor" name, logo, and any associated marks are trademarks of Punchlist Labs. Nothing in these Terms grants you the right to use our trademarks.</p>
                        </div>
                    </section>

                    {{-- 8 --}}
                    <section id="privacy" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">8</span>
                            Privacy
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                            <p>Your use of the Service is also governed by our <a href="/privacy-policy" class="text-blue-600 hover:underline">Privacy Policy</a>, which is incorporated into these Terms by reference. By accepting these Terms, you also agree to our Privacy Policy.</p>
                            <p class="mt-3">As a Subscriber, you are the data controller for any personal information collected from your site visitors (e.g., contact form submissions, appointment requests). You are responsible for maintaining your own privacy policy on your BusyRealtor-powered site and for complying with all applicable data protection laws (including, where applicable, GDPR and CCPA).</p>
                        </div>
                    </section>

                    {{-- 9 --}}
                    <section id="disclaimers" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">9</span>
                            Disclaimers
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p class="uppercase text-xs font-semibold text-gray-400 tracking-wide">Important — please read carefully</p>
                            <p>THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT.</p>
                            <p>We do not warrant that: (a) the Service will be uninterrupted or error-free; (b) defects will be corrected; (c) the Service or its servers are free of viruses or other harmful components; or (d) the results obtained from using the Service will be accurate or reliable.</p>
                            <p>BusyRealtor provides tools to help you build and operate a real estate website. We make no warranties or representations regarding actual results such as lead generation, listing inquiries, or transaction volume — those outcomes depend on many factors outside our control.</p>
                        </div>
                    </section>

                    {{-- 10 --}}
                    <section id="liability" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">10</span>
                            Limitation of Liability
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, PUNCHLIST LABS SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING LOSS OF PROFITS, DATA, GOODWILL, OR BUSINESS OPPORTUNITIES, ARISING OUT OF OR RELATING TO YOUR USE OF OR INABILITY TO USE THE SERVICE.</p>
                            <p>IN NO EVENT WILL OUR TOTAL CUMULATIVE LIABILITY TO YOU FOR ALL CLAIMS ARISING OUT OF OR RELATING TO THESE TERMS OR THE SERVICE EXCEED THE GREATER OF: (A) THE AMOUNTS ACTUALLY PAID BY YOU TO US IN THE TWELVE (12) MONTHS PRECEDING THE CLAIM; OR (B) ONE HUNDRED US DOLLARS ($100).</p>
                            <p>Some jurisdictions do not allow the exclusion or limitation of certain damages, so the above limitations may not apply to you.</p>
                        </div>
                    </section>

                    {{-- 11 --}}
                    <section id="indemnity" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">11</span>
                            Indemnification
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>You agree to defend, indemnify, and hold harmless Punchlist Labs and its officers, directors, employees, and agents from and against any claims, liabilities, damages, losses, and expenses (including reasonable attorneys' fees) arising out of or related to:</p>
                            <ul class="list-none space-y-2 mt-2">
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg><span>Your violation of these Terms or any applicable law</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg><span>Content you post through the Service that infringes a third party's rights</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg><span>Your collection and handling of personal data from your site visitors</span></li>
                                <li class="flex items-start gap-2"><svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg><span>Any disputes between you and clients or visitors of your BusyRealtor site</span></li>
                            </ul>
                        </div>
                    </section>

                    {{-- 12 --}}
                    <section id="termination" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">12</span>
                            Termination
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p><strong>By you.</strong> You may cancel your subscription and close your account at any time through your billing settings. Cancellation of a paid subscription takes effect at the end of the current billing period.</p>
                            <p><strong>By us.</strong> We may suspend or terminate your account at any time if: (a) you materially breach these Terms and fail to cure the breach within 7 days of notice; (b) you engage in conduct that poses a legal or reputational risk to Punchlist Labs or other Subscribers; (c) we are required to do so by law; or (d) you fail to pay subscription fees after a reasonable grace period.</p>
                            <p><strong>Effect of termination.</strong> Upon termination, your right to access the Service ends immediately. We will make your data available for export for 30 days post-termination, after which it will be permanently deleted per our Privacy Policy. Termination does not relieve you of any payment obligations incurred prior to the termination date.</p>
                        </div>
                    </section>

                    {{-- 13 --}}
                    <section id="governing-law" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">13</span>
                            Governing Law &amp; Disputes
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>These Terms are governed by and construed in accordance with the laws of the United States and the State in which Punchlist Labs is registered, without regard to conflict of law principles.</p>
                            <p>Before filing any formal dispute, you agree to first contact us at <a href="mailto:contact@busyrealtor.com" class="text-blue-600 hover:underline">contact@busyrealtor.com</a> and attempt to resolve the issue informally. We will make good-faith efforts to resolve disputes within 30 days.</p>
                            <p>Any dispute that cannot be resolved informally will be submitted to binding individual arbitration under the rules of a mutually agreed arbitration provider. <strong>Class action lawsuits and class-wide arbitration are not permitted under these Terms.</strong></p>
                        </div>
                    </section>

                    {{-- 14 --}}
                    <section id="changes" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">14</span>
                            Changes to These Terms
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>We may update these Terms from time to time. When we make material changes, we will provide at least 30 days' advance notice by: (a) emailing all active Subscribers; and (b) posting a prominent notice in the platform dashboard.</p>
                            <p>Your continued use of the Service after the effective date of any changes constitutes your acceptance of the revised Terms. If you do not agree to the revised Terms, you must cancel your subscription before the effective date.</p>
                        </div>
                    </section>

                    {{-- 15 --}}
                    <section id="contact" class="bg-white rounded-2xl border border-gray-200 p-8 scroll-mt-24">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center flex-shrink-0">15</span>
                            Contact Us
                        </h2>
                        <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 space-y-3">
                            <p>Questions about these Terms? We're happy to clarify anything.</p>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-5 mt-3">
                                <p class="font-semibold text-gray-900 dark:text-white mb-1">Punchlist Labs</p>
                                <p class="text-gray-600 dark:text-gray-400">Legal Inquiries</p>
                                <a href="mailto:contact@busyrealtor.com" class="text-blue-600 hover:underline mt-1 inline-block">contact@busyrealtor.com</a>
                            </div>
                        </div>
                    </section>

                </div>{{-- /space-y-10 --}}

                {{-- Also see --}}
                <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-6 flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white text-sm">Also see our Privacy Policy</p>
                        <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">How we collect, use, and protect your data.</p>
                    </div>
                    <a href="/privacy-policy" class="flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors">View Privacy Policy &rarr;</a>
                </div>

            </article>
        </div>
    </div>
</div>

{{-- FOOTER --}}
<footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 py-4">
    <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
        <p class="text-sm text-gray-500 dark:text-gray-400">&copy; {{ date('Y') }} Punchlist Labs. All rights reserved.</p>
        <div class="flex gap-4">
            <a href="/privacy-policy" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">Privacy Policy</a>
            <a href="/terms" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">Terms of Service</a>
        </div>
    </div>
</footer>

@endsection
