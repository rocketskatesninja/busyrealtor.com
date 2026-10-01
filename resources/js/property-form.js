// The listing form: tab state, the create-then-upload submit sequence, and the photo
// grid (drag to reorder, delete, promote the first photo to primary). Sortable is
// imported rather than reached for on window, so the view no longer has to load it
// separately and hope the ordering works out.

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

// Both card builders below used to carry their own copy of these two icons, inlined by
// Blade into a JS string. One copy each now.
const STAR_ICON = '<svg fill="currentColor" viewBox="0 0 20 20" class="w-3 h-3"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
const X_ICON = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
const DRAG_ICON = '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 6a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm8-16a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4z"/></svg>';

const CARD_CLASS = 'relative group rounded-xl overflow-hidden aspect-square bg-gray-100 cursor-grab active:cursor-grabbing select-none';
const SORTABLE_OPTS = { animation: 150, ghostClass: 'opacity-30', dragClass: 'shadow-xl' };

const form = () => document.getElementById('property-form');
const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

let cfg = {};
let pendingFiles = [];
let localSortable = null;

function grid() {
    return document.getElementById('image-grid');
}

// ─── Badge helpers ────────────────────────────────────────────────────────
function refreshMainBadge() {
    document.querySelectorAll('#image-grid > div').forEach(function (card, i) {
        const b = card.querySelector('.main-badge');
        if (b) b.style.display = i === 0 ? 'inline-flex' : 'none';
    });
}

// ─── Persist sort order to server (edit only) ─────────────────────────────
function persistOrder() {
    document.querySelectorAll('#image-grid > [data-id]').forEach(function (card, i) {
        const url = cfg.reorderUrlTpl.replace('__ID__', card.dataset.id);
        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json' }, body: JSON.stringify({ sort_order: i }) });
        if (i === 0) fetch(card.dataset.primaryUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf() } });
    });
}

// ─── File select handler — unified for both create and edit ──────────────
function handleFileSelect(files) {
    if (cfg.isEdit) {
        // Edit: upload immediately via AJAX
        Array.from(files).forEach(file => uploadImmediate(file));
    } else {
        // Create: store files locally, show preview cards, upload after form submit
        Array.from(files).forEach(file => {
            pendingFiles.push(file);
            addLocalPreviewCard(file, pendingFiles.length - 1);
        });
    }
    document.getElementById('images').value = '';
}

function addLocalPreviewCard(file, idx) {
    const g = grid();
    const hdr = document.getElementById('photos-header');
    if (!g) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        const card = document.createElement('div');
        card.className = CARD_CLASS;
        card.dataset.localIdx = idx;
        card.innerHTML =
            '<img src="' + e.target.result + '" class="w-full h-full object-cover pointer-events-none">' +
            '<span class="main-badge absolute top-1.5 left-1.5 items-center gap-1 bg-emerald-500 text-white text-xs px-1.5 py-0.5 rounded-md font-semibold shadow" style="display:none">' +
            STAR_ICON +
            'Main</span>' +
            '<div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">' +
            '<button type="button" data-action="remove-preview" class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg transition-colors pointer-events-auto" title="Remove">' +
            X_ICON + '</button></div>' +
            '<div class="absolute bottom-1 right-1 text-white/70 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">' +
            DRAG_ICON + '</div>';
        g.appendChild(card);
        refreshMainBadge();
        if (hdr) hdr.style.removeProperty('display');
        // Init sortable on first card
        if (!localSortable) {
            localSortable = Sortable.create(g, {
                ...SORTABLE_OPTS,
                onEnd() { refreshMainBadge(); reindexPendingFiles(); },
            });
        }
    };
    reader.readAsDataURL(file);
}

function removeLocalPreview(btn) {
    const card = btn.closest('[data-local-idx]');
    const idx = parseInt(card.dataset.localIdx);
    pendingFiles[idx] = null;
    card.remove();
    refreshMainBadge();
    if (!document.querySelectorAll('#image-grid [data-local-idx]').length) {
        const hdr = document.getElementById('photos-header');
        if (hdr) hdr.style.setProperty('display', 'none', 'important');
    }
}

function reindexPendingFiles() {
    const cards = document.querySelectorAll('#image-grid [data-local-idx]');
    const reordered = [];
    cards.forEach(card => {
        const idx = parseInt(card.dataset.localIdx);
        if (pendingFiles[idx]) reordered.push(pendingFiles[idx]);
    });
    pendingFiles = reordered;
    cards.forEach((card, i) => card.dataset.localIdx = i);
}

