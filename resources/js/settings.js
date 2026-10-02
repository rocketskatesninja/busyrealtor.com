// The settings screen: the live title preview, the test-email button, backup/restore
// with its console, homepage section ordering, the submit-time serialisation of the
// Alpine-managed data, dirty tracking, and mobile swipe navigation.

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

const form = () => document.getElementById('settings-form');
const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

let cfg = {};

function configFor(el) {
    try {
        return JSON.parse(el?.dataset.config || '{}');
    } catch (e) {
        return {};
    }
}

// ── Title Preview ─────────────────────────────────────────────────────────────
const _fontSizeMap = { 'xl': '1.25rem', '2xl': '1.5rem', '3xl': '1.875rem', '4xl': '2.25rem' };
const _trackingMap = { 'tight': '-0.05em', 'wide': '0.05em', 'normal': 'normal' };

function updateTitlePreview() {
    const defaults = cfg.titleDefaults || {};
    const previews = [document.getElementById('title_preview_light'), document.getElementById('title_preview_dark')].filter(Boolean);
    if (!previews.length) return;
    const titleText = document.querySelector('input[name="site_title"]')?.value || 'Your Site Title';
    const font      = document.querySelector('input[name="title_font"]')?.value  || 'Poppins';
    const size      = document.querySelector('select[name="site_title_font_size"]')?.value     || '3xl';
    const weight    = document.querySelector('select[name="site_title_font_weight"]')?.value   || '800';
    const tracking  = document.querySelector('select[name="site_title_letter_spacing"]')?.value || 'normal';
    const colorType = document.querySelector('select[name="title_color_type"]')?.value         || defaults.title_color_type;
    previews.forEach(el => {
        el.textContent           = titleText;
        el.style.fontFamily      = `'${font}', sans-serif`;
        el.style.fontSize        = _fontSizeMap[size] || '1.875rem';
        el.style.fontWeight      = weight;
        el.style.letterSpacing   = _trackingMap[tracking] || 'normal';
        if (colorType === 'gradient') {
            const start = document.querySelector('input[name="title_gradient_start"]')?.value || defaults.title_gradient_start;
            const via   = document.querySelector('input[name="title_gradient_via"]')?.value   || defaults.title_gradient_via;
            const end   = document.querySelector('input[name="title_gradient_end"]')?.value   || defaults.title_gradient_end;
            el.style.background           = `linear-gradient(to right, ${start}, ${via}, ${end})`;
            el.style.webkitBackgroundClip = 'text';
            el.style.webkitTextFillColor  = 'transparent';
            el.style.backgroundClip       = 'text';
            el.style.color                = 'transparent';
        } else {
            const solid = document.querySelector('input[name="title_color_solid"]')?.value || defaults.title_color_solid;
            el.style.background           = 'none';
            el.style.webkitBackgroundClip = 'unset';
            el.style.webkitTextFillColor  = 'unset';
            el.style.backgroundClip       = 'unset';
            el.style.color                = solid;
        }
    });
}

// ── Backup & Restore ─────────────────────────────────────────────────────────
const _spin = '<svg width="16" height="16" class="w-4 h-4 animate-spin inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>';

function dclog(msg, type) {
    const el   = document.getElementById('data-console');
    if (!el) return;
    const dark = document.documentElement.classList.contains('dark');
    const ts   = new Date().toTimeString().slice(0, 8);
    const tsColor = dark ? '#4b5563' : '#9ca3af';
    const cols = dark
        ? { info: '#94a3b8', success: '#4ade80', error: '#f87171', warn: '#fbbf24' }
        : { info: '#4b5563', success: '#16a34a', error: '#dc2626', warn: '#d97706' };
    const col = cols[type || 'info'];
    const pre = { success: '✓', error: '✗', warn: '⚠', info: '›' }[type || 'info'];
    const d = document.createElement('div');
    d.style.marginBottom = '1px';
    d.innerHTML = `<span style="color:${tsColor};user-select:none">[${ts}]</span> <span style="color:${col}">${pre} ${msg}</span>`;
    el.appendChild(d);
    el.scrollTop = el.scrollHeight;
}

function clearDataConsole() {
    const el = document.getElementById('data-console');
    if (el) el.innerHTML = '<div style="color:#9ca3af">— Cleared. —</div>';
}

function updateRestoreFile(input) {
    const el = document.getElementById('restore-filename');
    if (el) el.textContent = input.files[0] ? input.files[0].name : 'No file chosen';
}

