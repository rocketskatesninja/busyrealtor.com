// The super-admin "Send Test Email" button.

const TEST_MAIL_URL = document.getElementById('super-test-mail-btn')?.dataset.url;

// Super-admin "Send Test Email" — POSTs to /super-admin/api/test-mail and
// surfaces success or the raw SMTP error inline. Doesn't pre-save the
// form (different from the per-tenant button) — operator should hit
// "Save Settings" first if they edited any SMTP fields.
document.getElementById('super-test-mail-btn')?.addEventListener('click', async () => {
    const to = prompt('Send test email to:');
    if (!to) return;

    const btn = document.getElementById('super-test-mail-btn');
    const out = document.getElementById('super-test-mail-result');
    btn.disabled = true;
    btn.textContent = 'Sending...';
    out.textContent = '';
    out.className   = 'text-xs text-gray-400';

    try {
        const resp = await fetch(TEST_MAIL_URL, {
            method: 'POST',
            headers: {
                'Content-Type':       'application/json',
                'Accept':             'application/json',
                'X-CSRF-TOKEN':       document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With':   'XMLHttpRequest',
            },
            body: JSON.stringify({ to }),
        });
        const data = await resp.json();
        out.textContent = data.message || (resp.ok ? 'Sent.' : 'Failed.');
        out.className   = 'text-xs ' + (data.success ? 'text-green-400' : 'text-red-400');
    } catch (e) {
        out.textContent = 'Network error: ' + e.message;
        out.className   = 'text-xs text-red-400';
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Send Test Email';
    }
});
