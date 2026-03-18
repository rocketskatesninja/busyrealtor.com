@extends('layouts.super-admin')
@section('title', 'Feedback')
@section('page-title', 'Realtor Feedback')
@section('page-description', 'Review and manage feedback from realtors')

@section('content')
<div class="space-y-4">

    {{-- Filter Bar --}}
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search feedback..."
                       class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400">
            </div>
            <select name="status" class="bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Statuses</option>
                <option value="new" {{ request('status')=='new' ? 'selected' : '' }}>New</option>
                <option value="reviewed" {{ request('status')=='reviewed' ? 'selected' : '' }}>Reviewed</option>
            </select>
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-500 transition">Filter</button>
            @if(request()->hasAny(['search','status']))
                <a href="{{ route('super.feedback') }}" class="text-sm text-gray-400 hover:text-white py-2 transition">Clear</a>
            @endif
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Feedback List --}}
        <div class="lg:col-span-1">
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between">
                    <p class="text-sm text-gray-400">{{ $feedback->total() }} {{ Str::plural('submission', $feedback->total()) }}</p>
                    @if($newCount > 0)
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-600/20 text-blue-300">{{ $newCount }} new</span>
                    @endif
                </div>

                @if($feedback->isEmpty())
                <div class="text-center py-16 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    <p>No feedback submissions yet.</p>
                </div>
                @else
                <div class="divide-y divide-gray-700 max-h-[600px] overflow-y-auto">
                    @foreach($feedback as $item)
                    @php $isActive = $selectedItem && $selectedItem->id === $item->id; @endphp
                    <a href="{{ route('super.feedback') }}?{{ http_build_query(array_merge(array_filter(request()->except('id')), ['id' => $item->id])) }}"
                       class="block p-4 hover:bg-gray-700/50 transition-colors {{ $isActive ? 'bg-blue-900/30 border-l-4 border-blue-500' : '' }}">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-semibold
                                        {{ $item->status === 'new' ? 'bg-blue-600 text-white' : 'bg-gray-600 text-gray-300' }}">
                                {{ strtoupper(substr($item->user->name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm truncate {{ $item->status === 'new' ? 'font-bold text-white' : 'font-medium text-gray-300' }}">
                                        {{ $item->subject }}
                                        @if($item->hasScreenshot())
                                        <svg class="w-3.5 h-3.5 inline text-gray-500 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        @endif
                                    </p>
                                    <span class="text-xs font-medium px-1.5 py-0.5 rounded-full flex-shrink-0 {{ $item->status === 'new' ? 'bg-blue-600/20 text-blue-300' : 'bg-gray-700 text-gray-400' }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-400 truncate mt-0.5">{{ Str::limit($item->message, 60) }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-gray-500">{{ $item->tenant->name ?? $item->tenant->slug }}</span>
                                    <span class="text-xs text-gray-600">&middot;</span>
                                    <span class="text-xs text-gray-500">{{ $item->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @if($feedback->hasPages())
                <div class="p-3 border-t border-gray-700">{{ $feedback->appends(request()->except('page'))->links() }}</div>
                @endif
                @endif
            </div>
        </div>

        {{-- Detail Panel --}}
        <div class="lg:col-span-2">
            @if($selectedItem)
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-700 bg-gray-800/80">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-white truncate">{{ $selectedItem->subject }}</h2>
                            <div class="flex items-center gap-3 text-sm text-gray-400 mt-1">
                                <span>{{ $selectedItem->user->name ?? 'Unknown' }}</span>
                                @if($selectedItem->user->email ?? false)
                                <span class="text-gray-600">&middot;</span>
                                <span>{{ $selectedItem->user->email }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold flex-shrink-0
                                     {{ $selectedItem->status === 'new' ? 'bg-blue-600/20 text-blue-300' : 'bg-gray-700 text-gray-400' }}">
                            {{ ucfirst($selectedItem->status) }}
                        </span>
                    </div>
                </div>

                {{-- Meta Bar --}}
                <div class="px-6 py-3 border-b border-gray-700 flex items-center gap-4 text-xs text-gray-500">
                    <span>{{ $selectedItem->tenant->name ?? $selectedItem->tenant->slug }}</span>
                    <span class="text-gray-600">&middot;</span>
                    <span>{{ $selectedItem->created_at->format('M j, Y g:i A') }}</span>
                    <span class="text-gray-600">&middot;</span>
                    <span>{{ $selectedItem->created_at->diffForHumans() }}</span>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5">
                    <p class="text-gray-200 text-sm leading-relaxed whitespace-pre-wrap">{{ $selectedItem->message }}</p>
                </div>

                {{-- Screenshots --}}
                @if($selectedItem->hasScreenshot())
                <div class="px-6 py-4 border-t border-gray-700">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                        Screenshots ({{ count($selectedItem->screenshots()) }})
                    </h3>
                    <div class="{{ count($selectedItem->screenshots()) > 1 ? 'grid grid-cols-2 gap-3' : '' }}">
                        @foreach($selectedItem->screenshots() as $i => $path)
                        <div>
                            <img src="{{ route('super.feedback.screenshot', [$selectedItem->id, $i]) }}"
                                 alt="Screenshot {{ $i + 1 }}"
                                 class="max-w-full w-full rounded-lg border border-gray-700 cursor-zoom-in"
                                 @click="$el.classList.toggle('w-full'); $el.classList.toggle('w-auto'); $el.classList.toggle('max-w-full')"
                                 x-data>
                            <a href="{{ route('super.feedback.screenshot', [$selectedItem->id, $i]) }}" download
                               class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-white mt-1.5 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Download #{{ $i + 1 }}
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Actions --}}
                <div class="px-6 py-4 border-t border-gray-700 flex justify-end">
                    <form method="POST" action="{{ route('super.feedback.destroy', $selectedItem->id) }}"
                          onsubmit="return confirm('Delete this feedback? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-red-400 hover:text-red-300 border border-red-800/50 rounded-lg hover:bg-red-900/20 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Delete
                        </button>
                    </form>
                </div>
            </div>
            @else
            <div class="bg-gray-800 rounded-xl border border-gray-700 flex items-center justify-center" style="min-height: 400px">
                <div class="text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    <p class="text-sm">Select a feedback submission to view details</p>
                </div>
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
