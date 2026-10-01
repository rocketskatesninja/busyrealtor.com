/*
 | The admin header's menus and its dark-mode switch.
 |
 | Moved out of layouts/admin.blade.php. The four onclick attributes that called these are
 | replaced by data attributes and one delegated listener, which is the part that counts
 | towards dropping 'unsafe-inline' from the CSP.
 */
function toggle(id) {
    const el = document.getElementById(id);
    if (!el) return;

    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function updateDarkModeLabels() {
    const isDark = document.documentElement.classList.contains('dark');

    document.querySelectorAll('.dark-mode-label').forEach((el) => {
        el.textContent = isDark ? 'Light Mode' : 'Dark Mode';
    });
}

function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');

    try {
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    } catch {
        // A browser that refuses storage still gets the toggle, just not the memory of it.
    }

    updateDarkModeLabels();
}

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-toggle-user-menu]')) return toggle('user-dropdown');
    if (event.target.closest('[data-toggle-mobile-menu]')) return toggle('mobile-menu');
    if (event.target.closest('[data-toggle-dark-mode]')) return toggleDarkMode();

    // Close the user dropdown on a click outside it.
    const btn = document.getElementById('user-menu-btn');
    const dropdown = document.getElementById('user-dropdown');

    if (dropdown && btn && !btn.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});

document.addEventListener('DOMContentLoaded', updateDarkModeLabels);
