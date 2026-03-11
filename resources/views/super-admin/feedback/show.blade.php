@extends('layouts.super-admin')
@section('title', 'Feedback — ' . $item->subject)
@section('page-title', 'Feedback Detail')

@section('content')
<div class="max-w-2xl space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ route('super.feedback') }}" class="text-gray-400 hover:text-white transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h1 class="text-xl font-bold text-white">{{ $item->subject }}</h1>
    </div>

    {{-- Meta --}}
    <div class="bg-gray-800 rounded-xl border border-gray-700 px-5 py-4 flex flex-wrap gap-4 text-sm">
        <div>
            <p class="text-gray-500 text-xs mb-0.5">From</p>
            <p class="text-white font-medium">{{ $item->user->name ?? 'Unknown' }}</p>
            <p class="text-gray-400 text-xs">{{ $item->user->email ?? '' }}</p>
        </div>
        <div>
            <p class="text-gray-500 text-xs mb-0.5">Account</p>
            <p class="text-white font-medium">{{ $item->tenant->name ?? $item->tenant->slug }}</p>
        </div>
        <div>
            <p class="text-gray-500 text-xs mb-0.5">Submitted</p>
            <p class="text-white font-medium">{{ $item->created_at->format('M j, Y g:i A') }}</p>
            <p class="text-gray-400 text-xs">{{ $item->created_at->diffForHumans() }}</p>
        </div>
        <div>
            <p class="text-gray-500 text-xs mb-0.5">Status</p>
            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $item->status === 'new' ? 'bg-blue-600/20 text-blue-300' : 'bg-gray-700 text-gray-300' }}">
                {{ ucfirst($item->status) }}
            </span>
        </div>
    </div>

    {{-- Message --}}
    <div class="bg-gray-800 rounded-xl border border-gray-700 px-5 py-4">
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Message</h3>
        <p class="text-gray-200 text-sm leading-relaxed whitespace-pre-wrap">{{ $item->message }}</p>
    </div>

    {{-- Screenshots --}}
    @if($item->hasScreenshot())
    <div class="bg-gray-800 rounded-xl border border-gray-700 px-5 py-4">
        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
            Screenshots ({{ count($item->screenshots()) }})
        </h3>
        <div class="{{ count($item->screenshots()) > 1 ? 'grid grid-cols-2 gap-3' : '' }}">
            @foreach($item->screenshots() as $i => $path)
            <div>
                <img src="{{ route('super.feedback.screenshot', [$item->id, $i]) }}"
                     alt="Screenshot {{ $i + 1 }}"
                     class="max-w-full w-full rounded-lg border border-gray-700 cursor-zoom-in"
                     @click="$el.classList.toggle('w-full'); $el.classList.toggle('w-auto'); $el.classList.toggle('max-w-full')"
                     x-data>
                <a href="{{ route('super.feedback.screenshot', [$item->id, $i]) }}" download
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
    <div class="flex justify-end">
        <form method="POST" action="{{ route('super.feedback.destroy', $item->id) }}"
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
@endsection
