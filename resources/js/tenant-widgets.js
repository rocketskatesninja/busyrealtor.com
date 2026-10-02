/*
 | The two floating widgets on the public tenant site: the chatbot bubble and the contact
 | bubble.
 |
 | These were 24.8 KB of inline <script> in layouts/tenant.blade.php — around 41% of all the
 | inline JavaScript in the application, shipped inside the HTML of every public page on every
 | request, uncacheable. Moved here verbatim so the move itself can be verified before anything
 | is restructured; the drag and positioning machinery they genuinely share is deduped as a
 | separate step.
 |
 | The three values they used to get from Blade now arrive as data attributes on their root
 | elements, which is what lets the code live in a bundle at all. Each widget returns early
 | when its root is absent: the chatbot is only rendered when the tenant has configured one.
 */

(function() {
    const root = document.getElementById('chatbot-root');
    if (!root) return;                       // the chatbot is only rendered when it is configured

    const API_URL = root.dataset.apiUrl || '';
    const CSRF = () => document.querySelector('meta[name=csrf-token]')?.content || '';
    const STORAGE_KEY = 'chatbot_position';
    let state = { isOpen: false, sessionId: null, isLoading: false, messages: [], isDragging: false, position: null };

    document.addEventListener('DOMContentLoaded', init);

    function init() { loadPosition(); createWidget(); loadConversation(); }

    function loadPosition() {
        try { const s = localStorage.getItem(STORAGE_KEY); if (s) state.position = JSON.parse(s); } catch(e) {}
        if (!state.position) state.position = { right: 24, bottom: 24 };
    }
    function savePosition() { localStorage.setItem(STORAGE_KEY, JSON.stringify(state.position)); }

    function esc(t) {
        return String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').split('\n').join('<br>');
    }

    function createWidget() {
        document.getElementById('chatbot-root').innerHTML = `
<button id="chatbot-btn" class="fixed w-14 h-14 rounded-full shadow-xl flex items-center justify-center text-white z-50 cursor-grab active:cursor-grabbing select-none" style="background-color:var(--primary);right:24px;bottom:24px">
    <svg width="24" height="24" id="chatbot-icon-chat" class="w-6 h-6 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
    </svg>
    <svg width="24" height="24" id="chatbot-icon-x" class="w-6 h-6 pointer-events-none hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
    </svg>
</button>
<div id="chatbot-modal" class="fixed w-96 bg-white rounded-xl shadow-2xl z-50 hidden" style="height:480px;max-height:calc(100vh - 80px);max-width:calc(100vw - 2rem);display:none;flex-direction:column">
    <div class="flex items-center justify-between p-4 border-b rounded-t-xl shrink-0" style="background-color:var(--primary)">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center">
                <svg width="20" height="20" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            </div>
            <div class="text-white">
                <div class="font-semibold text-sm">Chat Assistant</div>
                <div class="text-xs opacity-80">Online</div>
            </div>
        </div>
        <button id="chatbot-close" class="text-white/80 hover:text-white hover:bg-white/20 rounded p-1 transition-colors">
            <svg width="20" height="20" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div id="chatbot-messages" class="flex-1 overflow-y-auto p-4 space-y-3" style="background:#f7f8fa"></div>
    <div class="p-3 border-t bg-white rounded-b-xl shrink-0">
        <form id="chatbot-form" class="flex gap-2">
            <input type="text" id="chatbot-input" placeholder="Type a message..." autocomplete="off"
                   class="flex-1 border border-gray-200 rounded-full px-4 py-2 text-sm focus:outline-none">
            <button type="submit" id="chatbot-send" class="px-4 py-2 rounded-full text-white text-sm font-medium shrink-0" style="background-color:var(--primary)">Send</button>
        </form>
        <div id="chatbot-typing" class="hidden text-xs text-gray-400 mt-2 px-1">Typing...</div>
        <p class="text-center text-gray-400 mt-1.5 px-1" style="font-size:0.65rem;line-height:1.3">AI assistant &middot; Not legal, financial, or real estate advice</p>
    </div>
</div>`;

        // Keep chatbot-messages background in sync with dark mode
        const messagesEl = document.getElementById('chatbot-messages');
        function syncChatDark() {
            messagesEl.style.background = document.documentElement.classList.contains('dark') ? '#0f172a' : '#f7f8fa';
        }
        syncChatDark();
        new MutationObserver(syncChatDark).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        const btn = document.getElementById('chatbot-btn');
        const modal = document.getElementById('chatbot-modal');
        applyPosition(btn);
        makeDraggable(btn, () => { if (state.isOpen) positionModal(btn, modal); });
        btn.addEventListener('click', () => { if (!state.isDragging) toggle(); });
        document.getElementById('chatbot-close').addEventListener('click', close);
        document.getElementById('chatbot-form').addEventListener('submit', handleSubmit);
    }

    function applyPosition(el) {
        el.style.left   = state.position.left   !== undefined ? state.position.left   + 'px' : 'auto';
        el.style.right  = state.position.right  !== undefined ? state.position.right  + 'px' : 'auto';
        el.style.top    = state.position.top    !== undefined ? state.position.top    + 'px' : 'auto';
        el.style.bottom = state.position.bottom !== undefined ? state.position.bottom + 'px' : 'auto';
    }

    function makeDraggable(el, onMove) {
        let startX, startY, startLeft, startTop, moved;
        el.addEventListener('mousedown', ds);
        el.addEventListener('touchstart', ds, { passive: false });
        function ds(e) {
            moved = false; state.isDragging = false;
            const t = e.touches ? e.touches[0] : e;
            startX = t.clientX; startY = t.clientY;
            const r = el.getBoundingClientRect(); startLeft = r.left; startTop = r.top;
            document.addEventListener('mousemove', drag);
            document.addEventListener('touchmove', drag, { passive: false });
            document.addEventListener('mouseup', de); document.addEventListener('touchend', de);
        }
        function drag(e) {
            e.preventDefault();
            const t = e.touches ? e.touches[0] : e;
            const dx = t.clientX - startX, dy = t.clientY - startY;
            if (Math.abs(dx) > 5 || Math.abs(dy) > 5) { moved = true; state.isDragging = true; }
            if (!moved) return;
            let nl = Math.max(0, Math.min(startLeft + dx, window.innerWidth  - el.offsetWidth));
            let nt = Math.max(0, Math.min(startTop  + dy, window.innerHeight - el.offsetHeight));
            const cx = nl + el.offsetWidth/2, cy = nt + el.offsetHeight/2;
            state.position = {};
            if (cx < window.innerWidth/2)  state.position.left   = nl; else state.position.right  = window.innerWidth  - nl - el.offsetWidth;
            if (cy < window.innerHeight/2) state.position.top    = nt; else state.position.bottom = window.innerHeight - nt - el.offsetHeight;
            applyPosition(el); if (onMove) onMove();
        }
        function de() {
            document.removeEventListener('mousemove', drag); document.removeEventListener('touchmove', drag);
            document.removeEventListener('mouseup', de);    document.removeEventListener('touchend', de);
            if (moved) { savePosition(); setTimeout(() => { state.isDragging = false; }, 10); }
        }
    }

    function positionModal(btn, modal) {
        const r = btn.getBoundingClientRect(), vw = window.innerWidth, vh = window.innerHeight;
        const cx = r.left + r.width/2, cy = r.top + r.height/2;
        modal.style.left = modal.style.right = modal.style.top = modal.style.bottom = 'auto';
        if (cx > vw/2) modal.style.right = (vw - r.left + 8) + 'px'; else modal.style.left = (r.right + 8) + 'px';
        if (cy > vh/2) modal.style.bottom = (vh - r.top  + 8) + 'px'; else modal.style.top  = (r.bottom + 8) + 'px';
        requestAnimationFrame(() => {
            const mr = modal.getBoundingClientRect();
            if (mr.left < 8) modal.style.left = '8px';
            if (mr.right  > vw - 8) { modal.style.left = 'auto'; modal.style.right  = '8px'; }
            if (mr.top  < 8) modal.style.top  = '8px';
            if (mr.bottom > vh - 8) { modal.style.top  = 'auto'; modal.style.bottom = '8px'; }
        });
    }

    function toggle() {
        state.isOpen = !state.isOpen;
        const btn = document.getElementById('chatbot-btn');
        const modal = document.getElementById('chatbot-modal');
        document.getElementById('chatbot-icon-chat').classList.toggle('hidden', state.isOpen);
        document.getElementById('chatbot-icon-x').classList.toggle('hidden', !state.isOpen);
        if (state.isOpen) {
            modal.style.display = 'flex';
            positionModal(btn, modal);
            if (state.messages.length === 0) addMessage("Hi! I'm your AI real estate assistant. How can I help you today?", 'bot');
            document.getElementById('chatbot-input').focus();
        } else {
            modal.style.display = 'none';
        }
    }

    function close() {
        state.isOpen = false;
        document.getElementById('chatbot-icon-chat').classList.remove('hidden');
        document.getElementById('chatbot-icon-x').classList.add('hidden');
        document.getElementById('chatbot-modal').style.display = 'none';
    }

    function addMessage(text, sender) {
        const container = document.getElementById('chatbot-messages');
        const div = document.createElement('div');
        div.className = sender === 'user' ? 'flex justify-end' : 'flex justify-start';
        div.innerHTML = '<div class="max-w-[85%] px-3 py-2 rounded-lg text-sm ' +
            (sender === 'user' ? 'text-white rounded-br-none' : 'bg-white text-gray-800 rounded-bl-none shadow-sm') +
            '" ' + (sender === 'user' ? 'style="background-color:var(--primary)"' : '') + '>' + esc(text) + '</div>';
        container.appendChild(div);
        state.messages.push({ text, sender, ts: Date.now() });
        saveConversation();
        setTimeout(() => { container.scrollTop = container.scrollHeight; }, 10);
    }

    function showTyping(show) {
        document.getElementById('chatbot-typing').classList.toggle('hidden', !show);
        document.getElementById('chatbot-send').disabled = show;
        document.getElementById('chatbot-input').disabled = show;
    }

    async function handleSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('chatbot-input');
        const msg = input.value.trim();
        if (!msg || state.isLoading) return;
        input.value = '';
        addMessage(msg, 'user');
        showTyping(true); state.isLoading = true;
        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF() },
                body: JSON.stringify({ message: msg, session_id: state.sessionId })
            });
            const data = await res.json();
            if (data.session_id) state.sessionId = data.session_id;
            addMessage(data.reply || 'Sorry, something went wrong.', 'bot');
        } catch(err) {
            addMessage("Sorry, I'm having trouble connecting. Please try again.", 'bot');
        } finally {
            state.isLoading = false; showTyping(false);
            document.getElementById('chatbot-input').focus();
        }
    }

    function saveConversation() {
        sessionStorage.setItem('chatbot_conv', JSON.stringify({ id: state.sessionId, msgs: state.messages }));
    }
    function loadConversation() {
        try {
            const data = JSON.parse(sessionStorage.getItem('chatbot_conv') || 'null');
            if (!data) return;
            state.sessionId = data.id;
            state.messages = data.msgs || [];
            const container = document.getElementById('chatbot-messages');
            state.messages.forEach(m => {
                const div = document.createElement('div');
                div.className = m.sender === 'user' ? 'flex justify-end' : 'flex justify-start';
                div.innerHTML = '<div class="max-w-[85%] px-3 py-2 rounded-lg text-sm ' +
                    (m.sender === 'user' ? 'text-white rounded-br-none' : 'bg-white text-gray-800 rounded-bl-none shadow-sm') +
                    '" ' + (m.sender === 'user' ? 'style="background-color:var(--primary)"' : '') + '>' + esc(m.text) + '</div>';
                container.appendChild(div);
            });
        } catch(e) {}
    }

    window.chatbotWidget = { open: toggle, close };
})();

