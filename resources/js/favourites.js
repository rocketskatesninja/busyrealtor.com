// Visitor favourites, held in localStorage per tenant.
//
// This existed twice against the same storage key: the gallery kept the full
// implementation and the property page a hand-rolled copy that read the same array to
// decide whether to show one badge. One copy now, with the page saying which job it
// wants by the elements it renders.

const root = document.querySelector('[data-fav-key]');
const KEY = root?.dataset.favKey;

function read() {
    try {
        return JSON.parse(localStorage.getItem(KEY) || '[]');
    } catch (e) {
        return [];
    }
}

function write(favs) {
    try {
        localStorage.setItem(KEY, JSON.stringify(favs));
    } catch (e) {
        // Private browsing, or storage full. The page still works, the choice just
        // does not outlive it.
    }
}

// ── Gallery: mark the favourites and float them to the front of the grid ──────
function applyToGrid() {
    const grid = document.getElementById('property-grid');
    if (!grid) return;

    const favs = read();
    grid.querySelectorAll('[data-property-id]').forEach((card) => {
        const id = parseInt(card.dataset.propertyId);
        const isFav = favs.includes(id);
        const outline = document.getElementById('fav-outline-' + id);
        const filled = document.getElementById('fav-filled-' + id);
        if (outline) outline.style.display = isFav ? 'none' : '';
        if (filled) filled.style.display = isFav ? '' : 'none';
    });

    const cards = Array.from(grid.querySelectorAll('[data-property-id]'));
    const favCards = cards.filter(c => favs.includes(parseInt(c.dataset.propertyId)));
    const rest = cards.filter(c => !favs.includes(parseInt(c.dataset.propertyId)));
    [...favCards, ...rest].forEach(c => grid.appendChild(c));

    const hint = document.getElementById('favs-hint');
    if (hint) hint.style.display = favCards.length ? '' : 'none';
}

document.addEventListener('click', (e) => {
    const btn = e.target.closest?.('[data-fav-toggle]');
    if (!btn || !KEY) return;

    e.preventDefault();
    e.stopPropagation();

    const id = parseInt(btn.dataset.favToggle);
    const favs = read();
    const idx = favs.indexOf(id);
    if (idx === -1) favs.push(id); else favs.splice(idx, 1);
    write(favs);
    applyToGrid();
});

// ── Property page: one badge, shown when this listing is a favourite ──────────
function applyToBadge() {
    const badge = document.getElementById('fav-badge');
    if (!badge || !root?.dataset.propertyId) return;

    if (read().includes(parseInt(root.dataset.propertyId))) {
        badge.style.display = '';
    }
}

if (KEY) {
    applyToGrid();
    applyToBadge();
}
