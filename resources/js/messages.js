// The messages list: star, change status, delete. One endpoint, three actions, and
// previously one global called from an onclick on every row.

const root = () => document.getElementById('messages-list');

function config() {
    try {
        return JSON.parse(root()?.dataset.config || '{}');
    } catch (e) {
        return {};
    }
}

async function act(action, id, value = null) {
    const cfg = config();

    await fetch(cfg.actionUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ action, id, status: value }),
    });

    if (action === 'delete') {
        window.location = cfg.indexUrl;
    } else {
        location.reload();
    }
}

document.addEventListener('click', (e) => {
    const btn = e.target.closest?.('button[data-msg-action]');
    if (!btn) return;

    // Delete asks first. The attribute means the same thing it does on a form in ui.js;
    // that listener is on submit, so the two never see each other's elements.
    if (btn.dataset.confirm && !confirm(btn.dataset.confirm)) return;

    act(btn.dataset.msgAction, btn.dataset.msgId);
});

document.addEventListener('change', (e) => {
    const select = e.target.closest?.('select[data-msg-action]');
    if (!select) return;

    act(select.dataset.msgAction, select.dataset.msgId, select.value);
});
