@extends('layouts.admin')
@section('title', 'Appointments')
@section('page-subtitle', 'Scheduling requests from clients')
@section('content')
@php $account = $tenant->slug; @endphp
<div class="max-w-7xl mx-auto px-4">

    {{-- Filters --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 mb-6">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <select name="status" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none">
                <option value="">All Statuses</option>
                @foreach(['pending'=>'Pending','confirmed'=>'Confirmed','completed'=>'Completed','cancelled'=>'Cancelled'] as $v=>$l)
                    <option value="{{ $v }}" {{ request('status')===$v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none">
            <button type="submit" class="btn-primary px-5 py-2.5 rounded-lg font-semibold text-sm hover:opacity-90 transition">Filter</button>
            @if(request()->hasAny(['status','date_from','date_to'])) <a href="{{ route('tenant.admin.appointments.index', $account) }}" class="text-sm text-gray-500 py-2.5">Clear</a> @endif
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
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <p class="font-bold text-gray-800">{{ $appt->visitor_name }}</p>
                    <p class="text-sm text-gray-500">{{ $appt->visitor_email }}</p>
                    @if($appt->visitor_phone) <p class="text-sm text-gray-500">{{ $appt->visitor_phone }}</p> @endif
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ match($appt->status) { 'confirmed' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400', 'pending' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400', 'completed' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400', default => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' } }}">
                    {{ ucfirst($appt->status) }}
                </span>
            </div>
            @if($appt->appointment_date)
            <div class="flex items-center gap-2 mb-3 text-sm text-gray-600">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ \Carbon\Carbon::parse($appt->appointment_date)->format('M j, Y') }}
                <span class="text-gray-400">·</span>
                <span>{{ ucwords(str_replace('_', ' ', $appt->appointment_type ?? 'showing')) }}</span>
            </div>
            @endif
            @if($appt->property)
            <div class="text-xs text-gray-500 mb-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                {{ Str::limit($appt->property->title, 40) }}<br>{{ $appt->property->address_street }}, {{ $appt->property->address_city }}
            </div>
            @endif
            <div class="flex items-center gap-2 mb-3">
                @if($appt->source === 'chatbot')
                <span class="text-xs px-2 py-0.5 rounded-full bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400">via chatbot</span>
                @elseif($appt->source === 'public_form')
                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">via website</span>
                @endif
                @if($appt->notes)
                <p class="text-sm text-gray-600 leading-relaxed truncate" title="{{ $appt->notes }}">{{ Str::limit($appt->notes, 80) }}</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2 border-t pt-3">
                @if($appt->status === 'pending')
                <form method="POST" action="{{ route('tenant.admin.appointments.action', [$account, $appt->id]) }}">
                    @csrf <input type="hidden" name="status" value="confirmed">
                    <button type="submit" class="px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg text-xs font-semibold hover:bg-green-200 dark:hover:bg-green-900/50 transition">Confirm</button>
                </form>
                @endif
                @if(in_array($appt->status, ['pending','confirmed']))
                <form method="POST" action="{{ route('tenant.admin.appointments.action', [$account, $appt->id]) }}">
                    @csrf <input type="hidden" name="status" value="completed">
                    <button type="submit" class="px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg text-xs font-semibold hover:bg-blue-200 dark:hover:bg-blue-900/50 transition">Complete</button>
                </form>
                <form method="POST" action="{{ route('tenant.admin.appointments.action', [$account, $appt->id]) }}">
                    @csrf <input type="hidden" name="status" value="cancelled">
                    <button type="submit" class="px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg text-xs font-semibold hover:bg-red-200 dark:hover:bg-red-900/50 transition">Cancel</button>
                </form>
                @endif
                <form method="POST" action="{{ route('tenant.admin.appointments.action', [$account, $appt->id]) }}"
                      onsubmit="return confirm('Delete this appointment? This cannot be undone.')">
                    @csrf <input type="hidden" name="status" value="delete">
                    <button type="submit" class="ml-auto px-3 py-1.5 text-gray-400 dark:text-gray-500 hover:text-red-500 dark:hover:text-red-400 rounded-lg text-xs transition" title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-6">{{ $appointments->links() }}</div>
    @else
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 text-center py-20">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">No Appointments</h3>
        <p class="text-gray-400 text-sm">Appointment requests will appear here when visitors submit them.</p>
    </div>
    @endif
</div>
@endsection
