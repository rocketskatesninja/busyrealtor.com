@extends('layouts.super-admin')
@section('title', 'Feedback')
@section('page-title', 'Realtor Feedback')

@section('content')
<div class="space-y-4">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Feedback</h1>
            <p class="text-gray-400 text-sm mt-0.5">Submissions from realtors</p>
        </div>
        @if($newCount > 0)
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-600 text-white">
            {{ $newCount }} new
        </span>
        @endif
    </div>

    <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
        @if($feedback->isEmpty())
        <div class="text-center py-16 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            <p>No feedback submissions yet.</p>
        </div>
        @else
        <div class="divide-y divide-gray-700">
            @foreach($feedback as $item)
            <a href="{{ route('super.feedback.show', $item->id) }}" class="flex items-start gap-4 px-5 py-4 hover:bg-gray-700/50 transition-colors block">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        @if($item->status === 'new')
                        <span class="inline-block w-2 h-2 rounded-full bg-blue-400 flex-shrink-0"></span>
                        @endif
                        <span class="font-medium text-white truncate">{{ $item->subject }}</span>
                        @if($item->hasScreenshot())
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        @endif
                    </div>
                    <p class="text-gray-400 text-sm truncate">{{ Str::limit($item->message, 120) }}</p>
                    <p class="text-gray-500 text-xs mt-1">
                        {{ $item->tenant->name ?? $item->tenant->slug }} &middot; {{ $item->user->name ?? 'Unknown' }} &middot; {{ $item->created_at->diffForHumans() }}
                    </p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full flex-shrink-0 mt-1 {{ $item->status === 'new' ? 'bg-blue-600/20 text-blue-300' : 'bg-gray-700 text-gray-400' }}">
                    {{ $item->status }}
                </span>
            </a>
            @endforeach
        </div>
        {{ $feedback->links() }}
        @endif
    </div>

</div>
@endsection
