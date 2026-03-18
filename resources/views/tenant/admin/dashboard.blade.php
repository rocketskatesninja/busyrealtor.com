@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-subtitle', 'Welcome back, ' . auth()->user()->first_name)

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
.dark .stat-icon.bg-violet-50  { background-color: rgba(76,29,149,0.2);  }  .dark .stat-icon.bg-violet-50  svg { color: #c4b5fd; }
.drag-mode [data-widget] { cursor: grab; }
.drag-mode [data-widget]:active { cursor: grabbing; }
.drag-mode #stat-cards-container,
.drag-mode #charts-container,
.drag-mode #tables-container { outline: 2px dashed var(--primary); outline-offset: 4px; border-radius: 1rem; }
@endsection

@section('head')
<script src="{{ asset('js/chart.min.js') }}"></script>
<script src="{{ asset('js/Sortable.min.js') }}"></script>
@endsection

@section('content')
@php $account = $tenant->slug; @endphp
<div class="max-w-7xl mx-auto px-4">

    {{-- Arrange / Lock button (desktop only) --}}
    <div class="hidden md:flex justify-end mb-6">
        <button id="dash-lock-btn" type="button"
                class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold px-4 py-2.5 rounded-xl text-sm transition flex items-center gap-2">
            <svg id="dash-lock-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <svg id="dash-unlock-icon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
            <span id="dash-lock-label">Arrange Cards</span>
        </button>
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
    $cardDefs = [
        'active_listings'  => ['key'=>'active_listings',  'label'=>'Active Listings',  'value'=>number_format($stats['active_listings']),         'icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'color'=>'blue'],
        'portfolio_value'  => ['key'=>'portfolio_value',  'label'=>'Portfolio Value',  'value'=>'$'.number_format($stats['portfolio_value']),      'icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'green'],
        'unread_messages'  => ['key'=>'unread_messages',  'label'=>'Unread Messages',  'value'=>number_format($stats['unread_messages']),          'icon'=>'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'color'=>'yellow'],
        'pending_appts'    => ['key'=>'pending_appts',    'label'=>'Pending Appts',    'value'=>number_format($stats['appointments']),             'icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'color'=>'purple'],
        'views_month'      => ['key'=>'views_month',      'label'=>'Views (30 Days)',  'value'=>number_format($stats['views_month']),              'icon'=>'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z', 'color'=>'violet'],
        'total_properties' => ['key'=>'total_properties', 'label'=>'Total Properties', 'value'=>number_format($stats['total_properties']),         'icon'=>'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', 'color'=>'indigo'],
        'sold_properties'  => ['key'=>'sold_properties',  'label'=>'Sold Properties',  'value'=>number_format($stats['sold_properties']),          'icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'emerald'],
        'new_this_week'    => ['key'=>'new_this_week',    'label'=>'New This Week',    'value'=>number_format($stats['new_this_week']),             'icon'=>'M13 10V3L4 14h7v7l9-11h-7z', 'color'=>'orange'],
        'avg_price'        => ['key'=>'avg_price',        'label'=>'Avg List Price',   'value'=>'$'.number_format($stats['avg_price']),            'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'color'=>'rose'],
        'total_revenue'    => ['key'=>'total_revenue',    'label'=>'Total Revenue',    'value'=>'$'.number_format($stats['total_revenue']),        'icon'=>'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'color'=>'teal'],
        'response_rate'    => ['key'=>'response_rate',    'label'=>'Response Rate',    'value'=>$stats['response_rate'].'%',                       'icon'=>'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', 'color'=>'cyan'],
        'days_on_market'   => ['key'=>'days_on_market',   'label'=>'Avg Days Listed',  'value'=>$stats['days_on_market'].' days',                  'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'slate'],
        'pending_listings' => ['key'=>'pending_listings', 'label'=>'Pending Listings', 'value'=>number_format($stats['pending_listings']),         'icon'=>'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'amber'],
    ];
    $cardDefs = array_filter($cardDefs, fn($k) => $show($k), ARRAY_FILTER_USE_KEY);
    $statOrder = $dashConfig['_stat_order'] ?? array_keys($cardDefs);
    uksort($cardDefs, fn($a, $b) => (($pa = array_search($a, $statOrder)) !== false ? $pa : 99) - (($pb = array_search($b, $statOrder)) !== false ? $pb : 99));
    $cards = array_values($cardDefs);
    @endphp
    @if(count($cards) > 0)
    <div id="stat-cards-container" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @foreach($cards as $card)
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-200" data-widget="{{ $card['key'] }}">
            <div class="stat-icon w-10 h-10 rounded-xl flex items-center justify-center mb-3 bg-{{ $card['color'] }}-50">
                <svg class="w-5 h-5 text-{{ $card['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/></svg>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $card['value'] }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $card['label'] }}</p>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Charts —— unified sortable container --}}
    @if(count($chartOrder) > 0)
    <div id="charts-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6" style="grid-auto-rows: 1fr;">
        @foreach($chartOrder as $chartKey)
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 flex flex-col" data-widget="{{ $chartKey }}">
            @switch($chartKey)
                @case('type_chart')
                    <h3 class="font-semibold text-gray-800 mb-4">Properties by Type</h3>
                    @if($propertiesByType->isEmpty())
                        <p class="text-gray-400 text-sm text-center py-16">No data yet.</p>
                    @else
                        <canvas id="typeChart" height="200"></canvas>
                    @endif
                @break
                @case('status_chart')
                    <h3 class="font-semibold text-gray-800 mb-4">Listing Status</h3>
                    @if($propertiesByStatus->isEmpty())
                        <p class="text-gray-400 text-sm text-center py-16">No data yet.</p>
                    @else
                        <canvas id="statusChart" height="200"></canvas>
                    @endif
                @break
                @case('views_chart')
                    <h3 class="font-semibold text-gray-800 mb-4">Views by Property</h3>
                    @if($viewsByProperty->isEmpty())
                        <p class="text-gray-400 text-sm text-center py-16 flex-1 flex items-center justify-center">No views tracked yet.</p>
                    @else
                        <div class="flex-1 relative min-h-[140px]">
                            <canvas id="viewsChart" class="w-full h-full"></canvas>
                        </div>
                    @endif
                @break
                @case('views_30days')
                    <h3 class="font-semibold text-gray-800 mb-4">Property Views — Last 30 Days</h3>
                    <div class="flex-1 relative min-h-[140px]"><canvas id="views30Chart" class="w-full h-full"></canvas></div>
                @break
                @case('messages_7days')
                    <h3 class="font-semibold text-gray-800 mb-4">Messages — Last 7 Days</h3>
                    <div class="flex-1 relative min-h-[140px]"><canvas id="msgs7Chart" class="w-full h-full"></canvas></div>
                @break
                @case('price_distribution')
                    <h3 class="font-semibold text-gray-800 mb-4">Price Range Distribution</h3>
                    @if($priceDistribution->sum() === 0)
                        <p class="text-gray-400 text-sm text-center py-16 flex-1 flex items-center justify-center">No data yet.</p>
                    @else
                        <div class="flex-1 relative min-h-[140px]"><canvas id="priceChart" class="w-full h-full"></canvas></div>
                    @endif
                @break
                @case('listings_over_time')
                    <h3 class="font-semibold text-gray-800 mb-4">Listings Added (12 Months)</h3>
                    <div class="flex-1 relative min-h-[140px]"><canvas id="listingsTimeChart" class="w-full h-full"></canvas></div>
                @break
                @case('revenue_trend')
                    <h3 class="font-semibold text-gray-800 mb-4">Revenue Trend (12 Months)</h3>
                    <div class="flex-1 relative min-h-[140px]"><canvas id="revenueChart" class="w-full h-full"></canvas></div>
                @break
                @case('appt_status')
                    <h3 class="font-semibold text-gray-800 mb-4">Appointment Status</h3>
                    @if($apptByStatus->isEmpty())
                        <p class="text-gray-400 text-sm text-center py-16">No appointments yet.</p>
                    @else
                        <canvas id="apptStatusChart" height="200"></canvas>
                    @endif
                @break
                @case('message_sources')
                    <h3 class="font-semibold text-gray-800 mb-4">Message Sources</h3>
                    @if($messageSources->isEmpty())
                        <p class="text-gray-400 text-sm text-center py-16">No messages yet.</p>
                    @else
                        <canvas id="msgSourcesChart" height="200"></canvas>
                    @endif
                @break
            @endswitch
        </div>
        @endforeach
    </div>
    @endif

    {{-- Tables —— unified sortable container --}}
    <div id="tables-container" class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        @foreach($tableOrder as $tableKey)
        @switch($tableKey)

            @case('top_properties')
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200" data-widget="top_properties">
                <div class="flex items-center justify-between p-5 dash-header-border">
                    <h3 class="font-semibold text-gray-800">Top Properties by Views</h3>
                    <a href="{{ route('tenant.admin.properties.index', $account) }}" class="text-sm hover-primary" style="color:var(--primary)">View All</a>
                </div>
                <div class="dash-divide">
                    @forelse($topProperties as $p)
                    @php $img = $p->images->first(); @endphp
                    <div class="flex items-center gap-4 p-4">
                        <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-slate-600 overflow-hidden flex-shrink-0">
                            @if($img)<img src="{{ asset('storage/'.$img->image_path) }}" class="w-full h-full object-cover">@else<div class="w-full h-full flex items-center justify-center"><svg class="w-5 h-5 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg></div>@endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800 text-sm truncate">{{ $p->title }}</p>
                            <p class="text-xs text-gray-500">${{ number_format($p->price) }}</p>
                        </div>
                        <span class="text-sm text-gray-400 dark:text-gray-500">{{ $p->view_count }} views</span>
                    </div>
                    @empty
                    <div class="p-8 text-center text-gray-400 text-sm">No properties yet.</div>
                    @endforelse
                </div>
            </div>
            @break

            @case('recent_messages')
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200" data-widget="recent_messages">
                <div class="flex items-center justify-between p-5 dash-header-border">
                    <h3 class="font-semibold text-gray-800">Recent Messages</h3>
                    <a href="{{ route('tenant.admin.messages.index', $account) }}" class="text-sm hover-primary" style="color:var(--primary)">View All</a>
                </div>
                <div class="dash-divide">
                    @forelse($recentMessages as $msg)
                    <div class="flex items-start gap-3 p-4 {{ !$msg->is_read ? 'bg-blue-50/50 dark:bg-blue-900/20' : '' }}">
                        <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-slate-600 flex items-center justify-center flex-shrink-0 text-sm font-semibold text-gray-600 dark:text-gray-300">
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
                        <svg class="w-10 h-10 text-gray-200 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <p class="text-gray-400 text-sm">No messages yet.</p>
                        <p class="text-gray-300 dark:text-gray-600 text-xs mt-1">Messages from your contact form will appear here.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @break

            @case('starred_messages')
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200" data-widget="starred_messages">
                <div class="flex items-center justify-between p-5 dash-header-border">
                    <h3 class="font-semibold text-gray-800">Starred Messages</h3>
                    <a href="{{ route('tenant.admin.messages.index', $account) }}" class="text-sm hover-primary" style="color:var(--primary)">View All</a>
                </div>
                <div class="dash-divide">
                    @forelse($starredMessages as $msg)
                    <div class="flex items-start gap-3 p-4">
                        <div class="w-9 h-9 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center flex-shrink-0 text-sm font-semibold text-yellow-700 dark:text-yellow-400">
                            {{ strtoupper(substr($msg->sender_name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800 text-sm truncate">{{ $msg->sender_name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ Str::limit($msg->message, 65) }}</p>
                        </div>
                        <svg class="w-4 h-4 text-yellow-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    @empty
                    <div class="p-8 text-center">
                        <svg class="w-10 h-10 text-gray-200 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        <p class="text-gray-400 text-sm">No starred messages.</p>
                        <p class="text-gray-300 dark:text-gray-600 text-xs mt-1">Star important messages to pin them here.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @break

            @case('upcoming_appts')
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200" data-widget="upcoming_appts">
                <div class="flex items-center justify-between p-5 dash-header-border">
                    <h3 class="font-semibold text-gray-800">Upcoming Appointments</h3>
                    <a href="{{ route('tenant.admin.appointments.index', $account) }}" class="text-sm hover-primary" style="color:var(--primary)">View All</a>
                </div>
                <div class="dash-divide">
                    @forelse($upcomingAppts as $appt)
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-1">
                            <p class="font-medium text-gray-800 dark:text-gray-200 text-sm">{{ $appt->visitor_name }}</p>
                            <span class="text-xs text-gray-500">{{ $appt->appointment_date ? \Carbon\Carbon::parse($appt->appointment_date)->format('M j') : 'TBD' }}</span>
                        </div>
                        <p class="text-xs text-gray-500">{{ $appt->visitor_email }} · {{ ucfirst($appt->appointment_type ?? 'showing') }}</p>
                    </div>
                    @empty
                    <div class="p-8 text-center">
                        <svg class="w-10 h-10 text-gray-200 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-gray-400 text-sm">No upcoming appointments.</p>
                        <p class="text-gray-300 dark:text-gray-600 text-xs mt-1">Appointment requests will appear here.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @break

            @case('recent_properties')
            @php $sc = ['active'=>'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400','pending'=>'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400','sold'=>'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300']; @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200" data-widget="recent_properties">
                <div class="flex items-center justify-between p-5 dash-header-border">
                    <h3 class="font-semibold text-gray-800">Recently Added</h3>
                    <a href="{{ route('tenant.admin.properties.create', $account) }}" class="text-sm hover-primary" style="color:var(--primary)">+ Add New</a>
                </div>
                <div class="dash-divide">
                    @forelse($recentProperties as $p)
                    @php $img = $p->images->first(); @endphp
                    <a href="{{ route('tenant.admin.properties.edit', [$account, $p->id]) }}" class="flex items-center gap-3 p-4 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition">
                        <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-slate-600 overflow-hidden flex-shrink-0">
                            @if($img)<img src="{{ asset('storage/'.$img->image_path) }}" class="w-full h-full object-cover">@else<div class="w-full h-full flex items-center justify-center"><svg class="w-5 h-5 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg></div>@endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800 text-sm truncate">{{ $p->title }}</p>
                            <p class="text-xs text-gray-500">${{ number_format($p->price) }} · {{ $p->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full {{ $sc[$p->listing_status] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($p->listing_status) }}</span>
                    </a>
                    @empty
                    <div class="p-8 text-center text-gray-400 text-sm">No properties yet.</div>
                    @endforelse
                </div>
            </div>
            @break

            @case('needs_attention')
            <div class="bg-white rounded-2xl shadow-sm border border-amber-200 dark:border-amber-700/50" data-widget="needs_attention">
                <div class="flex items-center justify-between p-5 border-b border-amber-100 dark:border-amber-700/30">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        <h3 class="font-semibold text-gray-800">Needs Attention</h3>
                    </div>
                    @if($needsAttention->count() > 0)
                    <span class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded-full">{{ $needsAttention->count() }} active {{ Str::plural('listing', $needsAttention->count()) }}</span>
                    @endif
                </div>
                <div class="dash-divide">
                    @forelse($needsAttention as $p)
                    @php $img = $p->images->first(); @endphp
                    <a href="{{ route('tenant.admin.properties.edit', [$account, $p->id]) }}" class="flex items-center gap-4 p-4 hover:bg-amber-50/50 dark:hover:bg-amber-900/10 transition">
                        <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-slate-700 overflow-hidden flex-shrink-0">
                            @if($img)
                                <img src="{{ asset('storage/'.$img->image_path) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-amber-50 dark:bg-amber-900/30">
                                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800 text-sm truncate">{{ $p->title }}</p>
                            <p class="text-xs text-gray-500">${{ number_format($p->price) }} · Listed {{ $p->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1 flex-shrink-0">
                            @foreach($p->attention_reasons as $reason)
                            <span class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 rounded-full whitespace-nowrap">{{ $reason }}</span>
                            @endforeach
                        </div>
                    </a>
                    @empty
                    <div class="p-8 text-center">
                        <svg class="w-10 h-10 text-gray-200 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-gray-400 text-sm">All listings look good!</p>
                        <p class="text-gray-300 dark:text-gray-600 text-xs mt-1">Active listings with low views, missing photos, or other issues will appear here.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @break

        @endswitch
        @endforeach

    </div>
</div>
@endsection

@section('scripts')
const isDark = document.documentElement.classList.contains('dark');
const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#3b82f6';
const chartLegendColor = isDark ? '#94a3b8' : '#6b7280';
const gridColor = isDark ? '#334155' : '#f3f4f6';
const tickColor = isDark ? '#94a3b8' : '#6b7280';
const scaleDefaults = {
    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { precision: 0, color: tickColor } },
    x: { grid: { display: false }, ticks: { font: { size: 10 }, color: tickColor } }
};

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
    data: { labels: {!! json_encode($viewLabels) !!}, datasets: [{ label: 'Views', data: {!! json_encode($viewData) !!}, backgroundColor: primaryColor, borderRadius: 6 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: scaleDefaults }
});
@endif
@if($show('views_30days'))
new Chart(document.getElementById('views30Chart'), {
    type: 'line',
    data: { labels: {!! json_encode($v30Labels) !!}, datasets: [{ label: 'Views', data: {!! json_encode($v30Data) !!}, borderColor: primaryColor, backgroundColor: primaryColor + '22', fill: true, tension: 0.4, pointRadius: 2, borderWidth: 2 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: scaleDefaults }
});
@endif
@if($show('messages_7days'))
new Chart(document.getElementById('msgs7Chart'), {
    type: 'bar',
    data: { labels: {!! json_encode($msg7Labels) !!}, datasets: [{ label: 'Messages', data: {!! json_encode($msg7Data) !!}, backgroundColor: '#8b5cf6', borderRadius: 6 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: scaleDefaults }
});
@endif
@if($show('price_distribution') && $priceDistribution->sum() > 0)
new Chart(document.getElementById('priceChart'), {
    type: 'bar',
    data: { labels: {!! json_encode($priceLabels) !!}, datasets: [{ label: 'Properties', data: {!! json_encode($priceData) !!}, backgroundColor: '#10b981', borderRadius: 6 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: scaleDefaults }
});
@endif
@if($show('listings_over_time'))
new Chart(document.getElementById('listingsTimeChart'), {
    type: 'line',
    data: { labels: {!! json_encode($ltLabels) !!}, datasets: [{ label: 'Listings Added', data: {!! json_encode($ltData) !!}, borderColor: '#f59e0b', backgroundColor: '#f59e0b22', fill: true, tension: 0.4, pointRadius: 2, borderWidth: 2 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: scaleDefaults }
});
@endif
@if($show('revenue_trend'))
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: { labels: {!! json_encode($revLabels) !!}, datasets: [{ label: 'Revenue', data: {!! json_encode($revData) !!}, backgroundColor: '#10b981', borderRadius: 6 }] },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor, callback: function(v) { return '$' + (v >= 1000000 ? (v/1000000).toFixed(1)+'M' : v >= 1000 ? (v/1000).toFixed(0)+'K' : v); } } },
            x: { grid: { display: false }, ticks: { font: { size: 10 }, color: tickColor } }
        }
    }
});
@endif
@if($show('appt_status') && !$apptByStatus->isEmpty())
new Chart(document.getElementById('apptStatusChart'), {
    type: 'doughnut',
    data: { labels: {!! json_encode($apptLabels) !!}, datasets: [{ data: {!! json_encode($apptData) !!}, backgroundColor: ['#8b5cf6','#10b981','#ef4444','#f59e0b','#6b7280'] }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 }, color: chartLegendColor } } } }
});
@endif
@if($show('message_sources') && !$messageSources->isEmpty())
new Chart(document.getElementById('msgSourcesChart'), {
    type: 'doughnut',
    data: { labels: {!! json_encode($srcLabels) !!}, datasets: [{ data: {!! json_encode($srcData) !!}, backgroundColor: ['#3b82f6','#f59e0b','#10b981','#ef4444','#8b5cf6'] }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 }, color: chartLegendColor } } } }
});
@endif

// ── Sortable Dashboard ──────────────────────────────────────────────────────
const LOCK_KEY = 'dashboard_locked';
const saveUrl  = '{{ route("tenant.admin.dashboard.order", $account) }}';
const csrfToken = '{{ csrf_token() }}';

let statSortable, chartSortable, tableSortable;

function getLocked() { return localStorage.getItem(LOCK_KEY) !== 'false'; }
function setLocked(v) { localStorage.setItem(LOCK_KEY, v ? 'true' : 'false'); }

function applyLockState(locked) {
    document.getElementById('dash-lock-icon').classList.toggle('hidden', !locked);
    document.getElementById('dash-unlock-icon').classList.toggle('hidden', locked);
    document.getElementById('dash-lock-label').textContent = locked ? 'Arrange Cards' : 'Lock Layout';
    document.body.classList.toggle('drag-mode', !locked);

    const opts = (containerId, sectionKey) => ({
        animation: 150,
        ghostClass: 'opacity-50',
        dragClass: 'shadow-2xl',
        disabled: locked,
        scroll: false,
        onEnd() {
            const order = Array.from(
                document.getElementById(containerId).querySelectorAll('[data-widget]')
            ).map(el => el.dataset.widget);
            fetch(saveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ section: sectionKey, order }),
            });
        },
    });

    if (statSortable)  statSortable.destroy();
    if (chartSortable) chartSortable.destroy();
    if (tableSortable) tableSortable.destroy();

    const statEl  = document.getElementById('stat-cards-container');
    const chartEl = document.getElementById('charts-container');
    const tableEl = document.getElementById('tables-container');

    if (statEl)  statSortable  = new Sortable(statEl,  opts('stat-cards-container', 'stat_cards'));
    if (chartEl) chartSortable = new Sortable(chartEl, opts('charts-container',      'charts'));
    if (tableEl) tableSortable = new Sortable(tableEl, opts('tables-container',      'tables'));
}

const lockBtn = document.getElementById('dash-lock-btn');
if (lockBtn) {
    lockBtn.addEventListener('click', () => {
        const newLocked = !getLocked();
        setLocked(newLocked);
        applyLockState(newLocked);
    });
}

const isMobile = window.innerWidth < 768;
applyLockState(isMobile ? true : getLocked());

window.addEventListener('resize', () => {
    if (window.innerWidth < 768 && !getLocked()) applyLockState(true);
});
@endsection
