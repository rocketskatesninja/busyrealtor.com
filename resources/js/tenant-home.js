// The public homepage: the optional hero effects, plus the reveal and count-up shared
// with the marketing site.

import { revealOnScroll, countUp } from './reveal';

function effects() {
    try {
        return JSON.parse(document.getElementById('hero-section')?.dataset.effects || '{}');
    } catch (e) {
        return {};
    }
}

const fx = effects();

if (fx.parallax) {
    const layer = document.getElementById('hero-parallax');
    const hero = document.getElementById('hero-section');

    if (layer && hero) {
        // The wrapper is taller than the hero; half the difference sits above it and half
        // below. Never move further than that, or an edge shows. Clamping to the measured
        // slack rather than a hardcoded number means the CSS can change without this
        // needing to know.
        const headroom = () => Math.max(0, (layer.offsetHeight - hero.offsetHeight) / 2);

        window.addEventListener('scroll', () => {
            const shift = Math.min(window.scrollY * 0.2, headroom());
            layer.style.transform = `translate3d(0, ${shift}px, 0)`;
        }, { passive: true });
    }
}

if (fx.particles) {
    const container = document.getElementById('hero-particles');
    if (container) {
        for (let i = 0; i < 22; i++) {
            const el = document.createElement('div');
            const size = 2 + Math.random() * 4;
            el.className = 'hero-particle';
            el.style.cssText = [
                `left:${Math.random() * 100}%`,
                `bottom:${Math.random() * 40}%`,
                `width:${size}px`,
                `height:${size}px`,
                `animation-duration:${10 + Math.random() * 14}s`,
                `animation-delay:-${Math.random() * 18}s`,
                `opacity:${0.15 + Math.random() * 0.45}`,
            ].join(';');
            container.appendChild(el);
        }
    }
}

revealOnScroll();
countUp();

// Alpine's collapse plugin animates height; without this the content is visible for a
// frame before it takes over.
document.querySelectorAll('[x-collapse]').forEach((el) => {
    el.style.overflow = 'hidden';
});
