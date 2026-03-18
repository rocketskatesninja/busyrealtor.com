@extends('layouts.super-admin')
@section('title', 'Activity Log')
@section('page-title', 'Activity Log')
@section('page-description', 'Track all actions across the platform')

@section('content')
@php
    $badgeColors = [
        'created'  => 'bg-green-100 text-green-700',
        'updated'  => 'bg-blue-100 text-blue-700',
        'deleted'  => 'bg-red-100 text-red-700',
        'login'    => 'bg-purple-100 text-purple-700',
        'logout'   => 'bg-gray-100 text-gray-600',
        'impersonate' => 'bg-yellow-100 text-yellow-700',
        'stop_impersonate' => 'bg-yellow-100 text-yellow-700',
    ];
@endphp
<div class="space-y-6">
    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <form method="GET" action="{{ route('super.activity') }}" class="flex flex-col sm:flex-row sm:flex-wrap gap-3 sm:items-end">
            <div class="flex-1 sm:min-w-48">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search descriptions or tenant..." class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <select name="action" class="flex-1 sm:flex-none border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none">
                <option value="">All Actions</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
<input type="date" name="date_from" value="{{ request('date_from') }}" class="flex-1 sm:flex-none border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="flex-1 sm:flex-none border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <div class="flex items-center gap-3">
                <button type="submit" class="flex-1 sm:flex-none bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-700 transition">Filter</button>
                @if(request()->hasAny(['search','action','date_from','date_to']))
                <a href="{{ route('super.activity') }}" class="text-sm text-gray-500 py-2.5 whitespace-nowrap">Clear</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Results count + clear --}}
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-400">{{ $logs->total() }} {{ Str::plural('entry', $logs->total()) }}</p>
        @if($logs->total() > 0)
        <form method="POST" action="{{ route('super.activity.clear') }}" onsubmit="return confirm('Clear all activity log entries? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-red-400 hover:text-red-300 transition-colors">Clear all</button>
        </form>
        @endif
    </div>

    {{-- Mobile: card list --}}
    <div class="md:hidden space-y-3">
        @forelse($logs as $log)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="min-w-0">
                    <p class="font-medium text-gray-800 text-sm truncate">{{ $log->description }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                </div>
                @php $badge = $badgeColors[$log->action] ?? 'bg-gray-100 text-gray-600'; @endphp
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $badge }} flex-shrink-0">{{ $log->action }}</span>
            </div>
            <div class="flex items-center gap-3 text-xs text-gray-500">
                <span>{{ $log->user ? $log->user->first_name . ' ' . $log->user->last_name : 'System' }}</span>
                <span>&middot;</span>
                <span>{{ $log->tenant?->name ?? 'Platform' }}</span>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <p class="text-gray-400 text-sm">No activity logged yet.</p>
            <p class="text-gray-400 text-xs mt-1">Actions across the platform will appear here.</p>
        </div>
        @endforelse
    </div>

    {{-- Desktop: table --}}
    <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($logs->isEmpty())
            <div class="p-12 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="text-gray-400 text-sm">No activity logged yet.</p>
                <p class="text-gray-400 text-xs mt-1">Actions across the platform will appear here.</p>
            </div>
        @else
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tenant</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3.5 whitespace-nowrap text-gray-500 text-xs" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                            {{ $log->created_at->diffForHumans() }}
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            @if($log->user)
                                <span class="text-gray-800 font-medium">{{ $log->user->first_name }} {{ $log->user->last_name }}</span>
                            @else
                                <span class="text-gray-400 italic">System</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            @if($log->tenant)
                                <span class="text-gray-700">{{ $log->tenant->name }}</span>
                            @else
                                <span class="text-blue-600 text-xs font-semibold">Platform</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            @php
                                $badgeColors = [
                                    'created'  => 'bg-green-100 text-green-700',
                                    'updated'  => 'bg-blue-100 text-blue-700',
                                    'deleted'  => 'bg-red-100 text-red-700',
                                    'login'    => 'bg-purple-100 text-purple-700',
                                    'logout'   => 'bg-gray-100 text-gray-600',
                                    'impersonate' => 'bg-yellow-100 text-yellow-700',
                                    'stop_impersonate' => 'bg-yellow-100 text-yellow-700',
                                ];
                                $badge = $badgeColors[$log->action] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badge }}">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-600 max-w-md truncate" title="{{ $log->description }}">
                            {{ Str::limit($log->description, 80) }}
                        </td>
                        <td class="px-5 py-3.5 whitespace-nowrap text-gray-400 text-xs font-mono">
                            {{ $log->ip_address }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
    <div>
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
