import './bootstrap';
import './ui';
import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';
import collapse from '@alpinejs/collapse';

Alpine.plugin(intersect);
Alpine.plugin(collapse);

Alpine.store('theme', {
    dark: false,
    // Pages that opt out of theme switching set <body data-theme="force-dark">.
    // The store still initializes (so x-text bindings work) but never touches
    // the <html> class, so the layout's hardcoded `class="dark"` wins.
    locked() {
        return document.body?.dataset?.theme === 'force-dark';
    },
    init() {
        if (this.locked()) { this.dark = true; return; }
        const saved = localStorage.getItem('theme');
        if (saved) {
            this.dark = saved === 'dark';
        } else {
            this.dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        this.apply();
    },
    toggle() {
        if (this.locked()) return;
        this.dark = !this.dark;
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        this.apply();
    },
    apply() {
        if (this.locked()) return;
        document.documentElement.classList.toggle('dark', this.dark);
    }
});

window.Alpine = Alpine;

// Deliberately on DOMContentLoaded rather than here. Every @vite entry is a deferred
// module, and the spec runs all of them before the DOMContentLoaded task is queued, so
// starting on the event gives a page-specific bundle further down the document its one
// chance to register its Alpine.data() components before Alpine walks the DOM. No
// readyState guard: during a deferred module readyState already reads "interactive",
// so a guard looking for "loading" skips the listener and starts Alpine too early.
document.addEventListener('DOMContentLoaded', () => Alpine.start());
