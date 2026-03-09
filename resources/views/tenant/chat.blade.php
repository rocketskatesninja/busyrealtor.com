@extends('layouts.tenant')
@section('title', 'Chat Assistant — ' . ($settings->site_title ?? 'BusyRealtor'))

@section('hide_header')@endsection
@section('hide_chatbot')@endsection
@section('hide_contact')@endsection

@section('content')
@php $account = $tenant->slug; @endphp
<div class="flex flex-col bg-gray-50" style="height: 100vh; height: 100dvh">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 py-3 text-white shadow-sm flex-shrink-0" style="background-color: var(--primary)">
        <a href="{{ route('tenant.home', $account) }}" class="p-1 rounded hover:bg-white/20 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-sm leading-tight">{{ $settings->site_title ?? 'Chat Assistant' }}</p>
            <p class="text-xs opacity-80">Typically replies instantly</p>
        </div>
    </div>

    {{-- Messages --}}
    <div id="chat-messages" class="flex-1 overflow-y-auto px-4 py-4 space-y-3" style="-webkit-overflow-scrolling: touch">
        {{-- Welcome message --}}
        <div class="flex items-start gap-2">
            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 text-white" style="background-color: var(--primary)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            </div>
            <div class="bg-white rounded-2xl rounded-tl-sm px-4 py-2.5 shadow-sm max-w-xs text-sm text-gray-800">
                Hi! I'm the virtual assistant for {{ $settings->site_title ?? 'this agency' }}. How can I help you today?
            </div>
        </div>
    </div>

    {{-- Typing indicator (hidden by default) --}}
    <div id="chat-typing" class="hidden px-4 pb-2">
        <div class="flex items-start gap-2">
            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-white" style="background-color: var(--primary)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            </div>
            <div class="bg-white rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm">
                <div class="flex gap-1 items-center">
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Input --}}
    <div class="flex-shrink-0 bg-white border-t px-4 py-3">
        <form id="chat-form" class="flex items-end gap-2">
            <textarea id="chat-input" rows="1" placeholder="Type a message..."
                      class="flex-1 px-4 py-2.5 border border-gray-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:border-transparent resize-none overflow-hidden"
                      style="--tw-ring-color: var(--primary); max-height: 120px"
                      onInput="this.style.height='auto'; this.style.height=this.scrollHeight+'px'"
                      onKeydown="if(event.key==='Enter' && !event.shiftKey){ event.preventDefault(); document.getElementById('chat-form').dispatchEvent(new Event('submit', {bubbles:true,cancelable:true})); }"></textarea>
            <button type="submit" id="chat-send"
                    class="w-10 h-10 rounded-full flex items-center justify-center text-white flex-shrink-0 transition hover:opacity-90 disabled:opacity-50"
                    style="background-color: var(--primary)">
                <svg class="w-5 h-5 translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </button>
        </form>
        <p class="text-center text-xs text-gray-400 mt-2">AI assistant · Not legal, financial, or real estate advice · Conversations are not stored</p>
    </div>
</div>

@section('scripts')
(function() {
    const API_URL = '{{ route('tenant.api.chatbot', $account) }}';
    const CSRF    = document.querySelector('meta[name=csrf-token]')?.content || '';
    const messages = document.getElementById('chat-messages');
    const typingEl = document.getElementById('chat-typing');
    const input    = document.getElementById('chat-input');
    const sendBtn  = document.getElementById('chat-send');
    const history  = [];

    function scrollBottom() {
        messages.scrollTop = messages.scrollHeight;
        setTimeout(() => { messages.scrollTop = messages.scrollHeight; }, 80);
    }

    function addMessage(text, isUser) {
        const wrapper = document.createElement('div');
        wrapper.className = 'flex items-end gap-2' + (isUser ? ' flex-row-reverse' : '');

        if (!isUser) {
            const avatar = document.createElement('div');
            avatar.className = 'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-white';
            avatar.style.backgroundColor = 'var(--primary)';
            avatar.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>';
            wrapper.appendChild(avatar);
        }

        const bubble = document.createElement('div');
        bubble.className = isUser
            ? 'px-4 py-2.5 rounded-2xl rounded-br-sm text-sm text-white max-w-xs shadow-sm'
            : 'bg-white px-4 py-2.5 rounded-2xl rounded-tl-sm text-sm text-gray-800 max-w-xs shadow-sm';
        if (isUser) bubble.style.backgroundColor = 'var(--primary)';
        bubble.textContent = text;
        wrapper.appendChild(bubble);
        messages.appendChild(wrapper);
        scrollBottom();
    }

    document.getElementById('chat-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;

        addMessage(text, true);
        history.push({ role: 'user', content: text });
        input.value = '';
        input.style.height = 'auto';
        sendBtn.disabled = true;
        typingEl.classList.remove('hidden');
        messages.appendChild(typingEl); // keep at bottom
        scrollBottom();

        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ message: text, history: history.slice(-10) })
            });
            const data = await res.json();
            const reply = data.reply || data.message || 'Sorry, I could not get a response.';
            typingEl.classList.add('hidden');
            addMessage(reply, false);
            history.push({ role: 'assistant', content: reply });
        } catch(err) {
            typingEl.classList.add('hidden');
            addMessage('Sorry, something went wrong. Please try again.', false);
        } finally {
            sendBtn.disabled = false;
            input.focus();
        }
    });

    scrollBottom();
})();
@endsection
@endsection
