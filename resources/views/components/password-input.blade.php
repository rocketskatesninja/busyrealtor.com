@props([
    'name',
    'class' => 'w-full border border-gray-300 rounded-lg px-4 py-3 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition',
])

{{--
    Reusable password input with show/hide eye toggle.

    Default styling matches the login page (px-4 py-3, blue ring, gray-300 border).
    Override entirely by passing `class="..."`. All other attributes pass through
    to the underlying <input> via $attributes — so things like
    `required`, `minlength`, `autocomplete`, `x-model`, `id`, etc. work as expected.

    Usage examples:
        <x-password-input name="password" required autocomplete="current-password" />
        <x-password-input name="api_key" class="custom-tailwind-classes" />

    Color choice for the eye button:
      - Default: text-[#9ca3af]   (literal gray-400 — sidesteps the admin layout's
                                    `.dark .text-gray-400` override so the icon
                                    looks the same in both auth and admin layouts)
      - Hover:   hover:text-gray-600 (both layouts override this to #94a3b8 in
                                    dark mode identically, so hover behavior
                                    stays uniform across the site)
--}}

<div class="relative">
    <input type="password" name="{{ $name }}" class="{{ $class }}" {{ $attributes->merge(['autocomplete' => 'off']) }}>
    <button type="button" onclick="togglePasswordField(this)" aria-label="Show password"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-[#9ca3af] hover:text-gray-600">
        {{-- Eye — shown when the password is hidden, click to reveal --}}
        <svg class="eye-on w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
        {{-- Eye-slash — shown when the password is visible, click to hide --}}
        <svg class="eye-off w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.071-3.454m3.084-2.757A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.938 3.259M3 3l18 18"/>
        </svg>
    </button>
</div>

{{-- Toggle JS — emitted only on the first <x-password-input> render per page,
     even if multiple instances of the component appear. --}}
@once
<script>
function togglePasswordField(btn) {
    const input   = btn.parentElement.querySelector('input');
    const eyeOn   = btn.querySelector('.eye-on');
    const eyeOff  = btn.querySelector('.eye-off');
    const showing = input.type === 'text';

    input.type = showing ? 'password' : 'text';
    eyeOn.classList.toggle('hidden', !showing);
    eyeOff.classList.toggle('hidden', showing);
    btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
}
</script>
@endonce
