@extends('layouts.super-admin')
@section('title', 'Mailer')
@section('page-title', 'Mailing List')
@section('page-description', 'Send emails to tenant owners')

@section('content')
<style>
    .dark .mailer-card { background-color: #283548 !important; border-color: #3b4f6b !important; }
    .dark .mailer-card:hover { background-color: #2d3a4d !important; }
    .dark .mailer-card.mailer-card--selected { background-color: #1e3a5f !important; border-color: #3b82f6 !important; }
    .dark .mailer-card .mailer-card-email { color: #94a3b8 !important; }
    .dark .mailer-card .mailer-card-tenant { color: #64748b !important; }
</style>
@php
$mailerUsers = $users->map(fn ($u) => [
    'id' => $u->id,
    'name' => trim($u->first_name.' '.$u->last_name),
    'first' => $u->first_name,
    'last' => $u->last_name,
    'email' => $u->email,
    'tenant' => $u->tenant->name ?? '',
    'plan' => $u->tenant->plan ?? '',
])->values();
@endphp
<div id="mailer"
     data-users='@json($mailerUsers, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG)'
     x-data="mailerApp" x-effect="search; filterPlan; currentPage = 1" class="space-y-6">

    {{-- Compose Panel --}}
    <div class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-gray-200">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <x-icon name="envelope" class="w-5 h-5 text-blue-500" />
            Compose Email
        </h3>
        <form method="POST" action="{{ route('super.mailer.send') }}" @submit.prevent="confirmSend($event)">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="f-subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input type="text" id="f-subject" name="subject" x-model="subject" required
                           placeholder="e.g. Platform Update — March 2026"
                           class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="f-body" class="block text-sm font-medium text-gray-700 mb-1">
                        Body
                        <span class="text-gray-400 font-normal ml-2">Available variables: @{{ first_name }}, @{{ last_name }}, @{{ email }}</span>
                    </label>
                    <textarea id="f-body" name="body" x-model="body" rows="8" required
                              placeholder="Hi @{{first_name}},&#10;&#10;We're excited to share some updates..."
                              class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"></textarea>
                </div>

                {{-- Hidden selected user IDs --}}
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="user_ids[]" :value="id">
                </template>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <p class="text-sm">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-medium text-xs" style="background: rgba(59,130,246,0.15); color: #93c5fd;">
                            <svg width="14" height="14" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                            <span x-text="selectedIds.length"></span> recipients selected
                        </span>
                    </p>
                    <div class="flex gap-3">
                        <button type="button" @click="showPreview = true" :disabled="!subject || !body"
                                class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition disabled:opacity-40">
                            Preview
                        </button>
                        <button type="submit" :disabled="selectedIds.length === 0 || !subject || !body || sending"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition disabled:opacity-40 flex items-center gap-2">
                            <svg width="16" height="16" x-show="sending" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Send Email
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Recipients Panel --}}
    <div class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <svg width="20" height="20" class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Recipients
                <span class="text-sm font-normal text-gray-400">(<span x-text="visibleCount"></span> shown)</span>
            </h3>
            <div class="flex items-center gap-3 flex-wrap">
                {{-- Plan filter --}}
                <select x-model="filterPlan" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-32">
                    <option value="">All Plans</option>
                    <option value="trial">Trial</option>
                    <option value="starter">Starter</option>
                    <option value="pro">Pro</option>
                </select>
                {{-- Search --}}
                <div class="relative">
                    <x-icon name="magnifying-glass" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input type="text" x-model="search" placeholder="Search..."
                           class="border border-gray-200 rounded-lg pl-9 pr-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-48">
                </div>
            </div>
        </div>
        {{-- Mobile card list --}}
        <div class="sm:hidden">
            <label class="flex items-center gap-2 px-1 py-2 mb-2 text-sm font-medium text-gray-600">
                <input type="checkbox" :checked="allVisibleSelected" :indeterminate.prop="someVisibleSelected && !allVisibleSelected"
                       @change="toggleAllVisible()"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                Select all (<span x-text="visibleCount"></span>)
            </label>
            <div class="space-y-2">
                <template x-for="u in paginatedUsers" :key="u.id">
                    <label class="mailer-card flex items-start gap-3 p-3 rounded-xl border border-gray-100 hover:bg-gray-50 transition-colors cursor-pointer"
                           :class="selectedIds.includes(u.id) ? 'mailer-card--selected bg-blue-50/50 border-blue-200' : ''">
                        <input type="checkbox" :checked="selectedIds.includes(u.id)"
                               @change="toggleUser(u.id)"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mt-0.5">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 text-sm truncate" x-text="u.name"></p>
                            <p class="mailer-card-email text-xs text-gray-600 truncate" x-text="u.email"></p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="mailer-card-tenant text-xs text-gray-500 truncate" x-text="u.tenant || '\u2014'"></span>
                                <span x-show="u.plan" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium"
                                      :class="u.plan === 'pro' ? 'bg-purple-100 text-purple-800' : (u.plan === 'starter' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800')"
                                      x-text="u.plan ? u.plan.charAt(0).toUpperCase() + u.plan.slice(1) : ''"></span>
                            </div>
                        </div>
                    </label>
                </template>
                <template x-if="sortedUsers.length === 0">
                    <p class="py-8 text-center text-gray-400 text-sm">No users match your filters.</p>
                </template>
            </div>
        </div>

        {{-- Desktop table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="py-2 px-3 w-10">
                            <input type="checkbox" :checked="allVisibleSelected" :indeterminate.prop="someVisibleSelected && !allVisibleSelected"
                                   @change="toggleAllVisible()"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th @click="setSort('name')" class="text-left py-2 px-3 font-medium text-gray-500 cursor-pointer hover:text-gray-300 select-none">
                            <span class="inline-flex items-center gap-1">Name
                                <template x-if="sortCol === 'name'">
                                    <svg width="14" height="14" class="w-3.5 h-3.5 transition-transform" :class="sortDir === 'desc' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                </template>
                            </span>
                        </th>
                        <th @click="setSort('email')" class="text-left py-2 px-3 font-medium text-gray-500 cursor-pointer hover:text-gray-300 select-none">
                            <span class="inline-flex items-center gap-1">Email
                                <template x-if="sortCol === 'email'">
                                    <svg width="14" height="14" class="w-3.5 h-3.5 transition-transform" :class="sortDir === 'desc' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                </template>
                            </span>
                        </th>
                        <th @click="setSort('tenant')" class="text-left py-2 px-3 font-medium text-gray-500 cursor-pointer hover:text-gray-300 select-none">
                            <span class="inline-flex items-center gap-1">Tenant
                                <template x-if="sortCol === 'tenant'">
                                    <svg width="14" height="14" class="w-3.5 h-3.5 transition-transform" :class="sortDir === 'desc' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                </template>
                            </span>
                        </th>
                        <th @click="setSort('plan')" class="text-left py-2 px-3 font-medium text-gray-500 cursor-pointer hover:text-gray-300 select-none">
                            <span class="inline-flex items-center gap-1">Plan
                                <template x-if="sortCol === 'plan'">
                                    <svg width="14" height="14" class="w-3.5 h-3.5 transition-transform" :class="sortDir === 'desc' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                </template>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="u in paginatedUsers" :key="u.id">
                        <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                            <td class="py-2 px-3">
                                <input type="checkbox" :checked="selectedIds.includes(u.id)"
                                       @change="toggleUser(u.id)"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="py-2 px-3 font-medium text-gray-900" x-text="u.name"></td>
                            <td class="py-2 px-3 text-gray-600" x-text="u.email"></td>
                            <td class="py-2 px-3 text-gray-600" x-text="u.tenant || '—'"></td>
                            <td class="py-2 px-3">
                                <span x-show="u.plan" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                      :class="u.plan === 'pro' ? 'bg-purple-100 text-purple-800' : (u.plan === 'starter' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800')"
                                      x-text="u.plan ? u.plan.charAt(0).toUpperCase() + u.plan.slice(1) : ''"></span>
                                <span x-show="!u.plan" class="text-gray-400">&mdash;</span>
                            </td>
                        </tr>
                    </template>
                    <template x-if="sortedUsers.length === 0">
                        <tr><td colspan="5" class="py-8 text-center text-gray-400">No users match your filters.</td></tr>
                    </template>
                </tbody>
            </table>
        </div>
        {{-- Pagination Controls --}}
        <div x-show="totalPages > 1" class="flex items-center justify-between pt-4 border-t border-gray-100 mt-4">
            <p class="text-sm text-gray-500">
                Showing <span x-text="pageStart"></span>–<span x-text="pageEnd"></span> of <span x-text="filteredUsers.length"></span> users
            </p>
            <div class="flex items-center gap-1">
                <button @click="currentPage = 1" :disabled="currentPage === 1"
                        class="px-2.5 py-1.5 text-xs font-medium rounded-lg border border-gray-200 hover:bg-gray-50 transition disabled:opacity-30 disabled:cursor-not-allowed">&laquo;</button>
                <button @click="currentPage--" :disabled="currentPage === 1"
                        class="px-2.5 py-1.5 text-xs font-medium rounded-lg border border-gray-200 hover:bg-gray-50 transition disabled:opacity-30 disabled:cursor-not-allowed">&lsaquo;</button>
                <template x-for="p in pageNumbers" :key="p">
                    <button @click="if(p !== '...') currentPage = p"
                            :class="p === currentPage ? 'bg-blue-600 text-white border-blue-600' : (p === '...' ? 'cursor-default border-transparent' : 'border-gray-200 hover:bg-gray-50')"
                            class="px-2.5 py-1.5 text-xs font-medium rounded-lg border transition"
                            x-text="p"></button>
                </template>
                <button @click="currentPage++" :disabled="currentPage === totalPages"
                        class="px-2.5 py-1.5 text-xs font-medium rounded-lg border border-gray-200 hover:bg-gray-50 transition disabled:opacity-30 disabled:cursor-not-allowed">&rsaquo;</button>
                <button @click="currentPage = totalPages" :disabled="currentPage === totalPages"
                        class="px-2.5 py-1.5 text-xs font-medium rounded-lg border border-gray-200 hover:bg-gray-50 transition disabled:opacity-30 disabled:cursor-not-allowed">&raquo;</button>
            </div>
        </div>
    </div>

    {{-- Campaign History --}}
    @if($campaigns->count())
    <div class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-gray-200" x-data="{ historyOpen: false }">
        <button @click="historyOpen = !historyOpen" class="flex items-center justify-between w-full">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <svg width="20" height="20" class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Campaign History
                <span class="text-sm font-normal text-gray-400">({{ $campaigns->count() }})</span>
            </h3>
            <svg :class="historyOpen && 'rotate-180'" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="historyOpen" x-cloak class="mt-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left py-2 px-3 font-medium text-gray-500">Subject</th>
                        <th class="text-left py-2 px-3 font-medium text-gray-500">Recipients</th>
                        <th class="text-left py-2 px-3 font-medium text-gray-500">Sent</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaigns as $campaign)
                    <tr class="border-b border-gray-50">
                        <td class="py-2 px-3 font-medium text-gray-900">{{ $campaign->subject }}</td>
                        <td class="py-2 px-3 text-gray-600">{{ $campaign->recipient_count }}</td>
                        <td class="py-2 px-3 text-gray-600">{{ $campaign->sent_at?->format('M j, Y g:ia') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Preview Modal --}}
    <div x-show="showPreview" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showPreview = false">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full mx-4 max-h-[80vh] overflow-auto">
            <div class="p-6 border-b border-gray-100">
                <h3 class="font-bold text-gray-900">Email Preview</h3>
                <p class="text-sm text-gray-500 mt-1">Subject: <span x-text="subject" class="font-medium text-gray-700"></span></p>
            </div>
            <div class="p-6 text-sm text-gray-700 whitespace-pre-wrap" x-text="body"></div>
            <div class="p-4 border-t border-gray-100 flex justify-end">
                <button @click="showPreview = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium transition">Close</button>
            </div>
        </div>
    </div>

    {{-- Confirm Modal --}}
    <div x-show="showConfirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showConfirm = false">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6">
            <h3 class="font-bold text-gray-900 text-lg mb-2">Confirm Send</h3>
            <p class="text-sm text-gray-600 mb-4">
                You are about to send "<span x-text="subject" class="font-medium"></span>" to
                <span x-text="selectedIds.length" class="font-bold text-blue-600"></span> recipients.
                This action cannot be undone.
            </p>
            <div class="flex justify-end gap-3">
                <button @click="showConfirm = false" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium hover:bg-gray-50 transition">Cancel</button>
                <button @click="doSend()" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition">
                    Yes, Send Now
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
@vite('resources/js/super-mailer.js')
@endsection
