// Behaviour for <x-password-input>: the show/hide eye, and the fallback that keeps
// non-credential secrets masked on browsers without -webkit-text-security.
//
// Two kinds of field share this component. A real credential is type=password. A secret
// that is not this user's password — an API key, an SMTP password — is a text input
// masked by -webkit-text-security, so no password manager treats it as a credential and
// none offers to save it alongside whatever email field shares the form.

const MASKABLE = CSS.supports?.('-webkit-text-security', 'disc') ?? false;

// Firefox before 119 has no -webkit-text-security, and an unmasked API key on screen is
// worse than the autofill problem the masking avoids. Those browsers get the previous
// arrangement back: a password input held readonly until it is focused, which is enough
// to keep a saved credential from being written into it.
if (!MASKABLE) {
    document.querySelectorAll('input[data-secret]').forEach((el) => {
        el.type = 'password';
        el.removeAttribute('data-secret');
        el.setAttribute('readonly', '');
        el.setAttribute('data-no-autofill', '');
    });

    document.addEventListener('focusin', (e) => {
        if (e.target.matches?.('input[data-no-autofill][readonly]')) {
            e.target.removeAttribute('readonly');
        }
    });
}

document.addEventListener('click', (e) => {
    const btn = e.target.closest?.('[data-password-toggle]');
    if (!btn) return;

    const input = btn.parentElement.querySelector('input');
    const eyeOn = btn.querySelector('.eye-on');
    const eyeOff = btn.querySelector('.eye-off');
    const masked = input.hasAttribute('data-secret');
    const showing = masked ? input.style.webkitTextSecurity === 'none' : input.type === 'text';

    if (masked) {
        input.style.webkitTextSecurity = showing ? 'disc' : 'none';
    } else {
        input.type = showing ? 'password' : 'text';
    }

    eyeOn.classList.toggle('hidden', !showing);
    eyeOff.classList.toggle('hidden', showing);
    btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
});
