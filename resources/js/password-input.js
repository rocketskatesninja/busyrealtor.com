// Behaviour for <x-password-input>: the show/hide eye, and releasing the readonly that
// keeps a password manager out of fields that are not the user's password.
//
// Both listeners are delegated from the document, so they cover inputs that were not in
// the page when this ran — a field inside an Alpine template, or one a later script
// builds — and they attach once however many password inputs a page has.

// A field marked data-no-autofill is rendered readonly so the browser will not put a
// saved password in it. Focusing it releases that, so typing works normally.
document.addEventListener('focusin', (e) => {
    if (e.target.matches?.('input[data-no-autofill][readonly]')) {
        e.target.removeAttribute('readonly');
    }
});

document.addEventListener('click', (e) => {
    const btn = e.target.closest?.('[data-password-toggle]');
    if (!btn) return;

    const input = btn.parentElement.querySelector('input');
    const eyeOn = btn.querySelector('.eye-on');
    const eyeOff = btn.querySelector('.eye-off');
    const showing = input.type === 'text';

    input.type = showing ? 'password' : 'text';
    eyeOn.classList.toggle('hidden', !showing);
    eyeOff.classList.toggle('hidden', showing);
    btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
});
