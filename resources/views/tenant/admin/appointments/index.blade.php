@extends('layouts.admin')
@section('title', 'Appointments')
@section('page-subtitle', 'Scheduling requests from clients')
@section('content')
@php
$account = $tenant->slug;
$siteSettings = \App\Models\SiteSettings::where('tenant_id', $tenant->id)->first();
$gcalConnected = \App\Models\Integration::where('tenant_id', $tenant->id)
    ->where('integration_type', 'google_calendar')->where('is_active', true)->exists();
$properties = \App\Models\Property::where('tenant_id', $tenant->id)->where('listing_status', 'active')->orderBy('title')->get();
$staffMembers = \App\Models\StaffMember::where('tenant_id', $tenant->id)->where('accepts_appointments', true)->orderBy('name')->get();
@endphp
<div class="max-w-7xl mx-auto px-4">

    {{-- Make Appointment Button --}}
    <div class="flex justify-end mb-6">
        <button onclick="document.getElementById('add-appt-panel').style.display = document.getElementById('add-appt-panel').style.display === 'none' ? 'block' : 'none'"
                class="btn-primary px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Make Appointment
        </button>
    </div>

    {{-- Add Appointment Panel --}}
    <div id="add-appt-panel" style="display:none" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 mb-8">
        <h3 class="font-semibold text-gray-800 dark:text-white mb-4">New Appointment</h3>
        <form method="POST" action="{{ route('tenant.admin.appointments.store', $account) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4" x-data="{ apptStatus: 'confirmed' }">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client Name *</label>
                <input type="text" name="visitor_name" value="{{ old('visitor_name') }}" required class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                <input type="email" name="visitor_email" value="{{ old('visitor_email') }}" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                <input type="tel" name="visitor_phone" value="{{ old('visitor_phone') }}" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type *</label>
                <select name="appointment_type" required class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                    <option value="showing" {{ old('appointment_type', 'showing') === 'showing' ? 'selected' : '' }}>Showing</option>
                    <option value="open_house" {{ old('appointment_type') === 'open_house' ? 'selected' : '' }}>Open House</option>
                    <option value="listing_appointment" {{ old('appointment_type') === 'listing_appointment' ? 'selected' : '' }}>Listing Appointment</option>
                    <option value="consultation" {{ old('appointment_type') === 'consultation' ? 'selected' : '' }}>Consultation</option>
                    <option value="closing" {{ old('appointment_type') === 'closing' ? 'selected' : '' }}>Closing</option>
                    <option value="inspection" {{ old('appointment_type') === 'inspection' ? 'selected' : '' }}>Inspection</option>
                    <option value="other" {{ old('appointment_type') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date *</label>
                <input type="date" name="appointment_date" value="{{ old('appointment_date', now()->format('Y-m-d')) }}" required class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Time</label>
                <input type="time" name="appointment_time" value="{{ old('appointment_time', '10:00') }}" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Property</label>
                <select name="property_id" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                    <option value="">— None —</option>
                    @foreach($properties as $prop)
                        <option value="{{ $prop->id }}" {{ old('property_id') == $prop->id ? 'selected' : '' }}>{{ $prop->title ?? $prop->address_street }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign To</label>
                <select name="staff_member_id" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                    <option value="">— Unassigned —</option>
                    @foreach($staffMembers as $staff)
                        <option value="{{ $staff->id }}" {{ old('staff_member_id') == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)] resize-none">{{ old('notes') }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Initial Status</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="radio" name="status" value="confirmed" x-model="apptStatus" class="text-[var(--primary)]"> Confirmed
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="radio" name="status" value="pending" x-model="apptStatus" class="text-[var(--primary)]"> Pending
                    </label>
                </div>
            </div>
            <div class="md:col-span-2" x-show="apptStatus === 'confirmed'" x-collapse x-cloak>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notifications</label>
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    @if($gcalConnected)
                    <label class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" name="send_calendar" value="1" checked class="rounded text-[var(--primary)] w-3.5 h-3.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Add to Google Calendar
                    </label>
                    @endif
                    <label class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" name="send_visitor_email" value="1" class="rounded text-[var(--primary)] w-3.5 h-3.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Email client
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" name="send_admin_email" value="1" class="rounded text-[var(--primary)] w-3.5 h-3.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Email me a copy
                    </label>
                </div>
            </div>
            <div class="md:col-span-2 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('add-appt-panel').style.display='none'" class="px-5 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
                <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">Create Appointment</button>
            </div>
        </form>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 mb-6">
        <form method="GET" class="flex flex-wrap gap-3 items-end justify-center md:justify-start">
            <div class="w-full md:flex-1 md:min-w-40">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search appointments..." class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            </div>
            <select name="status" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                <option value="">All Statuses</option>
                @foreach(['pending'=>'Pending','confirmed'=>'Confirmed','completed'=>'Completed','cancelled'=>'Cancelled'] as $v=>$l)
                    <option value="{{ $v }}" {{ request('status')===$v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="From" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="To" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            <select name="sort" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none">
                <option value="default" {{ request('sort','default')==='default' ? 'selected' : '' }}>Date (soonest)</option>
                <option value="newest"  {{ request('sort')==='newest'  ? 'selected' : '' }}>Date (latest)</option>
                <option value="created" {{ request('sort')==='created' ? 'selected' : '' }}>Recently Added</option>
            </select>
            <button type="submit" class="btn-primary px-5 py-2.5 rounded-lg font-semibold text-sm hover:opacity-90 transition">Filter</button>
            @if(request()->hasAny(['search','status','date_from','date_to','sort'])) <a href="{{ route('tenant.admin.appointments.index', $account) }}" class="text-sm text-gray-500 hover:text-gray-700 py-2.5">Clear</a> @endif
        </form>
    </div>

    {{-- Bulk delete cancelled --}}
    @php $cancelledCount = \App\Models\Appointment::where('tenant_id', $tenant->id)->where('status','cancelled')->count(); @endphp
    @if($cancelledCount > 0)
    <form method="POST" action="{{ route('tenant.admin.appointments.bulk', $account) }}" class="mb-4"
          onsubmit="return confirm('Delete all {{ $cancelledCount }} cancelled appointment(s)? This cannot be undone.')">
        @csrf
        @foreach(\App\Models\Appointment::where('tenant_id', $tenant->id)->where('status','cancelled')->pluck('id') as $cid)
            <input type="hidden" name="ids[]" value="{{ $cid }}">
        @endforeach
        <input type="hidden" name="action" value="delete">
        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-lg text-sm font-medium hover:bg-red-100 dark:hover:bg-red-900/30 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Delete all cancelled ({{ $cancelledCount }})
        </button>
    </form>
    @endif

    {{-- Appointments Grid --}}
    @if($appointments->count())
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($appointments as $appt)
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
            {{-- Header: name + status --}}
            <div class="flex items-start justify-between mb-3">
                <div class="min-w-0">
                    <p class="font-semibold text-gray-800 dark:text-white text-sm truncate">{{ $appt->visitor_name }}</p>
                    @if($appt->visitor_email)<p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $appt->visitor_email }}</p>@endif
                    @if($appt->visitor_phone)<p class="text-xs text-gray-500 dark:text-gray-400">{{ $appt->visitor_phone }}</p>@endif
                </div>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full shrink-0 ml-2 {{ match($appt->status) { 'confirmed' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400', 'pending' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400', 'completed' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400', default => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' } }}">{{ ucfirst($appt->status) }}</span>
            </div>

            {{-- Date/time --}}
            @if($appt->appointment_date)
            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 mb-1">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>{{ \Carbon\Carbon::parse($appt->appointment_date)->format('M j, Y') }}@if($appt->appointment_time) at {{ \Carbon\Carbon::parse($appt->appointment_time)->format('g:i A') }}@endif</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ ucwords(str_replace('_', ' ', $appt->appointment_type ?? 'showing')) }}</p>
            @endif

            {{-- Property link --}}
            @if($appt->property)
            <a href="{{ route('tenant.property', [$account, $appt->property->id]) }}" target="_blank" class="block text-xs mb-3 text-[var(--primary)] hover:underline truncate">{{ $appt->property->title ?? $appt->property->address_street }}, {{ $appt->property->address_city }}</a>
            @endif

            {{-- Notes --}}
            @if($appt->notes)
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 truncate" title="{{ $appt->notes }}">{{ Str::limit($appt->notes, 80) }}</p>
            @endif

            {{-- Source badge --}}
            @if(in_array($appt->source, ['chatbot', 'website', 'admin']))
            <div class="mb-3">
                <span class="text-xs px-2 py-0.5 rounded-full {{ match($appt->source) { 'chatbot' => 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400', 'website' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400', 'admin' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300', default => '' } }}">{{ match($appt->source) { 'chatbot' => 'chatbot', 'website' => 'website', 'admin' => 'manual', default => $appt->source } }}</span>
            </div>
            @endif

            {{-- Actions --}}
            @if($appt->status === 'pending')
            <div class="border-t border-gray-100 dark:border-gray-700 pt-3 space-y-2"
                 x-data="{
                    send_calendar: {{ ($gcalConnected && ($siteSettings->gcal_sync_appointments ?? true)) ? 'true' : 'false' }},
                    send_visitor_email: true,
                    send_admin_email: true
                 }">
                <div class="flex flex-col gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                    @if($gcalConnected)
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" x-model="send_calendar" class="rounded text-[var(--primary)] w-3.5 h-3.5">
                        Add to Google Calendar
                    </label>
                    @endif
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" x-model="send_visitor_email" class="rounded text-[var(--primary)] w-3.5 h-3.5">
                        Email visitor
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" x-model="send_admin_email" class="rounded text-[var(--primary)] w-3.5 h-3.5">
                        Email me a copy
                    </label>
                </div>
                <div class="flex items-center gap-1.5">
                    <form method="POST" action="{{ route('tenant.admin.appointments.action', [$account, $appt->id]) }}">
                        @csrf <input type="hidden" name="status" value="confirmed">
                        <template x-if="send_calendar"><input type="hidden" name="send_calendar" value="1"></template>
                        <template x-if="send_visitor_email"><input type="hidden" name="send_visitor_email" value="1"></template>
                        <template x-if="send_admin_email"><input type="hidden" name="send_admin_email" value="1"></template>
                        <button type="submit" class="px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg text-xs font-semibold hover:bg-green-200 dark:hover:bg-green-900/50 transition">Confirm</button>
                    </form>
                    <form method="POST" action="{{ route('tenant.admin.appointments.action', [$account, $appt->id]) }}">
                        @csrf <input type="hidden" name="status" value="cancelled">
                        <template x-if="send_visitor_email"><input type="hidden" name="send_visitor_email" value="1"></template>
                        <template x-if="send_admin_email"><input type="hidden" name="send_admin_email" value="1"></template>
                        <button type="submit" class="px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg text-xs font-semibold hover:bg-red-200 dark:hover:bg-red-900/50 transition">Decline</button>
                    </form>
                    <form method="POST" action="{{ route('tenant.admin.appointments.action', [$account, $appt->id]) }}" class="ml-auto" onsubmit="return confirm('Delete this appointment?')">
                        @csrf <input type="hidden" name="status" value="delete">
                        <button type="submit" class="p-1.5 text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 rounded transition" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>
            @elseif($appt->status === 'confirmed')
            <div class="flex items-center gap-1.5 border-t border-gray-100 dark:border-gray-700 pt-3">
                <form method="POST" action="{{ route('tenant.admin.appointments.action', [$account, $appt->id]) }}">
                    @csrf <input type="hidden" name="status" value="completed">
                    <button type="submit" class="px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg text-xs font-semibold hover:bg-blue-200 dark:hover:bg-blue-900/50 transition">Complete</button>
                </form>
                <form method="POST" action="{{ route('tenant.admin.appointments.action', [$account, $appt->id]) }}">
                    @csrf <input type="hidden" name="status" value="cancelled">
                    <button type="submit" class="px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg text-xs font-semibold hover:bg-red-200 dark:hover:bg-red-900/50 transition">Cancel</button>
                </form>
                <form method="POST" action="{{ route('tenant.admin.appointments.action', [$account, $appt->id]) }}" class="ml-auto" onsubmit="return confirm('Delete this appointment?')">
                    @csrf <input type="hidden" name="status" value="delete">
                    <button type="submit" class="p-1.5 text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 rounded transition" title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            </div>
            @else
            <div class="flex items-center gap-1.5 border-t border-gray-100 dark:border-gray-700 pt-3">
                <form method="POST" action="{{ route('tenant.admin.appointments.action', [$account, $appt->id]) }}" class="ml-auto" onsubmit="return confirm('Delete this appointment?')">
                    @csrf <input type="hidden" name="status" value="delete">
                    <button type="submit" class="p-1.5 text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 rounded transition" title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    <div class="mt-6">{{ $appointments->links() }}</div>
    @else
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 text-center py-20">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">No Appointments</h3>
        <p class="text-gray-400 text-sm">No appointments yet. Click "Make Appointment" to create one, or they'll appear here when visitors submit them.</p>
    </div>
    @endif
</div>
@endsection
