@extends('layouts.admin')

@section('title', 'AI Assistant')

@section('content')
<style>main{min-height:0!important;overflow:hidden;}body{overflow:hidden;}#user-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--primary) 20%,transparent);}</style>
<div id="chat-wrapper" class="mx-auto flex flex-col bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
     style="max-width:1281px; min-height:500px;">

    {{-- Header --}}
    <div class="flex items-center justify-between px-6 py-4 border-b bg-white flex-shrink-0">
        <div>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <h1 class="text-lg font-bold text-gray-900">AI Assistant</h1>
            </div>
            <p class="text-xs text-gray-400 mt-0.5 ml-7">Powered by {{ $providerLabel }} &middot; {{ $modelLabel }}</p>
        </div>
        <a href="{{ route('tenant.admin.assistant', $account) }}?new=1"
           title="Start a new conversation"
           class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-700 border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New chat
        </a>
    </div>

    {{-- Messages --}}
    <div id="messages" class="flex-1 overflow-y-auto px-5 py-5 bg-gray-50" style="display:flex; flex-direction:column; gap:16px;">
        @foreach($chatLogs as $i => $log)
            @if($log->role === 'assistant')
            <div style="display:flex; gap:12px;">
                <div class="flex-shrink-0 flex items-center justify-center text-white"
                     style="background-color:var(--primary); width:32px; height:32px; border-radius:50%; flex-shrink:0;">
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div style="flex:1; min-width:0;">
                    <div class="bg-white border border-gray-100 text-gray-800 text-sm leading-relaxed shadow-sm js-format"
                         style="padding:12px 16px; border-radius:16px; border-top-left-radius:4px; word-break:break-word;"
                         @if($i === 0) id="motd-bubble" @endif>
                        {!! nl2br(e($log->content)) !!}
                    </div>
                    <p class="text-xs text-gray-400" style="margin-top:4px; margin-left:4px;">
                        {{ $i === 0 ? 'Session started ' : '' }}{{ $log->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>
            @else
            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <div style="max-width:75%; display:flex; flex-direction:column; align-items:flex-end;">
                    <div class="text-sm text-white leading-relaxed"
                         style="background-color:var(--primary); padding:12px 16px; border-radius:16px; border-top-right-radius:4px; word-break:break-word;">
                        {!! nl2br(e($log->content)) !!}
                    </div>
                    <p class="text-xs text-gray-400" style="margin-top:4px; text-align:right;">
                        {{ $log->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>
            @endif
        @endforeach
    </div>

    {{-- Typing indicator --}}
    <div id="typing" class="hidden px-5 pb-3 bg-gray-50 flex-shrink-0">
        <div style="display:flex; gap:12px;">
            <div class="flex-shrink-0 flex items-center justify-center text-white"
                 style="background-color:var(--primary); width:32px; height:32px; border-radius:50%;">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <div class="bg-white border border-gray-100 shadow-sm" style="padding:12px 16px; border-radius:16px; border-top-left-radius:4px;">
                <div style="display:flex; gap:4px; align-items:center; height:20px;">
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Disclaimer --}}
    <div x-data class="border-t flex-shrink-0 transition-colors"
         :class="$store.theme.dark ? 'bg-yellow-900/20 border-yellow-800/40' : 'bg-amber-50 border-amber-100'">
        <div style="display:flex; align-items:flex-start; gap:8px; padding:8px 20px;">
            <svg class="flex-shrink-0 transition-colors" style="width:14px; height:14px; margin-top:2px;"
                 :class="$store.theme.dark ? 'text-yellow-400' : 'text-amber-500'"
                 fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <p class="text-xs leading-snug transition-colors"
               :class="$store.theme.dark ? 'text-yellow-300/80' : 'text-amber-700'">
                AI can make mistakes &mdash; always verify critical information independently.
                All changes are logged. Not financial, legal, or professional real estate advice.
            </p>
        </div>
    </div>

    {{-- Input --}}
    <div class="border-t bg-white flex-shrink-0 px-4 py-3">
        <div style="display:flex; gap:12px; align-items:flex-end;">
            <textarea id="user-input" rows="1"
                placeholder="Ask anything about your listings, messages, or appointments&hellip;"
                class="flex-1 resize-none border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none leading-relaxed"
                style="max-height:140px;"></textarea>
            <button id="send-btn"
                class="flex-shrink-0 flex items-center justify-center text-white transition-opacity disabled:opacity-40"
                style="background-color:var(--primary); width:40px; height:40px; border-radius:12px;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
        <p class="text-xs text-gray-400 mt-1.5 text-right">Enter to send &middot; Shift+Enter for new line</p>
    </div>

</div>
@endsection

@section('scripts')
const SESSION_ID = '{{ $sessionId }}';
const API_URL    = '{{ route("tenant.admin.api.assistant", $account) }}';
const CSRF       = document.querySelector('meta[name="csrf-token"]').content;

function fitHeight() {
    const header  = document.querySelector('header');
    const main    = document.querySelector('main');
    const wrapper = document.getElementById('chat-wrapper');
    if (!header || !wrapper) return;
    const cs   = getComputedStyle(main);
    const used = header.offsetHeight + parseInt(cs.paddingTop) + parseInt(cs.paddingBottom);
    wrapper.style.height = (window.innerHeight - used) + 'px';
}
fitHeight();
window.addEventListener('resize', fitHeight);

function escapeHtml(text) {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function formatText(text) {
    return escapeHtml(text)
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/^[ \t]*[•\-] (.+)$/gm, '<span style="display:block;padding-left:1rem;position:relative"><span style="position:absolute;left:0">&bull;</span>$1</span>')
        .replace(/\n/g, '<br>');
}

const AVATAR_SVG = `<svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>`;

function appendMessage(role, text) {
    const isUser = role === 'user';
    const wrap   = document.createElement('div');
    wrap.style.cssText = 'display:flex; gap:12px;' + (isUser ? 'justify-content:flex-end;' : '');

    const ts = document.createElement('p');
    ts.className = 'text-xs text-gray-400';
    ts.style.marginTop = '4px';
    ts.textContent = 'Just now';

    if (isUser) {
        const col = document.createElement('div');
        col.style.cssText = 'max-width:75%; display:flex; flex-direction:column; align-items:flex-end;';
        const bubble = document.createElement('div');
        bubble.className = 'text-sm text-white leading-relaxed';
        bubble.style.cssText = 'background-color:var(--primary); padding:12px 16px; border-radius:16px; border-top-right-radius:4px; word-break:break-word;';
        bubble.innerHTML = escapeHtml(text).replace(/\n/g, '<br>');
        ts.style.textAlign = 'right';
        col.appendChild(bubble);
        col.appendChild(ts);
        wrap.appendChild(col);
    } else {
        const avatar = document.createElement('div');
        avatar.style.cssText = 'background-color:var(--primary); width:32px; height:32px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; color:white;';
        avatar.innerHTML = AVATAR_SVG;
        const col = document.createElement('div');
        col.style.cssText = 'flex:1; min-width:0;';
        const bubble = document.createElement('div');
        bubble.className = 'bg-white border border-gray-100 text-gray-800 text-sm leading-relaxed shadow-sm';
        bubble.style.cssText = 'padding:12px 16px; border-radius:16px; border-top-left-radius:4px; word-break:break-word;';
        bubble.innerHTML = formatText(text);
        col.appendChild(bubble);
        col.appendChild(ts);
        wrap.appendChild(avatar);
        wrap.appendChild(col);
    }

    document.getElementById('messages').appendChild(wrap);
    scrollToBottom();
}

function scrollToBottom() {
    const m = document.getElementById('messages');
    m.scrollTop = m.scrollHeight;
}

function setLoading(on) {
    document.getElementById('typing').classList.toggle('hidden', !on);
    document.getElementById('send-btn').disabled = on;
    document.getElementById('user-input').disabled = on;
    if (on) scrollToBottom();
}

async function sendMessage() {
    const input = document.getElementById('user-input');
    const text  = input.value.trim();
    if (!text) return;
    input.value = '';
    input.style.height = 'auto';
    appendMessage('user', text);
    setLoading(true);
    try {
        const res  = await fetch(API_URL, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body:    JSON.stringify({ message: text, session_id: SESSION_ID }),
        });
        const data = await res.json();
        setLoading(false);
        appendMessage('assistant', data.reply || data.error || 'Something went wrong. Please try again.');
    } catch (e) {
        setLoading(false);
        appendMessage('assistant', 'Network error. Please check your connection and try again.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Apply markdown formatting to all server-rendered assistant bubbles
    document.querySelectorAll('.js-format').forEach(el => {
        el.innerHTML = formatText(el.innerText);
    });
    scrollToBottom();
});

document.getElementById('user-input').addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});
document.getElementById('send-btn').addEventListener('click', sendMessage);
document.getElementById('user-input').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 140) + 'px';
});
@endsection