async function doBackup() {
    const btn = document.getElementById('backup-btn');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = _spin + ' Creating...';
    clearDataConsole();
    dclog('Starting backup...');
    try {
        dclog('Requesting archive from server...');
        const r = await fetch(cfg.backupUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf() } });
        if (!r.ok) throw new Error('Server error: ' + r.status);
        const blob = await r.blob();
        const kb   = (blob.size / 1024).toFixed(1);
        dclog('Archive ready — ' + kb + ' KB');
        const date     = new Date().toISOString().slice(0, 10);
        const filename = 'backup-' + date + '.zip';
        const url      = URL.createObjectURL(blob);
        const a        = document.createElement('a');
        a.href = url; a.download = filename;
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        URL.revokeObjectURL(url);
        dclog('Download started: ' + filename, 'success');
        dclog('Backup complete.', 'success');
    } catch (e) {
        dclog('Backup failed: ' + e.message, 'error');
    }
    btn.disabled = false;
    btn.innerHTML = orig;
}

async function doRestore() {
    const fileInput = document.getElementById('restore-file');
    const btn       = document.getElementById('restore-btn');
    if (!fileInput.files[0]) { dclog('No file selected — choose a .zip backup first.', 'warn'); return; }
    const file = fileInput.files[0];
    const orig = btn.innerHTML;
    btn.disabled  = true;
    btn.innerHTML = _spin + ' Restoring...';
    clearDataConsole();
    dclog('File: ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)');
    const fd = new FormData();
    fd.append('backup', file);
    fd.append('_token', csrf());
    // Lock the settings save button — the form on screen still shows the
    // pre-restore values; saving it would overwrite the restored data.
    const saveBtn = document.getElementById('settings-save-btn');
    if (saveBtn) saveBtn.disabled = true;
    try {
        dclog('Uploading to server...');
        const r = await fetch(cfg.restoreUrl, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
        dclog('Processing archive...');
        const d = await r.json();
        if (!r.ok || !d.success) throw new Error(d.message || 'Restore failed');
        dclog('Properties restored: ' + d.properties, 'success');
        if (d.images)       dclog('Property images restored: ' + d.images, 'success');
        if (d.staff)        dclog('Staff members restored: ' + d.staff, 'success');
        if (d.appointments) dclog('Appointments restored: ' + d.appointments, 'success');
        if (d.messages)     dclog('Messages restored: ' + d.messages, 'success');
        if (d.legal_pages)  dclog('Legal pages restored: ' + d.legal_pages, 'success');
        if (d.settings)     dclog('Site settings restored.', 'success');
        if (d.files)        dclog('Image files restored: ' + d.files, 'success');
        dclog('Restore complete — reloading page to show restored values...', 'success');
        fileInput.value = '';
        const fn = document.getElementById('restore-filename');
        if (fn) fn.textContent = 'No file chosen';
        setTimeout(() => location.reload(), 1500);
        return; // keep the spinner visible until reload
    } catch (e) {
        dclog('Restore failed: ' + e.message, 'error');
        if (saveBtn) saveBtn.disabled = false; // restore aborted — saving is safe again
    }
    btn.disabled  = false;
    btn.innerHTML = orig;
}

// ── Test email ───────────────────────────────────────────────────────────────
async function sendTestEmail() {
    const email = prompt('Send test to email:');
    if (!email) return;
    const btn = document.getElementById('test-email-btn');
    const res = document.getElementById('test-email-result');
    btn.textContent = 'Saving & sending...';
    // Grab current SMTP values from form so they get saved before test
    const f = form();
    const smtpData = {
        to: email,
        smtp_host: f?.querySelector('[name="smtp_host"]')?.value || '',
        smtp_port: f?.querySelector('[name="smtp_port"]')?.value || '',
        smtp_encryption: f?.querySelector('[name="smtp_encryption"]')?.value || '',
        smtp_username: f?.querySelector('[name="smtp_username"]')?.value || '',
        smtp_password: f?.querySelector('[name="smtp_password"]')?.value || '',
        smtp_from_email: f?.querySelector('[name="smtp_from_email"]')?.value || '',
        smtp_from_name: f?.querySelector('[name="smtp_from_name"]')?.value || '',
    };
    try {
        const r = await fetch(cfg.testEmailUrl, {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify(smtpData),
        });
        const d = await r.json();
        res.textContent = d.message;
        res.className = 'text-sm ml-3 ' + (d.success ? 'text-green-600' : 'text-red-600');
    } catch (e) { res.textContent = 'Failed'; res.className = 'text-sm ml-3 text-red-600'; }
    btn.textContent = 'Send Test Email';
}

// ── Homepage section ordering ─────────────────────────────────────────────────
function wireSectionOrdering() {
    const container = document.getElementById('sections-container');
    if (!container) return;

    function updateSectionsData() {
        const sections = [];
        container.querySelectorAll('.section-item').forEach(function (item, i) {
            const key    = item.dataset.section;
            const toggle = item.querySelector('.section-toggle');
            const locked = item.dataset.locked === '1';
            sections.push({ key: key, enabled: locked ? true : (toggle ? toggle.checked : true), order: i, locked: locked });
        });
        const input = document.getElementById('hp_sections_input');
        if (input) input.value = JSON.stringify(sections);
    }

    updateSectionsData();
    container.addEventListener('change', function (e) {
        if (e.target.classList.contains('section-toggle')) updateSectionsData();
    });
    if (window.innerWidth >= 640) {
        new Sortable(container, {
            animation: 150,
            ghostClass: 'opacity-50',
            handle: '.drag-handle',
            filter: '.locked-section',
            preventOnFilter: false,
            onEnd: updateSectionsData,
        });
    }
}

// ── Form submit: sync Alpine data to hidden inputs ──
function wireForm() {
    const f = form();
    const btn = document.getElementById('settings-save-btn');
    if (!f) return;

    f.addEventListener('submit', function () {
        const tabInput = f.querySelector('input[name="tab"]');
        if (tabInput && window.Alpine) {
            try { tabInput.value = Alpine.$data(f.closest('[x-data]')).activeTab; } catch (e) {}
        }
        // By id rather than by matching the x-data attribute's text, which broke on any
        // reformat and on the component being registered by name instead of called.
        const hpDiv = document.getElementById('hp-sections');
        if (hpDiv && window.Alpine) {
            try {
                const data = Alpine.$data(hpDiv);
                const map = { 'features_items': 'features', 'services_items': 'services', 'testimonials_items': 'testimonials', 'stats_items': 'stats', 'faq_items': 'faq' };
                Object.entries(map).forEach(function (pair) {
                    const el = f.querySelector('[name="' + pair[0] + '"]');
                    if (el && data[pair[1]] !== undefined) el.value = JSON.stringify(data[pair[1]]);
                });
            } catch (e) {}
        }
        if (btn) btn.disabled = true;
    }, true);

    // ── Dirty tracking ──
    if (btn) {
        const markDirty = () => { btn.disabled = false; };
        f.addEventListener('input', markDirty, true);
        f.addEventListener('change', markDirty, true);
        window.addEventListener('beforeunload', function (e) {
            if (!btn.disabled) { e.preventDefault(); e.returnValue = ''; }
        });
    }
}

// ── Mobile swipe navigation ──────────────────────────────────────────────────
function wireSwipeNavigation() {
    if (window.innerWidth >= 768) return; // desktop only uses sidebar

    const tabOrder = cfg.tabOrder || [];
    // Get the Alpine root and track active tab reactively
    const alpineRoot = document.querySelector('[x-data]');
    function getActiveTab() {
        try { return window.Alpine ? Alpine.$data(alpineRoot).activeTab : cfg.activeTab; }
        catch (e) { return cfg.activeTab; }
    }
    function setActiveTab(tab) {
        try {
            if (window.Alpine) Alpine.$data(alpineRoot).activeTab = tab;
        } catch (e) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.location.href = url.toString();
        }
    }

    // Scroll active tab into view on load
    const strip = document.querySelector('.md\\:hidden .flex.overflow-x-auto');
    if (strip) {
        const active = strip.querySelector(`[data-tab="${cfg.activeTab}"]`);
        if (active) {
            strip.scrollLeft = active.offsetLeft - strip.offsetWidth / 2 + active.offsetWidth / 2;
        }
    }

    // Swipe detection on the content area
    const content = document.querySelector('.flex-1 form') || document.body;
    let startX, startY, startTime;

    content.addEventListener('touchstart', function (e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        startTime = Date.now();
    }, { passive: true });

    content.addEventListener('touchend', function (e) {
        if (!startX) return;
        const dx = e.changedTouches[0].clientX - startX;
        const dy = e.changedTouches[0].clientY - startY;
        const dt = Date.now() - startTime;

        // Must be fast (<400ms), mostly horizontal (2:1 ratio), and >50px
        if (dt > 400 || Math.abs(dx) < 50 || Math.abs(dy) > Math.abs(dx) * 0.6) return;

        const currentIdx = tabOrder.indexOf(getActiveTab());
        const nextIdx = dx < 0 ? currentIdx + 1 : currentIdx - 1;
        if (nextIdx < 0 || nextIdx >= tabOrder.length) return;

        const nextTab = tabOrder[nextIdx];
        setActiveTab(nextTab);

        // Scroll strip to new active tab
        if (strip) {
            const newActive = strip.querySelector(`[data-tab="${nextTab}"]`);
            if (newActive) strip.scrollLeft = newActive.offsetLeft - strip.offsetWidth / 2 + newActive.offsetWidth / 2;
        }
    }, { passive: true });
}

