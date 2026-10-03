// The admin AI assistant: the chat transcript, the composer and the request to the API.
//
// Moved out of the view unchanged apart from where its three constants come from. The
// session id and endpoint arrive as JSON on the chat wrapper; the CSRF token comes from
// the meta tag, as it did before.

const root = document.getElementById('chat-wrapper');

function config() {
    try {
        return JSON.parse(root?.dataset.config || '{}');
    } catch (e) {
        return {};
    }
}

const { sessionId: SESSION_ID, apiUrl: API_URL } = config();
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function fitHeight() {
    const header  = document.querySelector('header');
    const main    = document.querySelector('main');
    const wrapper = document.getElementById('chat-wrapper');
    if (!header || !wrapper) return;
    const cs   = getComputedStyle(main);
    const used = header.offsetHeight + parseInt(cs.paddingTop) + parseInt(cs.paddingBottom);
    wrapper.style.height = (window.innerHeight - used) + 'px';
}
if (root) {
    fitHeight();
    window.addEventListener('resize', fitHeight);
}

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
        document.getElementById('user-input').focus();
        appendMessage('assistant', data.reply || data.error || 'Something went wrong. Please try again.');
    } catch (e) {
        setLoading(false);
        document.getElementById('user-input').focus();
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

if (root) {
    const input = document.getElementById('user-input');

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
    document.getElementById('send-btn').addEventListener('click', sendMessage);
    input.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 140) + 'px';
    });
}
