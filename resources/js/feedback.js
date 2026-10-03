// The feedback form's screenshot picker: local previews, drag to reorder, and rebuilding
// the FileList in that order just before submit.

import Sortable from 'sortablejs';

const X_ICON = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
const DRAG_ICON = '<svg width="16" height="16" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 6a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm8-16a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4z"/></svg>';

let sortable = null;

function addPreview(file) {
    const grid = document.getElementById('fb-preview-grid');
    if (!grid) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        const card = document.createElement('div');
        card.className = 'relative group rounded-xl overflow-hidden aspect-square bg-gray-100 cursor-grab active:cursor-grabbing select-none';
        card.dataset.preview = '1';
        card._file = file;
        card.innerHTML = `
            <img src="${e.target.result}" class="w-full h-full object-cover pointer-events-none">
            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                <button type="button" data-fb-remove class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg transition-colors pointer-events-auto" title="Remove">
                    ${X_ICON}
                </button>
            </div>
            <div class="absolute bottom-1 right-1 text-white/70 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                ${DRAG_ICON}
            </div>`;
        grid.appendChild(card);

        const hdr = document.getElementById('fb-preview-header');
        if (hdr) hdr.style.removeProperty('display');

        if (!sortable) {
            sortable = Sortable.create(grid, { animation: 150, ghostClass: 'opacity-30', dragClass: 'shadow-xl' });
        }
    };
    reader.readAsDataURL(file);
}

const picker = document.getElementById('screenshots');
if (picker) {
    picker.addEventListener('change', () => {
        Array.from(picker.files).forEach(addPreview);
        picker.value = '';
    });
}

document.addEventListener('click', (e) => {
    const btn = e.target.closest?.('[data-fb-remove]');
    if (!btn) return;

    btn.closest('[data-preview]')?.remove();

    const hdr = document.getElementById('fb-preview-header');
    if (hdr && !document.querySelectorAll('#fb-preview-grid [data-preview]').length) {
        hdr.style.setProperty('display', 'none', 'important');
    }
});

// The input keeps the files in the order they were chosen; the grid is the order the
// person actually wants. Rebuild the FileList from the grid before the form goes.
document.getElementById('feedback-form')?.addEventListener('submit', () => {
    const grid = document.getElementById('fb-preview-grid');
    const input = document.getElementById('screenshots');
    if (!grid || !input) return;

    const dt = new DataTransfer();
    grid.querySelectorAll('[data-preview]').forEach((card) => {
        if (card._file) dt.items.add(card._file);
    });
    input.files = dt.files;
});