// ── Small field mirrors that used to be on* attributes ───────────────────────
function wireFieldMirrors() {
    const email = document.getElementById('f-email');
    const warning = document.getElementById('email-warning');
    if (email && warning) {
        email.addEventListener('input', () => {
            warning.style.display = email.value !== email.defaultValue ? 'flex' : 'none';
        });
    }

    const hex = document.getElementById('primary_color_hex');
    const picker = document.querySelector('[name=primary_color]');
    if (hex && picker) {
        hex.addEventListener('input', () => { picker.value = hex.value; });
    }

    const colorType = document.getElementById('title_color_type');
    if (colorType) {
        const applyColorType = () => {
            const gradient = document.getElementById('gradient-fields');
            const solid = document.getElementById('solid-color-field');
            if (gradient) gradient.style.display = colorType.value === 'gradient' ? 'flex' : 'none';
            if (solid) solid.style.display = colorType.value === 'solid' ? 'flex' : 'none';
        };
        colorType.addEventListener('change', applyColorType);
    }

    const opacity = document.querySelector('[name=hero_fx_overlay_opacity]');
    const opacityLabel = document.getElementById('overlay-opacity-val');
    if (opacity && opacityLabel) {
        opacity.addEventListener('input', () => { opacityLabel.textContent = opacity.value; });
    }

    // The settings form wraps this button, and a form cannot be nested inside another,
    // so the POST is still made by building one and submitting it.
    document.querySelector('[data-action="disconnect-google-calendar"]')?.addEventListener('click', () => {
        if (!confirm('Disconnect Google Calendar? Future confirmed appointments will no longer be synced.')) return;
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = cfg.gcalDisconnectUrl;
        const t = document.createElement('input');
        t.type = 'hidden';
        t.name = '_token';
        t.value = csrf();
        f.appendChild(t);
        document.body.appendChild(f);
        f.submit();
    });
}

