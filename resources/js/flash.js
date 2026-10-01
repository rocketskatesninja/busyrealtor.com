/*
 | The flash banner: position it under the header, reveal it, auto-dismiss it.
 |
 | Moved out of partials/flash.blade.php, where it was ~2 KB of inline script shipped on every
 | page that can flash a message. Its three inline handlers are gone too — the dismiss button
 | is a delegated listener now, and the hover fade is CSS, which is where it belonged.
 */
const banner = () => document.getElementById('flash-banner');

function dismissFlash() {
    const el = banner();
    if (!el) return;

    el.style.opacity = '0';
    el.style.transform = 'translateX(100%)';
    setTimeout(() => el.remove(), 400);
}

const el = banner();
const bar = document.getElementById('flash-bar');

if (el && bar) {
    // Position just below the header.
    //
    // getBoundingClientRect().bottom, not offsetHeight, so the flash sits below the header's
    // actual bottom edge in the viewport. That matters when a banner above the header — the
    // "you are impersonating X" one in the admin layout — pushes it down: offsetHeight only
    // reports the header's own height, and the flash would slide in underneath it.
    //
    // Re-run on scroll because the header is sticky: once the impersonation banner scrolls
    // away, the header sticks to the top and .bottom shrinks back to its own height.
    const header = document.querySelector('header[id]') || document.querySelector('header');

    const positionFlash = () => {
        el.style.top = header ? Math.max(0, header.getBoundingClientRect().bottom) + 'px' : '0';
    };

    positionFlash();
    window.addEventListener('scroll', positionFlash, { passive: true });

    if (document.documentElement.classList.contains('dark') || document.body.classList.contains('dark')) {
        bar.style.background = bar.dataset.darkBg;
        bar.style.borderColor = bar.dataset.darkBorder;
        bar.style.color = bar.dataset.darkText;
    }

    // Two frames, so the browser paints the off-screen start position before the transition.
    requestAnimationFrame(() => requestAnimationFrame(() => {
        el.style.opacity = '1';
        el.style.transform = 'translateX(0)';
    }));

    setTimeout(dismissFlash, 8000);
}

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-flash-dismiss]')) dismissFlash();
});
