@extends('layouts.admin')
@section('title', 'Messages')
@section('content')
@php $account = $tenant->slug; @endphp
<div class="max-w-7xl mx-auto px-4">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Messages</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Messages List --}}
        <div class="lg:col-span-1">
            {{-- Filters --}}
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 mb-4">
                <form method="GET" class="space-y-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search messages..." class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div class="grid grid-cols-2 gap-2">
                        <select name="type" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
                            <option value="">All Types</option>
                            <option value="contact_form" {{ request('type')=='contact_form' ? 'selected' : '' }}>Contact</option>
                            <option value="chatbot"      {{ request('type')=='chatbot'      ? 'selected' : '' }}>Chatbot</option>
                        </select>
                        <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none">
                            <option value="">All</option>
                            <option value="new"      {{ request('status')=='new'      ? 'selected' : '' }}>New</option>
                            <option value="replied"  {{ request('status')=='replied'  ? 'selected' : '' }}>Replied</option>
                            <option value="archived" {{ request('status')=='archived' ? 'selected' : '' }}>Archived</option>
                            <option value="spam"     {{ request('status')=='spam'     ? 'selected' : '' }}>Spam</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary w-full py-2 rounded-lg text-sm font-semibold hover:opacity-90 transition">Filter</button>
                </form>
            </div>

            {{-- Messages --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                @if($messages->count())
                <div class="divide-y max-h-[600px] overflow-y-auto">
                    @foreach($messages as $msg)
                    <a href="{{ route('tenant.admin.messages.index', $account) }}?view={{ $msg->id }}&{{ request()->getQueryString() }}"
                       class="block p-4 hover:bg-gray-50 transition-colors
                              {{ request('view') == $msg->id ? 'bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500' : '' }}
                              {{ !$msg->is_read && request('view') != $msg->id ? 'bg-yellow-50/50 dark:bg-yellow-900/20' : '' }}">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0 text-sm font-medium text-gray-600">
                                {{ strtoupper(substr($msg->sender_name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-medium text-gray-800 text-sm truncate {{ !$msg->is_read ? 'font-bold' : '' }}">{{ $msg->sender_name }}</p>
                                    @if(!$msg->is_read) <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: var(--primary)"></span> @endif
                                </div>
                                <p class="text-xs text-gray-500 truncate">{{ Str::limit($msg->message, 50) }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $msg->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                <div class="p-3 border-t">{{ $messages->links() }}</div>
                @else
                <div class="text-center py-12 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <p class="text-sm font-medium">No messages</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Message Detail --}}
        <div class="lg:col-span-2">
            @if($message)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg">{{ $message->sender_name }}</h3>
                        <p class="text-sm text-gray-500">{{ $message->sender_email }}</p>
                        @if($message->sender_phone) <p class="text-sm text-gray-500">{{ $message->sender_phone }}</p> @endif
                        <p class="text-xs text-gray-400 mt-1">{{ $message->created_at->format('M j, Y 	 g:i A') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="msgAction('star', {{ $message->id }})" class="p-2 rounded-lg hover:bg-gray-100 transition {{ $message->is_starred ? 'text-yellow-500' : 'text-gray-400' }}" title="Star">
                            <svg class="w-5 h-5" fill="{{ $message->is_starred ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </button>
                        <select onchange="msgAction('status', {{ $message->id }}, this.value)" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none">
                            @foreach(['new'=>'New','replied'=>'Replied','archived'=>'Archived','spam'=>'Spam'] as $v=>$l)
                            <option value="{{ $v }}" {{ $message->status === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                        <button onclick="if(confirm('Delete?')) msgAction('delete', {{ $message->id }})" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20" title="Delete">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl p-5 mb-6">
                    <div class="flex gap-2 mb-3">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-200 text-gray-600 capitalize">
                            {{ $message->source === 'contact_form' ? 'Contact Form' : ucfirst($message->source ?? 'contact') }}
                        </span>
                    </div>
                    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $message->message }}</p>
                </div>
                <div class="border-t pt-4">
                    <h4 class="font-medium text-gray-800 mb-3">Reply via Email</h4>
                    @if($message->sender_email)
                    <a href="mailto:{{ $message->sender_email }}?subject=Re: Your Inquiry" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90 transition inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Reply to {{ $message->sender_email }}
                    </a>
                    @endif
                </div>
            </div>
            @else
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex items-center justify-center" style="min-height: 400px">
                <div class="text-center text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <p class="font-medium">Select a message to view</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
async function msgAction(action, id, value = null) {
    await fetch('{{ route('tenant.admin.messages.action', $account) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ action, id, status: value })
    });
    if (action === 'delete') { window.location = '{{ route('tenant.admin.messages.index', $account) }}'; } else { location.reload(); }
}
@endsection