function wire() {
    const f = form();
    if (!f) return;

    cfg = configFor(f);

    ['site_title', 'title_gradient_start', 'title_gradient_via', 'title_gradient_end', 'title_color_solid']
        .forEach(n => document.querySelector(`[name="${n}"]`)?.addEventListener('input', updateTitlePreview));
    ['site_title_font_size', 'site_title_font_weight', 'site_title_letter_spacing', 'title_color_type']
        .forEach(n => document.querySelector(`[name="${n}"]`)?.addEventListener('change', updateTitlePreview));
    // The font picker writes its choice through an Alpine :value binding, which sets the
    // property without firing an input event, so it announces the change instead.
    document.addEventListener('title-preview-changed', updateTitlePreview);

    document.getElementById('test-email-btn')?.addEventListener('click', sendTestEmail);
    document.getElementById('backup-btn')?.addEventListener('click', doBackup);
    document.getElementById('restore-btn')?.addEventListener('click', doRestore);
    document.getElementById('data-console-clear')?.addEventListener('click', clearDataConsole);
    const restoreFile = document.getElementById('restore-file');
    restoreFile?.addEventListener('change', () => updateRestoreFile(restoreFile));

    wireFieldMirrors();
    wireSwipeNavigation();

    // These two stayed on DOMContentLoaded, where they were before. app.js registers its
    // own DOMContentLoaded listener first, so Alpine has started by the time these run --
    // which matters for the dirty tracking, since attaching those listeners ahead of
    // Alpine's own initialisation would let it enable Save on a page nobody has touched.
    document.addEventListener('DOMContentLoaded', () => {
        wireSectionOrdering();
        wireForm();
    });
}

document.addEventListener('alpine:init', () => {
    Alpine.data('hpSectionData', () => ({
        expandedSection: null,
        ...(configFor(document.getElementById('settings-form')).homepageSections || {}),
    }));

    Alpine.data('dashGroup', (id) => ({
        allChecked: true,
        init() {
            this.$nextTick(() => {
                const boxes = this.$refs.group.querySelectorAll('input[type="checkbox"]');
                this.allChecked = [...boxes].every(b => b.checked);
            });
        },
        toggleAll() {
            const boxes = this.$refs.group.querySelectorAll('input[type="checkbox"]');
            const target = !this.allChecked;
            boxes.forEach(b => { b.checked = target; });
            this.allChecked = target;
        },
    }));
});

wire();
