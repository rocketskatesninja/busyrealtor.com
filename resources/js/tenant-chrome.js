// The public site's chrome: the mobile nav, the hero header's scroll treatment, the dark
// mode toggle, and the consent-gated analytics loader.
//
// The pre-paint theme script stays inline in the layout and is not here. It has to run
// before the first paint or the page shows the wrong theme for a frame, which a deferred
// module cannot do.

// ── Mobile nav ───────────────────────────────────────────────────────────────
function menus() {
    return [
        document.getElementById('tenant-mobile-menu'),
        document.getElementById('tenant-default-mobile-menu'),
    ].filter(Boolean);
}

function navToggle() {
    const menu = menus()[0];
    if (!menu) return;
    const opening = menu.style.display === 'none' || menu.style.display === '';
    menu.style.display = opening ? 'block' : 'none';
}

function navClose() {
    menus().forEach((m) => { m.style.display = 'none'; });
}

document.addEventListener('click', (e) => {
    if (e.target.closest?.('[data-nav-toggle]')) {
        navToggle();
        return;
    }
    if (e.target.closest?.('[data-nav-close]')) {
        navClose();
        return;
    }
    if (e.target.closest?.('[data-theme-toggle]')) {
        themeToggle();
        return;
    }

    // Anywhere outside the header closes the menu.
    const header = document.getElementById('tenant-hero-header')
        || document.getElementById('tenant-default-header');
    if (header && !header.contains(e.target)) navClose();
});

// ── Hero header scroll treatment ─────────────────────────────────────────────
(function heroScroll() {
    const h = document.getElementById('tenant-hero-header');
    if (!h) return;

    const nav = document.getElementById('tenant-nav');
    const ham = document.getElementById('tenant-hamburger');
    const logo = document.getElementById('tenant-logo');

    function update() {
        const scrolled = window.scrollY > 50;
        h.classList.toggle('is-scrolled', scrolled);

        // The nav, the hamburger and the logo stay visible at every scroll position; only
        // their treatment changes. Over the hero they are white and get a drop-shadow so
        // they read against a photograph; once the header has a solid background they are
        // dark and need neither.
        //
        // They used to be set to opacity:0;pointer-events:none until you scrolled, which
        // meant the links simply were not there on the page people land on.
        const shadow = scrolled ? '' : 'drop-shadow(0 2px 4px rgba(0,0,0,0.3))';
        if (nav) nav.style.filter = shadow;
        if (ham) ham.style.filter = shadow;
        if (logo) logo.style.filter = shadow;

        // Close the mobile menu when scrolling back to the top.
        if (!scrolled) navClose();
    }

    window.addEventListener('scroll', update, { passive: true });
})();

// ── Dark mode ────────────────────────────────────────────────────────────────
function updateThemeIcons() {
    const dark = document.documentElement.classList.contains('dark');
    [
        ['theme-icon-moon', 'theme-icon-sun'],
        ['default-theme-icon-moon', 'default-theme-icon-sun'],
        ['default-mobile-theme-icon-moon', 'default-mobile-theme-icon-sun'],
    ].forEach(([moonId, sunId]) => {
        const moon = document.getElementById(moonId);
        const sun = document.getElementById(sunId);
        if (moon) moon.style.display = dark ? 'none' : '';
        if (sun) sun.style.display = dark ? '' : 'none';
    });

    const label = document.getElementById('default-mobile-theme-label');
    if (label) label.textContent = dark ? 'Light Mode' : 'Dark Mode';
}

function themeToggle() {
    const dark = !document.documentElement.classList.contains('dark');
    document.documentElement.classList.toggle('dark', dark);
    try {
        localStorage.setItem('theme', dark ? 'dark' : 'light');
    } catch (e) {
        // Private browsing. The toggle still works for this page.
    }
    updateThemeIcons();
}

updateThemeIcons();

// ── The HOA max field, revealed only when HOA is "yes" ───────────────────────
// The filter partial renders twice per page, sidebar and mobile drawer, so each select
// names its own target rather than both reaching for one id.
document.addEventListener('change', (e) => {
    const select = e.target.closest?.('[data-hoa-select]');
    if (!select) return;
    const wrap = document.getElementById(select.dataset.hoaSelect);
    if (wrap) wrap.style.display = select.value === 'yes' ? 'block' : 'none';
});

// ── Analytics, only with consent ─────────────────────────────────────────────
const GA_ID = document.querySelector('meta[name="ga-id"]')?.content;
let gaLoaded = false;

function loadAnalytics() {
    if (gaLoaded || !GA_ID) return;
    try {
        if (localStorage.getItem('cookie_consent') !== 'true') return;
    } catch (e) {
        return;
    }

    gaLoaded = true;
    const s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_ID;
    document.head.appendChild(s);

    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', GA_ID);
    window.gtag = gtag;
}

// Consent already stored, or granted just now on the banner. The banner used to call a
// global this file defined; it announces instead, so neither file has to know the other
// exists.
document.addEventListener('DOMContentLoaded', loadAnalytics);
document.addEventListener('cookie-consent-granted', loadAnalytics);
