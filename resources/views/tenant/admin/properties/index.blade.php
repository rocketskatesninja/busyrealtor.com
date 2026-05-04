@extends('layouts.admin')
@section('title', 'Properties')
@section('page-subtitle', 'Manage your listings')
@section('content')
@php
    $account       = $tenant->slug;
    $propertyLimit = $tenant->propertyLimit();
    $propertyCount = $properties->total();
    $atLimit       = $propertyLimit !== null && $propertyCount >= $propertyLimit;
@endphp
<div class="max-w-7xl mx-auto px-4">

    {{-- Starter plan usage bar --}}
    @if($propertyLimit !== null)
    <div class="mb-5 bg-white border border-gray-200 rounded-xl px-4 py-3 flex items-center justify-between gap-4 shadow-sm">
        <div class="flex-1">
            <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Listing usage (Starter plan)</span>
                <span class="{{ $atLimit ? 'text-red-600 font-semibold' : 'text-gray-600' }}">{{ $propertyCount }} / {{ $propertyLimit }}</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2">
                <div class="h-2 rounded-full transition-all {{ $atLimit ? 'bg-red-500' : 'bg-blue-500' }}"
                     style="width: {{ min(100, round($propertyCount / $propertyLimit * 100)) }}%"></div>
            </div>
        </div>
        <a href="{{ route('tenant.admin.billing', $account) }}"
           class="shrink-0 text-xs font-semibold text-blue-600 hover:text-blue-700 border border-blue-200 rounded-lg px-3 py-1.5 whitespace-nowrap">
            Upgrade to Pro →
        </a>
    </div>
    @endif

    <div class="flex justify-end gap-3 mb-6">
        @if($atLimit)
        <a href="{{ route('tenant.admin.billing', $account) }}" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Upgrade to Add More
        </a>
        @else
        <a href="{{ route('tenant.admin.properties.create', $account) }}" class="btn-primary px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Property
        </a>
        @endif
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 mb-6">
        <form method="GET" class="flex flex-wrap gap-3 items-end justify-center md:justify-start">
            <div class="w-full md:flex-1 md:min-w-40">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search properties..." class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            </div>
            <select name="type" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                <option value="">All Types</option>
                @foreach(['house'=>'House','condo'=>'Condo','townhouse'=>'Townhouse','land'=>'Land','commercial'=>'Commercial'] as $v=>$l)
                    <option value="{{ $v }}" {{ request('type') === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
            <select name="status" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                <option value="">All Statuses</option>
                @foreach(['active'=>'Active','pending'=>'Pending','sold'=>'Sold','off-market'=>'Off Market','withdrawn'=>'Withdrawn'] as $v=>$l)
                    <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
            <select name="sort" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none">
                <option value="newest" {{ request('sort','newest')==='newest' ? 'selected' : '' }}>Newest</option>
                <option value="price_asc" {{ request('sort')==='price_asc' ? 'selected' : '' }}>Price ↑</option>
                <option value="price_desc" {{ request('sort')==='price_desc' ? 'selected' : '' }}>Price ↓</option>
                <option value="views" {{ request('sort')==='views' ? 'selected' : '' }}>Most Views</option>
            </select>
            <button type="submit" class="btn-primary px-5 py-2.5 rounded-lg font-semibold text-sm hover:opacity-90 transition">Filter</button>
            @if(request()->hasAny(['search','type','status','sort'])) <a href="{{ route('tenant.admin.properties.index', $account) }}" class="text-sm text-gray-500 hover:text-gray-700 py-2.5">Clear</a> @endif
        </form>
    </div>

    {{-- List --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <p class="text-sm text-gray-500">{{ $properties->total() }} {{ Str::plural('property', $properties->total()) }}</p>
        </div>
        @if($properties->count())

        {{-- Mobile cards --}}
        <div class="md:hidden divide-y divide-gray-100">
            @foreach($properties as $property)
            @php
                // Show a "Featured" badge (blue) when the property is both
                // marked is_featured AND has an active listing_status — that's
                // the only combination that actually appears on the public
                // homepage. Featured+Pending/Sold/etc. shows the lifecycle
                // status instead, since the featured flag is dormant until
                // the listing returns to active.
                $showFeatured = $property->is_featured && $property->listing_status === 'active';
                $statusClass  = $showFeatured
                    ? 'bg-blue-100 text-blue-700'
                    : match($property->listing_status) {
                        'active'     => 'bg-green-100 text-green-700',
                        'pending'    => 'bg-yellow-100 text-yellow-700',
                        'sold'       => 'bg-gray-100 text-gray-600',
                        'off-market' => 'bg-orange-100 text-orange-700',
                        'withdrawn'  => 'bg-red-100 text-red-600',
                        default      => 'bg-gray-100 text-gray-600',
                    };
                $statusLabel  = $showFeatured
                    ? 'Featured'
                    : str_replace('-', ' ', ucwords($property->listing_status, '-'));
            @endphp
            <div class="p-4">
                {{-- Top row: image + title + actions --}}
                <div class="flex items-start gap-3">
                    <div class="w-20 h-14 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0">
                        @if($property->primaryImage)
                            <img src="{{ asset('storage/'.$property->primaryImage->image_path) }}" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-800 text-sm leading-snug">{{ Str::limit($property->title, 45) }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $property->city }}{{ $property->state ? ', '.$property->state : '' }}</p>
                    </div>
                    <div class="flex gap-1 flex-shrink-0">
                        <a href="{{ route('tenant.property', [$account, $property->id]) }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100" title="View">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                        <a href="{{ route('tenant.admin.properties.edit', [$account, $property->id]) }}" class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                        <form method="POST" action="{{ route('tenant.admin.properties.destroy', [$account, $property->id]) }}" x-data onsubmit="return confirm('Delete this property?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
                {{-- Detail row --}}
                <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
                    <div class="flex items-center flex-wrap gap-2">
                        <span class="font-semibold text-gray-900 text-sm">${{ number_format($property->price) }}</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $statusClass }}">{{ $statusLabel }}</span>
                        <span class="text-xs text-gray-500 capitalize">{{ str_replace('-', ' ', $property->property_type) }}</span>
                    </div>
                    <p class="text-xs text-gray-400 whitespace-nowrap">{{ number_format($property->view_count) }} views · {{ $property->created_at->format('M j, Y') }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Desktop table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                    <tr>
                        <th class="px-5 py-3 text-left">Property</th>
                        <th class="px-5 py-3 text-left">Price</th>
                        <th class="px-5 py-3 text-left">Type</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-left">Views</th>
                        <th class="px-5 py-3 text-left">Added</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($properties as $property)
                    @php
                        // Same featured-vs-status logic as the mobile cards above —
                        // is_featured + active = blue "Featured" badge; otherwise
                        // show the regular lifecycle status.
                        $showFeatured = $property->is_featured && $property->listing_status === 'active';
                        $statusClass  = $showFeatured
                            ? 'bg-blue-100 text-blue-700'
                            : match($property->listing_status) {
                                'active'     => 'bg-green-100 text-green-700',
                                'pending'    => 'bg-yellow-100 text-yellow-700',
                                'sold'       => 'bg-gray-100 text-gray-600',
                                'off-market' => 'bg-orange-100 text-orange-700',
                                'withdrawn'  => 'bg-red-100 text-red-600',
                                default      => 'bg-gray-100 text-gray-600',
                            };
                        $statusLabel  = $showFeatured
                            ? 'Featured'
                            : str_replace('-', ' ', ucwords($property->listing_status, '-'));
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-14 h-10 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0">
                                    @if($property->primaryImage)
                                        <img src="{{ asset('storage/'.$property->primaryImage->image_path) }}" class="w-full h-full object-cover">
                                    @endif
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800 text-sm">{{ Str::limit($property->title, 40) }}</p>
                                    <p class="text-xs text-gray-500">{{ $property->city }}{{ $property->state ? ', '.$property->state : '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 font-semibold text-gray-800">${{ number_format($property->price) }}</td>
                        <td class="px-5 py-4 text-sm text-gray-600 capitalize">{{ str_replace('-', ' ', $property->property_type) }}</td>
                        <td class="px-5 py-4">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-600">{{ number_format($property->view_count) }}</td>
                        <td class="px-5 py-4 text-sm text-gray-500">{{ $property->created_at->format('M j, Y') }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('tenant.property', [$account, $property->id]) }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('tenant.admin.properties.edit', [$account, $property->id]) }}" class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('tenant.admin.properties.destroy', [$account, $property->id]) }}" x-data onsubmit="return confirm('Delete this property?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">{{ $properties->links() }}</div>
        @else
        <div class="text-center py-16">
            <svg class="w-14 h-14 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">No properties found</h3>
            <a href="{{ route('tenant.admin.properties.create', $account) }}" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90 transition inline-block mt-2">Add Your First Property</a>
        </div>
        @endif
    </div>
</div>
@endsection
