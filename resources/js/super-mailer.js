// The super-admin bulk mailer: recipient filtering, sorting, paging and selection.
//
// Moved out of the view unchanged apart from where the recipients come from. The whole
// user list used to be json_encode'd into the middle of the function; it arrives as JSON
// on the component's own element instead.

import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('mailerApp', () => {
        let users = [];
        try {
            users = JSON.parse(document.getElementById('mailer')?.dataset.users || '[]');
        } catch (e) {
            users = [];
        }

    return {
        users,
        selectedIds: users.map(u => u.id),
        search: '',
        filterPlan: '',
        sortCol: 'name',
        sortDir: 'asc',
        perPage: 25,
        currentPage: 1,
        subject: '',
        body: '',
        sending: false,
        showPreview: false,
        showConfirm: false,
        pendingForm: null,

        get filteredUsers() {
            return this.users.filter(u => {
                if (this.filterPlan && u.plan !== this.filterPlan) return false;
                if (this.search) {
                    const q = this.search.toLowerCase();
                    return (u.name + ' ' + u.email + ' ' + u.tenant).toLowerCase().includes(q);
                }
                return true;
            });
        },

        get sortedUsers() {
            const col = this.sortCol;
            const dir = this.sortDir === 'asc' ? 1 : -1;
            return [...this.filteredUsers].sort((a, b) => {
                const av = (a[col] || '').toLowerCase();
                const bv = (b[col] || '').toLowerCase();
                return av < bv ? -dir : av > bv ? dir : 0;
            });
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredUsers.length / this.perPage));
        },

        get paginatedUsers() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.sortedUsers.slice(start, start + this.perPage);
        },

        get pageStart() {
            return this.filteredUsers.length === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
        },

        get pageEnd() {
            return Math.min(this.currentPage * this.perPage, this.filteredUsers.length);
        },

        get pageNumbers() {
            const total = this.totalPages;
            const cur = this.currentPage;
            if (total <= 7) return Array.from({length: total}, (_, i) => i + 1);
            const pages = [];
            pages.push(1);
            if (cur > 3) pages.push('...');
            for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i);
            if (cur < total - 2) pages.push('...');
            pages.push(total);
            return pages;
        },

        get visibleCount() {
            return this.filteredUsers.length;
        },

        get visibleIds() {
            return this.filteredUsers.map(u => u.id);
        },

        get allVisibleSelected() {
            const vis = this.visibleIds;
            return vis.length > 0 && vis.every(id => this.selectedIds.includes(id));
        },

        get someVisibleSelected() {
            return this.visibleIds.some(id => this.selectedIds.includes(id));
        },

        setSort(col) {
            if (this.sortCol === col) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortCol = col;
                this.sortDir = 'asc';
            }
        },

        toggleUser(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx > -1) this.selectedIds.splice(idx, 1);
            else this.selectedIds.push(id);
        },

        toggleAllVisible() {
            const vis = this.visibleIds;
            if (this.allVisibleSelected) {
                this.selectedIds = this.selectedIds.filter(id => !vis.includes(id));
            } else {
                vis.forEach(id => { if (!this.selectedIds.includes(id)) this.selectedIds.push(id); });
            }
        },

        confirmSend(e) {
            this.pendingForm = e.target;
            this.showConfirm = true;
        },

        doSend() {
            this.showConfirm = false;
            this.sending = true;
            this.pendingForm.submit();
        }
    };
    });
});
