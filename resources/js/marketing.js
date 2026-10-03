/*
 | The marketing site's header-on-scroll. The scroll reveal and the count-up stats it
 | used to carry are in ./reveal, shared with the tenant homepage, which had its own
 | slightly different copy of both.
 */

import { revealOnScroll, countUp } from './reveal';

revealOnScroll();
countUp();

(function () {
    // Header scroll: pure JS, no Alpine/Tailwind dependency
    (function() {
        var header = document.getElementById('main-header');
        if (!header) return;
        var isDark = document.documentElement.classList.contains('dark');
        function updateHeader() {
            var scrolled = window.scrollY > 40;
            header.style.backgroundColor = scrolled
                ? (isDark ? '#1e293b' : '#ffffff')
                : 'transparent';
            header.classList.toggle('shadow-md', scrolled);
            header.classList.toggle('is-scrolled', scrolled);
            var toggle = document.getElementById('theme-toggle');
            if (toggle) { toggle.style.opacity = scrolled ? '1' : '0'; toggle.style.pointerEvents = scrolled ? 'auto' : 'none'; }
        }
        window.addEventListener('scroll', updateHeader, { passive: true });
        updateHeader();
        // React to dark mode toggle widget (class change on <html>)
        new MutationObserver(function() {
            isDark = document.documentElement.classList.contains('dark');
            updateHeader();
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    })();
})();
