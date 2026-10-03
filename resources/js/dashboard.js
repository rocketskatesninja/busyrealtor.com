// The tenant dashboard: its charts, and the drag-to-rearrange layout.
//
// The ten charts were ten near-identical Chart configurations written out in Blade, each
// wrapped in its own @if and carrying its own copy of the shared options. They are data
// now — the view says what to draw, this says how — which is also why the library can be
// imported here rather than hung on window for a script block to find.

import Chart from 'chart.js/auto';
import Sortable from 'sortablejs';

const root = () => document.getElementById('dashboard');

function config() {
    try {
        return JSON.parse(root()?.dataset.config || '{}');
    } catch (e) {
        return {};
    }
}

const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

// ── Charts ───────────────────────────────────────────────────────────────────
function palette() {
    const dark = document.documentElement.classList.contains('dark');

    return {
        legend: dark ? '#94a3b8' : '#6b7280',
        grid: dark ? '#334155' : '#f3f4f6',
        tick: dark ? '#94a3b8' : '#6b7280',
        primary: getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#3b82f6',
    };
}

function money(v) {
    if (v >= 1000000) return '$' + (v / 1000000).toFixed(1) + 'M';
    if (v >= 1000) return '$' + (v / 1000).toFixed(0) + 'K';
    return '$' + v;
}

function draw(spec, p) {
    const el = document.getElementById(spec.el);
    if (!el) return;

    const colour = spec.color === 'primary' ? p.primary : spec.color;
    const round = { grid: { color: p.grid }, ticks: { precision: 0, color: p.tick } };
    const scales = {
        y: { beginAtZero: true, ...round },
        x: { grid: { display: false }, ticks: { font: { size: 10 }, color: p.tick } },
    };

    if (spec.kind === 'doughnut' || spec.kind === 'pie') {
        new Chart(el, {
            type: spec.kind,
            data: { labels: spec.labels, datasets: [{ data: spec.data, backgroundColor: spec.colors }] },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 }, color: p.legend } } },
            },
        });

        return;
    }

    const dataset = spec.kind === 'line'
        ? { label: spec.label, data: spec.data, borderColor: colour, backgroundColor: colour + '22', fill: true, tension: 0.4, pointRadius: 2, borderWidth: 2 }
        : { label: spec.label, data: spec.data, backgroundColor: colour, borderRadius: 6 };

    if (spec.currency) {
        // Revenue is the one axis that is not a count, so it loses the integer precision
        // and gains a formatter.
        scales.y = { beginAtZero: true, grid: { color: p.grid }, ticks: { color: p.tick, callback: money } };
    }

    new Chart(el, {
        type: spec.kind,
        data: { labels: spec.labels, datasets: [dataset] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales,
        },
    });
}

// ── Drag to rearrange ────────────────────────────────────────────────────────
const LOCK_KEY = 'dashboard_locked';
const SECTIONS = [
    ['stat-cards-container', 'stat_cards'],
    ['charts-container', 'charts'],
    ['tables-container', 'tables'],
];
let sortables = [];

function getLocked() {
    try {
        return localStorage.getItem(LOCK_KEY) !== 'false';
    } catch (e) {
        return true;
    }
}

function setLocked(v) {
    try {
        localStorage.setItem(LOCK_KEY, v ? 'true' : 'false');
    } catch (e) {
        // Private browsing; the choice just does not outlive the page.
    }
}

function applyLockState(locked, cfg) {
    document.getElementById('dash-lock-icon').classList.toggle('hidden', !locked);
    document.getElementById('dash-unlock-icon').classList.toggle('hidden', locked);
    document.getElementById('dash-lock-label').textContent = locked ? 'Arrange Cards' : 'Lock Layout';
    document.body.classList.toggle('drag-mode', !locked);

    sortables.forEach(s => s.destroy());
    sortables = [];

    SECTIONS.forEach(([containerId, sectionKey]) => {
        const el = document.getElementById(containerId);
        if (!el) return;

        sortables.push(new Sortable(el, {
            animation: 150,
            ghostClass: 'opacity-50',
            dragClass: 'shadow-2xl',
            disabled: locked,
            scroll: false,
            onEnd() {
                const order = Array.from(el.querySelectorAll('[data-widget]')).map(w => w.dataset.widget);
                fetch(cfg.saveUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify({ section: sectionKey, order }),
                });
            },
        }));
    });
}

function wire() {
    if (!root()) return;

    const cfg = config();
    const p = palette();
    (cfg.charts || []).forEach(spec => draw(spec, p));

    const lockBtn = document.getElementById('dash-lock-btn');
    if (lockBtn) {
        lockBtn.addEventListener('click', () => {
            const locked = !getLocked();
            setLocked(locked);
            applyLockState(locked, cfg);
        });
    }

    applyLockState(window.innerWidth < 768 ? true : getLocked(), cfg);

    window.addEventListener('resize', () => {
        if (window.innerWidth < 768 && !getLocked()) applyLockState(true, cfg);
    });
}

wire();
