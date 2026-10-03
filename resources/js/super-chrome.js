// The super-admin layout's tenant search, registered rather than left as a global the
// markup calls.

import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('tenantSearch', () => ({
        query: '',
        results: [],
        open: false,

        async search() {
            if (this.query.length < 2) {
                this.results = [];
                this.open = false;

                return;
            }

            try {
                const res = await fetch('/super-admin/api/tenants/search?q=' + encodeURIComponent(this.query));
                this.results = await res.json();
                this.open = this.results.length > 0;
            } catch (e) {
                this.results = [];
                this.open = false;
            }
        },
    }));
});
