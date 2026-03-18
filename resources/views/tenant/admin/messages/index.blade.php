@extends('layouts.admin')
@section('title', 'Messages')
@section('page-subtitle', 'Incoming contact form submissions')
@section('content')
@php $account = $tenant->slug; @endphp
<div class="max-w-7xl mx-auto px-4">

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 mb-6">
        <form method="GET" class="flex flex-wrap gap-3 items-end justify-center md:justify-start">
            <div class="w-full md:flex-1 md:min-w-40">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search messages..." class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            </div>
            <select name="type" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                <option value="">All Sources</option>
                <option value="contact_form" {{ request('type')=='contact_form' ? 'selected' : '' }}>Contact Form</option>
                <option value="chatbot"      {{ request('type')=='chatbot'      ? 'selected' : '' }}>Chatbot</option>
            </select>
            <select name="status" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                <option value="">All Statuses</option>
                <option value="new"      {{ request('status')=='new'      ? 'selected' : '' }}>New</option>
                <option value="replied"  {{ request('status')=='replied'  ? 'selected' : '' }}>Replied</option>
                <option value="archived" {{ request('status')=='archived' ? 'selected' : '' }}>Archived</option>
                <option value="spam"     {{ request('status')=='spam'     ? 'selected' : '' }}>Spam</option>
            </select>
            <select name="sort" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none">
                <option value="newest"  {{ request('sort','newest')==='newest'  ? 'selected' : '' }}>Newest</option>
                <option value="oldest"  {{ request('sort')==='oldest'  ? 'selected' : '' }}>Oldest</option>
                <option value="starred" {{ request('sort')==='starred' ? 'selected' : '' }}>Starred First</option>
            </select>
            <button type="submit" class="btn-primary px-5 py-2.5 rounded-lg font-semibold text-sm hover:opacity-90 transition">Filter</button>
            @if(request()->hasAny(['search','type','status','sort']))
                <a href="{{ route('tenant.admin.messages.index', $account) }}" class="text-sm text-gray-500 hover:text-gray-700 py-2.5">Clear</a>
            @endif
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Messages List --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 border-b flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $messages->total() }} {{ Str::plural('message', $messages->total()) }}</p>
                    @if($unreadCount > 0)
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">{{ $unreadCount }} unread</span>
                    @endif
                </div>
                @if($messages->count())
                <div class="divide-y divide-gray-200 max-h-[600px] overflow-y-auto">
                    @foreach($messages as $msg)
                    @php
                        $isActive = request('view') == $msg->id;
                        $statusColor = match($msg->status) {
                            'new'      => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
                            'replied'  => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
                            'archived' => 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400',
                            'spam'     => 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400',
                            default    => 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400',
                        };
                    @endphp
                    <a href="{{ route('tenant.admin.messages.index', $account) }}?{{ http_build_query(array_merge(array_filter(request()->except('view')), ['view' => $msg->id])) }}"
                       class="block p-4 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors
                              {{ $isActive ? 'bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500' : '' }}
                              {{ !$msg->is_read && !$isActive ? 'bg-yellow-50/50 dark:bg-yellow-900/20' : '' }}">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-semibold
                                        {{ $msg->is_read ? 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300' : 'text-white' }}"
                                 @if(!$msg->is_read) style="background-color: var(--primary)" @endif>
                                {{ strtoupper(substr($msg->sender_name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm truncate {{ !$msg->is_read ? 'font-bold text-gray-900 dark:text-gray-100' : 'font-medium text-gray-700 dark:text-gray-300' }}">
                                        {{ $msg->sender_name }}
                                        @if($msg->is_starred) <svg class="w-3.5 h-3.5 inline text-yellow-500 -mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg> @endif
                                    </p>
                                    <span class="text-xs font-medium px-1.5 py-0.5 rounded-full {{ $statusColor }} flex-shrink-0">{{ ucfirst($msg->status) }}</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ Str::limit($msg->message, 60) }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $msg->created_at->diffForHumans() }}</span>
                                    <span class="text-xs text-gray-300 dark:text-gray-600">&middot;</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500 capitalize">{{ $msg->source === 'contact_form' ? 'Contact' : ucfirst($msg->source ?? 'contact') }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                <div class="p-3 border-t">{{ $messages->links() }}</div>
                @else
                <div class="text-center py-12 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <p class="text-sm font-medium">No messages found</p>
                    @if(request()->hasAny(['search','type','status']))
                        <a href="{{ route('tenant.admin.messages.index', $account) }}" class="text-sm text-blue-600 hover:underline mt-1 inline-block">Clear filters</a>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- Message Detail --}}
        <div class="lg:col-span-2">
            @if($message)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                {{-- Detail Header --}}
                <div class="px-6 py-4 border-b bg-gray-50/50 dark:bg-slate-800/50 flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 rounded-full flex items-center justify-center text-white font-semibold text-lg flex-shrink-0" style="background-color: var(--primary)">
                            {{ strtoupper(substr($message->sender_name ?? 'U', 0, 1)) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-gray-100">{{ $message->sender_name }}</h3>
                            <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                <a href="mailto:{{ $message->sender_email }}" class="hover:text-blue-600 transition">{{ $message->sender_email }}</a>
                                @if($message->sender_phone)
                                    <span class="text-gray-300">|</span>
                                    <span>{{ $message->sender_phone }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button onclick="msgAction('star', {{ $message->id }})" class="p-2 rounded-lg hover:bg-gray-100 transition {{ $message->is_starred ? 'text-yellow-500' : 'text-gray-400' }}" title="Star">
                            <svg class="w-5 h-5" fill="{{ $message->is_starred ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </button>
                        <select onchange="msgAction('status', {{ $message->id }}, this.value)" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none">
                            @foreach(['new'=>'New','replied'=>'Replied','archived'=>'Archived','spam'=>'Spam'] as $v=>$l)
                            <option value="{{ $v }}" {{ $message->status === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                        <button onclick="if(confirm('Delete this message?')) msgAction('delete', {{ $message->id }})" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50" title="Delete">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Meta --}}
                <div class="px-6 py-3 border-b flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ $message->created_at->format('M j, Y \a\t g:i A') }}</span>
                    <span class="text-gray-300">&middot;</span>
                    <span class="font-medium px-2 py-0.5 rounded-full {{ match($message->source) { 'contact_form' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400', 'chatbot' => 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400', default => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' } }}">
                        {{ $message->source === 'contact_form' ? 'Contact Form' : ucfirst($message->source ?? 'Contact') }}
                    </span>
                    @if($message->property_id)
                        <span class="text-gray-300">&middot;</span>
                        <span>Re: Property #{{ $message->property_id }}</span>
                    @endif
                </div>

                {{-- Message Body --}}
                <div class="px-6 py-5">
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">{{ $message->message }}</p>
                </div>

                {{-- Reply --}}
                <div class="px-6 py-4 border-t bg-gray-50/50 dark:bg-slate-800/50">
                    @if($message->sender_email)
                    <a href="mailto:{{ $message->sender_email }}?subject=Re: Your Inquiry" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold hover:opacity-90 transition inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10l1.5 1.5L3 13M3 10l8 5 8-5M21 10l-1.5 1.5L21 13M21 10v7a2 2 0 01-2 2H5a2 2 0 01-2-2v-7"/></svg>
                        Reply to {{ $message->sender_name }}
                    </a>
                    @endif
                </div>
            </div>
            @else
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex items-center justify-center" style="min-height: 400px">
                <div class="text-center text-gray-400 dark:text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <p class="font-medium">Select a message to view</p>
                    <p class="text-sm mt-1">Choose from the list on the left</p>
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