// ─── EDIT: upload a single file immediately via AJAX ─────────────────────
function uploadImmediate(file) {
    const label = document.getElementById('upload-label');
    if (label) label.textContent = 'Uploading…';

    const fd = new FormData();
    fd.append('image', file);
    fd.append('property_id', cfg.propertyId);

    fetch(cfg.uploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf() }, body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.id) {
                const card = buildGridCard(
                    data.url,
                    data.id,
                    cfg.deleteUrlTpl.replace('__ID__', data.id),
                    cfg.primaryUrlTpl.replace('__ID__', data.id),
                );
                grid().appendChild(card);
                refreshMainBadge();
                persistOrder();
            }
        })
        .finally(() => {
            if (label) label.innerHTML = 'Click to select photos <span class="text-gray-400">(JPG, PNG, WebP — max 10MB each)</span>';
        });
}

// ─── Build a grid card element (used after AJAX upload on edit) ───────────
function buildGridCard(url, id, deleteUrl, primaryUrl) {
    const card = document.createElement('div');
    card.className = CARD_CLASS;
    card.dataset.id = id;
    card.dataset.deleteUrl = deleteUrl;
    card.dataset.primaryUrl = primaryUrl;
    card.innerHTML = `
        <img src="${url}" class="w-full h-full object-cover pointer-events-none">
        <span class="main-badge absolute top-1.5 left-1.5 items-center gap-1 bg-emerald-500 text-white text-xs px-1.5 py-0.5 rounded-md font-semibold shadow" style="display:none">
            ${STAR_ICON}
            Main
        </span>
        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
            <button type="button" data-action="delete-image" class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg transition-colors pointer-events-auto" title="Delete photo">
                ${X_ICON}
            </button>
        </div>
        <div class="absolute bottom-1 right-1 text-white/70 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
            ${DRAG_ICON}
        </div>`;
    return card;
}

// ─── Delete a saved photo (edit only) ────────────────────────────────────
function deleteImage(btn) {
    if (!confirm('Delete this photo?')) return;
    const card = btn.closest('[data-id]');
    fetch(card.dataset.deleteUrl, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf() } })
        .then(r => {
            if (r.ok) {
                card.remove();
                refreshMainBadge();
                persistOrder();
            }
        });
}

function wire() {
    const f = form();
    if (!f) return;

    try {
        cfg = JSON.parse(f.dataset.config || '{}');
    } catch (e) {
        cfg = {};
    }

    const picker = document.getElementById('images');
    if (picker) picker.addEventListener('change', () => handleFileSelect(picker.files));

    // One delegated listener covers the cards Blade rendered and the ones built here,
    // so neither builder needs an onclick attribute.
    const g = grid();
    if (g) {
        g.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            if (btn.dataset.action === 'delete-image') deleteImage(btn);
            if (btn.dataset.action === 'remove-preview') removeLocalPreview(btn);
        });
    }
}

document.addEventListener('alpine:init', () => {
    Alpine.data('propertyForm', () => ({
        activeTab: 'basic',

        _sortableReady: false,
        init() {
            wire();

            this.$watch('activeTab', (val) => {
                if (val === 'media' && !this._sortableReady) {
                    this._sortableReady = true;
                    this.$nextTick(() => {
                        // Edit only: the saved grid exists up front, so it can be made
                        // sortable now. On create the grid starts empty and
                        // addLocalPreviewCard() wires it when the first card lands.
                        if (!cfg.isEdit) return;
                        const g = grid();
                        if (!g) return;
                        refreshMainBadge();
                        Sortable.create(g, {
                            ...SORTABLE_OPTS,
                            onEnd() { refreshMainBadge(); persistOrder(); },
                        });
                    });
                }
            });
        },
        async submitCreate(e) {
            if (cfg.isEdit) return true;
            e.preventDefault();
            const f = this.$el.closest('form');
            const btn = f.querySelector('button[type=submit]');
            const origText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Saving...';
            try {
                // 1. Submit form data to create property
                const fd = new FormData(f);
                // Remove the file input data (we'll upload via AJAX)
                fd.delete('images[]');
                const res = await fetch(f.action, {
                    method: 'POST',
                    body: fd,
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (!res.ok) {
                    // Validation errors — reload with errors
                    if (data.errors) {
                        let msg = Object.values(data.errors).flat().join('\n');
                        alert(msg);
                    }
                    btn.disabled = false;
                    btn.innerHTML = origText;
                    return;
                }
                // 2. Upload pending photos
                const propertyId = data.id;
                const validFiles = pendingFiles.filter(file => file !== null);
                for (const file of validFiles) {
                    const imgFd = new FormData();
                    imgFd.append('image', file);
                    imgFd.append('property_id', propertyId);
                    await fetch(cfg.uploadUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
                        body: imgFd,
                    });
                }
                // 3. Redirect to properties index
                window.location.href = data.redirect || f.action.replace('/store', '');
            } catch (err) {
                alert('Error creating property: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = origText;
            }
        },
    }));
});
