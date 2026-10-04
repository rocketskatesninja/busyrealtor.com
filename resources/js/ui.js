// Site-wide form behaviours that were previously an on* attribute per element. Both are
// delegated from the document, so a row rendered inside a @foreach costs nothing extra:
// the properties list alone carried twenty onsubmit attributes for two in the source.

// <form data-confirm="Delete this property?"> — ask before submitting.
document.addEventListener('submit', (e) => {
    const message = e.target?.dataset?.confirm;
    if (message && !confirm(message)) {
        e.preventDefault();
    }
}, true);

// <select data-submit-on-change> — submit the owning form when the value changes.
document.addEventListener('change', (e) => {
    if (e.target?.matches?.('[data-submit-on-change]')) {
        e.target.form?.submit();
    }
});

// <button data-print> — the browser's print dialog.
document.addEventListener('click', (e) => {
    if (e.target.closest?.('[data-print]')) window.print();
});
