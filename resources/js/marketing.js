/*
 | The marketing site's scroll reveal, count-up stats and header-on-scroll.
 |
 | Lifted verbatim out of layouts/marketing.blade.php: one self-contained IIFE with no Blade
 | in it, no globals and nothing calling into it from markup, which is why it needed no
 | changes at all to become a module. It is also now strictly more correct — it ran at parse
 | time and queried for elements that may not have existed yet; a module runs after parsing.
 */
(function () {
    // Scroll reveal
    var ro = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('is-visible'); ro.unobserve(e.target); }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -36px 0px' });
    document.querySelectorAll('.reveal,.reveal-left,.reveal-right,.reveal-scale')
            .forEach(function (el) { ro.observe(el); });

    // Count-up animation
    function countUp(el) {
        var raw = el.dataset.target || el.textContent.trim();
        var m = raw.match(/^([^0-9]*)([0-9][0-9,]*)(\+?)(.*)$/);
        if (!m) return;
        var pre = m[1], numStr = m[2].replace(/,/g,''), plus = m[3], suf = m[4];
        var target = parseInt(numStr, 10);
        if (isNaN(target)) return;
        var dur = 1800, t0 = performance.now();
        (function tick(now) {
            var p = Math.min((now - t0) / dur, 1);
            var ease = 1 - Math.pow(1 - p, 3);
            el.textContent = pre + Math.round(ease * target).toLocaleString() + (p >= 1 ? plus : '') + suf;
            if (p < 1) requestAnimationFrame(tick);
            else el.textContent = raw;
        })(t0);
    }
    var co = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { countUp(e.target); co.unobserve(e.target); }
        });
    }, { threshold: 0.5 });
    document.querySelectorAll('.count-up').forEach(function (el) {
        el.dataset.target = el.textContent.trim(); co.observe(el);
    });

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