(function() {
    const root = document.getElementById('contact-widget-root');
    if (!root) return;

    const CONTACT_URL = root.dataset.apiUrl || '';
    const PRIVACY_URL = root.dataset.privacyUrl || '';
    const STORAGE_KEY = 'contact_position';
    let state = { isOpen: false, propertyId: null, isDragging: false, position: null };

    document.addEventListener('DOMContentLoaded', init);

    function init() { loadPosition(); createWidget(); setupPropertyContext(); }

    function loadPosition() {
        try { const s = localStorage.getItem(STORAGE_KEY); if (s) state.position = JSON.parse(s); } catch(e) {}
        if (!state.position) state.position = { left: 16, bottom: 16 };
    }
    function savePosition() { localStorage.setItem(STORAGE_KEY, JSON.stringify(state.position)); }

    function createWidget() {
        root.innerHTML = `
<button id="contact-btn" class="fixed w-14 h-14 rounded-full shadow-xl flex items-center justify-center text-white z-50 cursor-grab active:cursor-grabbing select-none" style="background-color:var(--primary);left:16px;bottom:16px">
    <svg width="24" height="24" id="contact-icon-mail" class="w-6 h-6 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
    </svg>
    <svg width="24" height="24" id="contact-icon-x" class="w-6 h-6 pointer-events-none hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
    </svg>
</button>
<div id="contact-modal" class="fixed w-96 bg-white rounded-xl shadow-2xl z-50 hidden" style="max-width:calc(100vw - 2rem)">
    <div class="flex items-center justify-between p-4 border-b rounded-t-xl" style="background-color:var(--primary)">
        <div class="text-white">
            <div class="font-semibold">Contact Agent</div>
            <div class="text-xs opacity-90">We'll respond within 24 hours</div>
        </div>
        <button id="contact-close" class="text-white hover:bg-white/20 rounded p-1">
            <svg width="20" height="20" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="p-4">
        <form id="contact-form" class="space-y-3">
            <div><label for="contact-name" class="block text-sm font-medium text-gray-700 mb-1">Your Name *</label>
            <input type="text" id="contact-name" name="name" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-0"></div>
            <div><label for="contact-email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
            <input type="email" id="contact-email" name="email" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none"></div>
            <div><label for="contact-phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
            <input type="tel" id="contact-phone" name="phone" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none"></div>
            <div><label for="contact-message" class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
            <textarea id="contact-message" name="message" rows="3" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none resize-none"></textarea></div>
            <input type="hidden" id="contact-property-id" name="property_id" value="">
            <p class="text-xs text-gray-400" style="margin-top:-4px">By providing your phone number, you consent to receive calls or texts regarding your inquiry. <a href="" id="widget-privacy-link" class="underline hover:text-gray-800" target="_blank">Privacy Policy</a>. <input type="checkbox" id="widget-consent" name="consent" class="w-3.5 h-3.5 rounded border-gray-400" style="accent-color:var(--primary);vertical-align:-3px"> <label for="widget-consent" class="cursor-pointer underline">I agree</label> <span class="text-red-500">*</span></p>

            <div id="contact-error" class="hidden text-red-600 text-sm"></div>
            <div id="contact-success" class="hidden text-green-600 text-sm font-medium"></div>
            <button type="submit" id="contact-submit" disabled class="w-full py-2.5 rounded-lg text-white font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed transition-opacity" style="background-color:var(--primary)">Send Message</button>
        </form>
    </div>
</div>`;
        const btn = document.getElementById('contact-btn');
        const modal = document.getElementById('contact-modal');
        applyPosition(btn);
        makeDraggable(btn, () => { if (state.isOpen) positionModal(btn, modal); });
        btn.addEventListener('click', () => { if (!state.isDragging) toggle(); });
        document.getElementById('contact-close').addEventListener('click', close);
        document.getElementById('contact-form').addEventListener('submit', handleSubmit);
        wireConsentCheckbox();
        var pl = document.getElementById('widget-privacy-link');
        if (pl) pl.href = '${PRIVACY_URL}';
    }

    function applyPosition(el) {
        el.style.left   = state.position.left   !== undefined ? state.position.left   + 'px' : 'auto';
        el.style.right  = state.position.right  !== undefined ? state.position.right  + 'px' : 'auto';
        el.style.top    = state.position.top    !== undefined ? state.position.top    + 'px' : 'auto';
        el.style.bottom = state.position.bottom !== undefined ? state.position.bottom + 'px' : 'auto';
    }

    function makeDraggable(el, onMove) {
        let startX, startY, startLeft, startTop, moved;
        el.addEventListener('mousedown', ds);
        el.addEventListener('touchstart', ds, { passive: false });
        function ds(e) {
            moved = false; state.isDragging = false;
            const t = e.touches ? e.touches[0] : e;
            startX = t.clientX; startY = t.clientY;
            const r = el.getBoundingClientRect(); startLeft = r.left; startTop = r.top;
            document.addEventListener('mousemove', drag);
            document.addEventListener('touchmove', drag, { passive: false });
            document.addEventListener('mouseup', de);
            document.addEventListener('touchend', de);
        }
        function drag(e) {
            e.preventDefault();
            const t = e.touches ? e.touches[0] : e;
            const dx = t.clientX - startX, dy = t.clientY - startY;
            if (Math.abs(dx) > 5 || Math.abs(dy) > 5) { moved = true; state.isDragging = true; }
            if (!moved) return;
            let nl = Math.max(0, Math.min(startLeft + dx, window.innerWidth  - el.offsetWidth));
            let nt = Math.max(0, Math.min(startTop  + dy, window.innerHeight - el.offsetHeight));
            const mx = window.innerWidth / 2, my = window.innerHeight / 2;
            const cx = nl + el.offsetWidth / 2, cy = nt + el.offsetHeight / 2;
            state.position = {};
            if (cx < mx) state.position.left = nl; else state.position.right  = window.innerWidth  - nl - el.offsetWidth;
            if (cy < my) state.position.top  = nt; else state.position.bottom = window.innerHeight - nt - el.offsetHeight;
            applyPosition(el); if (onMove) onMove();
        }
        function de() {
            document.removeEventListener('mousemove', drag); document.removeEventListener('touchmove', drag);
            document.removeEventListener('mouseup', de);    document.removeEventListener('touchend', de);
            if (moved) { savePosition(); setTimeout(() => { state.isDragging = false; }, 10); }
        }
    }

    function positionModal(btn, modal) {
        const r = btn.getBoundingClientRect(), vw = window.innerWidth, vh = window.innerHeight;
        const cx = r.left + r.width / 2, cy = r.top + r.height / 2;
        modal.style.left = modal.style.right = modal.style.top = modal.style.bottom = 'auto';
        if (cx > vw / 2) modal.style.right = (vw - r.left + 8) + 'px'; else modal.style.left = (r.right + 8) + 'px';
        if (cy > vh / 2) modal.style.bottom = (vh - r.top  + 8) + 'px'; else modal.style.top  = (r.bottom + 8) + 'px';
        requestAnimationFrame(() => {
            const mr = modal.getBoundingClientRect();
            if (mr.left < 8) modal.style.left = '8px';
            if (mr.right  > vw - 8) { modal.style.left = 'auto'; modal.style.right  = '8px'; }
            if (mr.top  < 8) modal.style.top  = '8px';
            if (mr.bottom > vh - 8) { modal.style.top  = 'auto'; modal.style.bottom = '8px'; }
        });
    }

    function toggle() {
        state.isOpen = !state.isOpen;
        const btn = document.getElementById('contact-btn');
        const modal = document.getElementById('contact-modal');
        document.getElementById('contact-icon-mail').classList.toggle('hidden', state.isOpen);
        document.getElementById('contact-icon-x').classList.toggle('hidden', !state.isOpen);
        if (state.isOpen) { modal.classList.remove('hidden'); positionModal(btn, modal); document.getElementById('contact-name').focus(); }
        else modal.classList.add('hidden');
    }
    function close() {
        state.isOpen = false;
        document.getElementById('contact-icon-mail').classList.remove('hidden');
        document.getElementById('contact-icon-x').classList.add('hidden');
        document.getElementById('contact-modal').classList.add('hidden');
    }

    function setupPropertyContext() {
        const m = location.pathname.match(/\/property\/(\d+)/);
        if (m) {
            state.propertyId = m[1];
            document.getElementById('contact-property-id').value = m[1];
            const addr = document.querySelector('[data-property-address]')?.textContent;
            if (addr) document.getElementById('contact-message').value =
                `Hi, I'm interested in the property at ${addr}. I'd like to schedule a viewing.`;
        }
    }

    function wireConsentCheckbox() {
        const box = document.getElementById('widget-consent');
        const btn = document.getElementById('contact-submit');
        if (!box || !btn) return;
        box.addEventListener('change', () => { btn.disabled = !box.checked; });
    }

    async function handleSubmit(e) {
        e.preventDefault();
        const consentBox = document.getElementById('widget-consent');
        if (consentBox && !consentBox.checked) return;
        const submitBtn = document.getElementById('contact-submit');
        const errDiv = document.getElementById('contact-error');
        const okDiv  = document.getElementById('contact-success');
        errDiv.classList.add('hidden'); okDiv.classList.add('hidden');
        submitBtn.disabled = true; submitBtn.textContent = 'Sending...';
        const fd = new FormData(e.target);
        try {
            const res = await fetch(CONTACT_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
                body: JSON.stringify({ name: fd.get('name'), email: fd.get('email'), phone: fd.get('phone'), message: fd.get('message'), property_id: fd.get('property_id') || null })
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Failed to send');
            okDiv.textContent = "Message sent! We'll be in touch soon.";
            okDiv.classList.remove('hidden');
            e.target.reset();
            setTimeout(() => { close(); okDiv.classList.add('hidden'); }, 3000);
        } catch(err) {
            errDiv.textContent = err.message;
            errDiv.classList.remove('hidden');
        } finally { submitBtn.disabled = false; submitBtn.textContent = 'Send Message'; }
    }

    window.contactWidget = { open: toggle, close };
})();
