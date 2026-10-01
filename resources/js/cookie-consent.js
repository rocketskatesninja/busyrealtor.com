/*
 | The cookie banner, for the public tenant sites and the marketing site.
 |
 | This existed twice — once in each layout — and the two copies had drifted, which is the
 | usual reason to find out they existed twice:
 |
 |   - the tenant copy called loadGA() when consent was granted and the marketing copy did
 |     not. That one is not a bug: the marketing site has no analytics at all. The call stays
 |     guarded by a typeof check, which is what makes one file correct on both.
 |   - the tenant copy's openCookiePrefs() checked the banner existed and then called
 |     scrollIntoView() outside that check, so it threw when the banner was absent. The
 |     marketing copy had the call inside the guard. Fixed here, the marketing way.
 |
 | The onclick attributes are gone, replaced by data attributes and one delegated listener.
 | That is the half of this work that matters for the CSP: inline handlers are what
 | 'unsafe-inline' exists to permit.
 */
const KEY = 'cookie_consent';

// localStorage throws in a locked-down browser rather than returning null. Inline, that
// killed one script block; in a module it would kill the bundle, so it is contained here.
function readConsent() {
    try {
        return localStorage.getItem(KEY);
    } catch {
        return null;
    }
}

function writeConsent(value) {
    try {
        localStorage.setItem(KEY, value);
    } catch {
        // A visitor who cannot store the choice is asked again next time, which is the
        // honest outcome — better than pretending it was saved.
    }
}

const banner = () => document.getElementById('cookie-banner');

function updatePrefsLink() {
    const link = document.getElementById('cookie-prefs-link');
    if (!link) return;

    const consent = readConsent();
    link.textContent = consent === 'true' ? 'Cookie Preferences ✓'
        : consent === 'false' ? 'Cookie Preferences ✕'
        : 'Cookie Preferences';
}

function setConsent(value) {
    writeConsent(value);

    const el = banner();
    if (el) el.style.display = 'none';

    // Defined by the tenant layout only; the marketing site has no analytics.
    if (value === 'true' && typeof window.loadGA === 'function') window.loadGA();

    updatePrefsLink();
}

function openPrefs() {
    const el = banner();
    if (!el) return;

    el.style.display = '';
    el.scrollIntoView({ behavior: 'smooth', block: 'end' });
}

// First visit: the banner is rendered hidden and revealed here.
if (!readConsent()) {
    const el = banner();
    if (el) el.style.display = '';
}

document.addEventListener('DOMContentLoaded', updatePrefsLink);

document.addEventListener('click', (event) => {
    const choice = event.target.closest('[data-cookie-consent]');
    if (choice) {
        setConsent(choice.dataset.cookieConsent);
        return;
    }

    if (event.target.closest('[data-cookie-prefs]')) {
        event.preventDefault();
        openPrefs();
    }
});
