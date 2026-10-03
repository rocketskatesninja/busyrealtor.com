// Scroll reveal and the count-up stat animation.
//
// Both existed twice: once in marketing.js for the marketing site and once inline in the
// tenant homepage, written slightly differently each time. One implementation now, used
// by both, since the markup contract -- .reveal/.reveal-left/.reveal-right/.reveal-scale
// and .count-up -- was already identical.

export function revealOnScroll() {
    const targets = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
    if (!targets.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.12 });

    targets.forEach(el => observer.observe(el));
}

export function countUp() {
    const els = document.querySelectorAll('.count-up');
    if (!els.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            observer.unobserve(entry.target);

            const el = entry.target;
            const raw = el.textContent.trim();
            const target = parseFloat(raw.replace(/[^0-9.]/g, ''));
            const suffix = raw.replace(/[0-9.,]/g, '');
            if (isNaN(target)) return;

            const start = performance.now();
            const step = (now) => {
                const progress = Math.min((now - start) / 1600, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.round(eased * target).toLocaleString() + suffix;
                if (progress < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        });
    }, { threshold: 0.3 });

    els.forEach(el => observer.observe(el));
}
