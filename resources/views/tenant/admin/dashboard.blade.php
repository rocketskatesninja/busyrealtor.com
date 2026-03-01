@extends('layouts.admin')
@section('title', 'Dashboard')

@section('styles')
/* Stat card icon backgrounds in dark mode — per-color overrides */
.dark .stat-icon.bg-blue-50    { background-color: rgba(29,78,216,0.2);  }  .dark .stat-icon.bg-blue-50    svg { color: #93c5fd; }
.dark .stat-icon.bg-green-50   { background-color: rgba(22,101,52,0.2);  }  .dark .stat-icon.bg-green-50   svg { color: #86efac; }
.dark .stat-icon.bg-yellow-50  { background-color: rgba(133,77,14,0.2);  }  .dark .stat-icon.bg-yellow-50  svg { color: #fde68a; }
.dark .stat-icon.bg-purple-50  { background-color: rgba(88,28,135,0.2);  }  .dark .stat-icon.bg-purple-50  svg { color: #d8b4fe; }
.dark .stat-icon.bg-indigo-50  { background-color: rgba(49,46,129,0.2);  }  .dark .stat-icon.bg-indigo-50  svg { color: #a5b4fc; }
.dark .stat-icon.bg-emerald-50 { background-color: rgba(6,78,59,0.2);   }  .dark .stat-icon.bg-emerald-50 svg { color: #6ee7b7; }
.dark .stat-icon.bg-orange-50  { background-color: rgba(154,52,18,0.2);  }  .dark .stat-icon.bg-orange-50  svg { color: #fdba74; }
.dark .stat-icon.bg-rose-50    { background-color: rgba(136,19,55,0.2);  }  .dark .stat-icon.bg-rose-50    svg { color: #fda4af; }
.dark .stat-icon.bg-teal-50    { background-color: rgba(17,94,89,0.2);   }  .dark .stat-icon.bg-teal-50    svg { color: #5eead4; }
.dark .stat-icon.bg-cyan-50    { background-color: rgba(21,94,117,0.2);  }  .dark .stat-icon.bg-cyan-50    svg { color: #67e8f9; }
.dark .stat-icon.bg-slate-50   { background-color: rgba(30,41,59,0.4);   }  .dark .stat-icon.bg-slate-50   svg { color: #94a3b8; }
.dark .stat-icon.bg-amber-50   { background-color: rgba(120,53,15,0.2);  }  .dark .stat-icon.bg-amber-50   svg { color: #fcd34d; }
@endsection

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.x/dist/chart.umd.min.js"></script>
@endsection

@section('content')
@php $account = $tenant->slug; @endphp
<div class="max-w-7xl mx-auto px-4">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-500 text-sm mt-1">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}</p>
        </div>

    </div>

    {{-- Action Items Alert --}}
    @php
        $actionItems = [];
        if ($stats['unread_messages'] > 0) $actionItems[] = $stats['unread_messages'] . ' unread ' . Str::plural('message', $stats['unread_messages']);
        if ($stats['appointments'] > 0)    $actionItems[] = $stats['appointments'] . ' pending ' . Str::plural('appointment', $stats['appointments']);
        if ($needsAttention->count() > 0)  $actionItems[] = $needsAttention->count() . ' ' . Str::plural('listing', $needsAttention->count()) . ' need attention';
    @endphp
    @if(count($actionItems) > 0)
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/50 rounded-2xl p-4 mb-6 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-500 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        <div>
            <p class="font-semibold text-amber-800 dark:text-amber-300 text-sm">Action Items</p>
            <p class="text-amber-700 dark:text-amber-400 text-sm mt-0.5">{{ implode(' · ', $actionItems) }}</p>
        </div>
    </div>
    @endif

    {{-- Stat Cards --}}
    @php
    $cards = array_filter([
        $show('active_listings')  ? ['label'=>'Active Listings',  'value'=>number_format($stats['active_listings']),         'icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'color'=>'blue']   : null,
        $show('portfolio_value')  ? ['label'=>'Portfolio Value',  'value'=>'$'.number_format($stats['portfolio_value']),      'icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'green']  : null,
        $show('unread_messages')  ? ['label'=>'Unread Messages',  'value'=>number_format($stats['unread_messages']),          'icon'=>'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'color'=>'yellow'] : null,
        $show('pending_appts')    ? ['label'=>'Pending Appts',    'value'=>number_format($stats['appointments']),             'icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'color'=>'purple']  : null,
        $show('total_properties') ? ['label'=>'Total Properties', 'value'=>number_format($stats['total_properties']),         'icon'=>'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', 'color'=>'indigo']  : null,
        $show('sold_properties')  ? ['label'=>'Sold Properties',  'value'=>number_format($stats['sold_properties']),          'icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'emerald']                                        : null,
        $show('new_this_week')    ? ['label'=>'New This Week',    'value'=>number_format($stats['new_this_week']),             'icon'=>'M13 10V3L4 14h7v7l9-11h-7z', 'color'=>'orange']                                                          : null,
        $show('avg_price')        ? ['label'=>'Avg List Price',   'value'=>'$'.number_format($stats['avg_price']),            'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'color'=>'rose'] : null,
        $show('total_revenue')    ? ['label'=>'Total Revenue',    'value'=>'$'.number_format($stats['total_revenue']),        'icon'=>'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'color'=>'teal'] : null,
        $show('response_rate')    ? ['label'=>'Response Rate',    'value'=>$stats['response_rate'].'%',                       'icon'=>'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', 'color'=>'cyan'] : null,
        $show('days_on_market')   ? ['label'=>'Avg Days Listed',  'value'=>$stats['days_on_market'].' days',                  'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'slate']                                          : null,
        $show('pending_listings') ? ['label'=>'Pending Listings', 'value'=>number_format($stats['pending_listings']),         'icon'=>'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'amber']                                     : null,
    ]);
    @endphp
    @if(count($cards) > 0)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @foreach($cards as $card)
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="stat-icon w-10 h-10 rounded-xl flex items-center justify-center mb-3 bg-{{ $card['color'] }}-50">
                <svg class="w-5 h-5 text-{{ $card['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/></svg>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $card['value'] }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $card['label'] }}</p>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Charts --}}
    @php $anyChart = $show('type_chart') || $show('status_chart') || $show('views_chart'); @endphp
    @if($anyChart)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        @if($show('type_chart'))
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="font-semibold text-gray-800 mb-4">Properties by Type</h3>
            @if($propertiesByType->isEmpty())
                <p class="text-gray-400 text-sm text-center py-16">No data yet.</p>
            @else
                <canvas id="typeChart" height="200"></canvas>
            @endif
        </div>
        @endif
        @if($show('status_chart'))
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="font-semibold text-gray-800 mb-4">Listing Status</h3>
            @if($propertiesByStatus->isEmpty())
                <p class="text-gray-400 text-sm text-center py-16">No data yet.</p>
            @else
                <canvas id="statusChart" height="200"></canvas>
            @endif
        </div>
        @endif
        @if($show('views_chart'))
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="font-semibold text-gray-800 mb-4">Views by Property</h3>
            @if($viewsByProperty->isEmpty())
                <p class="text-gray-400 text-sm text-center py-16">No views tracked yet.</p>
            @else
                <canvas id="viewsChart" height="200"></canvas>
            @endif
        </div>
        @endif
    </div>
    @endif

    {{-- Tables --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        @if($show('top_properties'))
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between p-5 border-b">
                <h3 class="font-semibold text-gray-800">Top Properties by Views</h3>
                <a href="{{ route('tenant.admin.properties.index', $account) }}" class="text-sm hover-primary" style="color:var(--primary)">View All</a>
            </div>
            <div class="divide-y">
                @forelse($topProperties as $p)
                @php $img = $p->images->first(); @endphp
                <div class="flex items-center gap-4 p-4">
                    <div class="w-12 h-12 rounded-xl bg-gray-100 overflow-hidden flex-shrink-0">
                        @if($img)<img src="{{ asset('storage/'.$img->image_path) }}" class="w-full h-full object-cover">@else<div class="w-full h-full flex items-center justify-center"><svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg></div>@endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-800 text-sm truncate">{{ $p->title }}</p>
                        <p class="text-xs text-gray-500">${{ number_format($p->price) }}</p>
                    </div>
                    <span class="text-sm text-gray-400">{{ $p->view_count }} views</span>
                </div>
                @empty
                <div class="p-8 text-center text-gray-400 text-sm">No properties yet.</div>
                @endforelse
            </div>
        </div>
        @endif

        @if($show('recent_messages'))
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between p-5 border-b">
                <h3 class="font-semibold text-gray-800">Recent Messages</h3>
                <a href="{{ route('tenant.admin.messages.index', $account) }}" class="text-sm hover-primary" style="color:var(--primary)">View All</a>
            </div>
            <div class="divide-y">
                @forelse($recentMessages as $msg)
                <div class="flex items-start gap-3 p-4 {{ !$msg->is_read ? 'bg-blue-50/50 dark:bg-blue-900/20' : '' }}">
                    <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0 text-sm font-semibold text-gray-500">
                        {{ strtoupper(substr($msg->sender_name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-medium text-gray-800 text-sm truncate">{{ $msg->sender_name }}</p>
                            @if(!$msg->is_read)<span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>@endif
                        </div>
                        <p class="text-xs text-gray-500 truncate">{{ Str::limit($msg->message, 65) }}</p>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center">
                    <svg class="w-10 h-10 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <p class="text-gray-400 text-sm">No messages yet.</p>
                    <p class="text-gray-300 text-xs mt-1">Messages from your contact form will appear here.</p>
                </div>
                @endforelse
            </div>
        </div>
        @endif

        @if($show('upcoming_appts'))
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between p-5 border-b">
                <h3 class="font-semibold text-gray-800">Upcoming Appointments</h3>
                <a href="{{ route('tenant.admin.appointments.index', $account) }}" class="text-sm hover-primary" style="color:var(--primary)">View All</a>
            </div>
            <div class="divide-y">
                @forelse($upcomingAppts as $appt)
                <div class="p-4">
                    <div class="flex items-center justify-between mb-1">
                        <p class="font-medium text-gray-800 text-sm">{{ $appt->visitor_name }}</p>
                        <span class="text-xs text-gray-500">{{ $appt->appointment_date ? \Carbon\Carbon::parse($appt->appointment_date)->format('M j') : 'TBD' }}</span>
                    </div>
                    <p class="text-xs text-gray-500">{{ $appt->visitor_email }} · {{ ucfirst($appt->appointment_type ?? 'showing') }}</p>
                </div>
                @empty
                <div class="p-8 text-center">
                    <svg class="w-10 h-10 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p class="text-gray-400 text-sm">No upcoming appointments.</p>
                    <p class="text-gray-300 text-xs mt-1">Appointment requests will appear here.</p>
                </div>
                @endforelse
            </div>
        </div>
        @endif

        @if($show('recent_properties'))
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between p-5 border-b">
                <h3 class="font-semibold text-gray-800">Recently Added</h3>
                <a href="{{ route('tenant.admin.properties.create', $account) }}" class="text-sm hover-primary" style="color:var(--primary)">+ Add New</a>
            </div>
            <div class="divide-y">
                @forelse($recentProperties as $p)
                @php $img = $p->images->first(); @endphp
                <a href="{{ route('tenant.admin.properties.edit', [$account, $p->id]) }}" class="flex items-center gap-3 p-4 hover:bg-gray-50 transition">
                    <div class="w-12 h-12 rounded-xl bg-gray-100 overflow-hidden flex-shrink-0">
                        @if($img)<img src="{{ asset('storage/'.$img->image_path) }}" class="w-full h-full object-cover">@else<div class="w-full h-full flex items-center justify-center"><svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg></div>@endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-800 text-sm truncate">{{ $p->title }}</p>
                        <p class="text-xs text-gray-500">${{ number_format($p->price) }} · {{ $p->created_at->diffForHumans() }}</p>
                    </div>
                    @php $sc = ['active'=>'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400','pending'=>'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400','sold'=>'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300']; @endphp
                    <span class="text-xs px-2 py-1 rounded-full {{ $sc[$p->listing_status] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($p->listing_status) }}</span>
                </a>
                @empty
                <div class="p-8 text-center text-gray-400 text-sm">No properties yet.</div>
                @endforelse
            </div>
        </div>
        @endif

        @if($show('needs_attention') && $needsAttention->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-amber-200 dark:border-amber-700/50 lg:col-span-2">
            <div class="flex items-center justify-between p-5 border-b border-amber-100 dark:border-amber-700/30">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <h3 class="font-semibold text-gray-800">Needs Attention</h3>
                </div>
                <span class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded-full">Active listings with low visibility</span>
            </div>
            <div class="divide-y">
                @foreach($needsAttention as $p)
                @php $img = $p->images->first(); @endphp
                <a href="{{ route('tenant.admin.properties.edit', [$account, $p->id]) }}" class="flex items-center gap-4 p-4 hover:bg-amber-50/50 dark:hover:bg-amber-900/10 transition">
                    <div class="w-12 h-12 rounded-xl bg-gray-100 overflow-hidden flex-shrink-0">
                        @if($img)<img src="{{ asset('storage/'.$img->image_path) }}" class="w-full h-full object-cover">@else<div class="w-full h-full flex items-center justify-center"><svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg></div>@endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-800 text-sm truncate">{{ $p->title }}</p>
                        <p class="text-xs text-gray-500">${{ number_format($p->price) }} · Listed {{ $p->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="text-xs text-amber-600 dark:text-amber-400">{{ $p->view_count }} views</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
@endsection

@section('scripts')
@php
$typeLabels  = $propertiesByType->keys()->map(fn($k) => ucfirst(str_replace('-', ' ', $k)))->values()->toArray();
$typeData    = $propertiesByType->values()->toArray();
$statusLabels = $propertiesByStatus->keys()->map(fn($k) => ucfirst($k))->values()->toArray();
$statusData   = $propertiesByStatus->values()->toArray();
$viewLabels   = $viewsByProperty->map(fn($p) => Str::limit($p->title ?: $p->address_street, 20))->toArray();
$viewData     = $viewsByProperty->pluck('view_count')->toArray();
@endphp
const isDark = document.documentElement.classList.contains('dark');
const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#3b82f6';
const chartLegendColor = isDark ? '#94a3b8' : '#6b7280';
@if($show('type_chart') && !$propertiesByType->isEmpty())
new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: { labels: {!! json_encode($typeLabels) !!}, datasets: [{ data: {!! json_encode($typeData) !!}, backgroundColor: ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4'] }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 }, color: chartLegendColor } } } }
});
@endif
@if($show('status_chart') && !$propertiesByStatus->isEmpty())
new Chart(document.getElementById('statusChart'), {
    type: 'pie',
    data: { labels: {!! json_encode($statusLabels) !!}, datasets: [{ data: {!! json_encode($statusData) !!}, backgroundColor: ['#10b981','#f59e0b','#6b7280','#3b82f6','#ef4444'] }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 }, color: chartLegendColor } } } }
});
@endif
@if($show('views_chart') && !$viewsByProperty->isEmpty())
new Chart(document.getElementById('viewsChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($viewLabels) !!},
        datasets: [{ label: 'Views', data: {!! json_encode($viewData) !!}, backgroundColor: primaryColor, borderRadius: 6 }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: isDark ? '#334155' : '#f3f4f6' }, ticks: { precision: 0, color: isDark ? '#94a3b8' : '#6b7280' } },
            x: { grid: { display: false }, ticks: { font: { size: 10 }, color: isDark ? '#94a3b8' : '#6b7280' } }
        }
    }
});
@endif
@endsection
