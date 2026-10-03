// The staff screen: the add panel, drag-to-reorder, and the edit modal.

const config = () => {
    const el = document.getElementById('staff-list');
    try {
        return JSON.parse(el?.dataset.config || '{}');
    } catch (e) {
        return {};
    }
};

const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

function wire() {
    const cfg = config();

    const panel = document.getElementById('add-staff-panel');
    document.querySelector('[data-toggle-add-staff]')?.addEventListener('click', () => {
        if (panel) panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    });
    document.querySelector('[data-hide-add-staff]')?.addEventListener('click', () => {
        if (panel) panel.style.display = 'none';
    });

    const modal = document.getElementById('edit-modal');
    document.querySelector('[data-close-edit-modal]')?.addEventListener('click', () => {
        modal?.classList.add('hidden');
    });

    // ── Drag to reorder ──────────────────────────────────────────────────────
    const list = document.getElementById('staff-list');
    if (list) {
        let dragged = null;
        list.querySelectorAll('[data-id]').forEach((row) => {
            row.draggable = true;
            row.addEventListener('dragstart', () => { dragged = row; row.style.opacity = '0.5'; });
            row.addEventListener('dragend', () => { row.style.opacity = ''; saveOrder(cfg); });
            row.addEventListener('dragover', (e) => {
                e.preventDefault();
                const after = row.getBoundingClientRect().top + row.offsetHeight / 2 > e.clientY;
                list.insertBefore(dragged, after ? row : row.nextSibling);
            });
        });
    }

    // ── Edit modal ───────────────────────────────────────────────────────────
    document.querySelectorAll('.edit-member-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            let member;
            try {
                member = JSON.parse(btn.dataset.member);
            } catch (e) {
                return;
            }
            openEditModal(member, cfg);
        });
    });
}

function saveOrder(cfg) {
    const order = [...document.querySelectorAll('#staff-list [data-id]')].map(el => el.dataset.id);
    fetch(cfg.orderUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ order }),
    });
}

function openEditModal(member, cfg) {
    const form = document.getElementById('edit-form');
    form.action = `${cfg.staffBaseUrl}/${member.id}`;
    document.getElementById('edit-name').value = member.name;
    document.getElementById('edit-title').value = member.title ?? '';
    document.getElementById('edit-bio').value = member.bio ?? '';
    document.getElementById('edit-email').value = member.email ?? '';
    document.getElementById('edit-phone').value = member.phone ?? '';
    document.getElementById('edit-homepage').checked = member.display_on_homepage;
    document.getElementById('edit-appts').checked = member.accepts_appointments;
    document.getElementById('edit-modal').classList.remove('hidden');
}

wire();
