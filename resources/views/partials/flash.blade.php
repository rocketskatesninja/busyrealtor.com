{{-- Flash notification overlay — slides in from right, sits under header shadow --}}
@php
    $hasErrors = isset($errors) && $errors->any();
    $flashType = null;
    $flashMessage = null;
    if (session('success')) { $flashType = 'success'; $flashMessage = session('success'); }
    elseif (session('error')) { $flashType = 'error'; $flashMessage = session('error'); }
    elseif ($hasErrors) { $flashType = 'error'; $flashMessage = implode(' ', $errors->all()); }
    elseif (session('status')) { $flashType = 'info'; $flashMessage = session('status'); }
@endphp
@if($flashType)
<div id="flash-banner" style="position:fixed;left:0;right:0;z-index:40;opacity:0;transform:translateX(100%);transition:opacity .4s ease,transform .4s cubic-bezier(.4,0,.2,1);">
    @php
        $lightBg = match($flashType) { 'success' => '#dcfce7', 'error' => '#fee2e2', default => '#dbeafe' };
        $lightBorder = match($flashType) { 'success' => '#22c55e', 'error' => '#ef4444', default => '#3b82f6' };
        $lightText = match($flashType) { 'success' => '#166534', 'error' => '#991b1b', default => '#1e3a8a' };
        $darkBg = match($flashType) { 'success' => '#064e3b', 'error' => '#450a0a', default => '#172554' };
        $darkBorder = match($flashType) { 'success' => '#22c55e', 'error' => '#ef4444', default => '#3b82f6' };
        $darkText = match($flashType) { 'success' => '#a7f3d0', 'error' => '#fecaca', default => '#bfdbfe' };
        $icon = match($flashType) {
            'success' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>',
            'error' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>',
            default => '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>'
        };
    @endphp
    <div id="flash-bar" class="border-l-4 p-4" role="alert"
         style="background:{{ $lightBg }};border-color:{{ $lightBorder }};color:{{ $lightText }};"
         data-dark-bg="{{ $darkBg }}" data-dark-border="{{ $darkBorder }}" data-dark-text="{{ $darkText }}">
        <div style="max-width:80rem;margin:0 auto;display:flex;align-items:center;">
            <svg style="width:1.5rem;height:1.5rem;margin-right:.5rem;flex-shrink:0;" fill="currentColor" viewBox="0 0 20 20">
                {!! $icon !!}
            </svg>
            <span style="flex:1;font-size:.875rem;font-weight:500;">{{ $flashMessage }}</span>
            <button onclick="dismissFlash()" style="margin-left:1rem;opacity:.6;cursor:pointer;font-size:1.25rem;line-height:1;background:none;border:none;color:inherit;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='.6'">&times;</button>
        </div>
    </div>
</div>
<script>
(function(){
    var el = document.getElementById('flash-banner');
    var bar = document.getElementById('flash-bar');
    if (!el || !bar) return;

    // Position just below the header
    var header = document.querySelector('header[id]') || document.querySelector('header');
    if (header) {
        el.style.top = header.offsetHeight + 'px';
    } else {
        el.style.top = '0';
    }

    // Dark mode colors
    if (document.documentElement.classList.contains('dark') || document.body.classList.contains('dark')) {
        bar.style.background = bar.dataset.darkBg;
        bar.style.borderColor = bar.dataset.darkBorder;
        bar.style.color = bar.dataset.darkText;
    }

    // Slide in from right
    requestAnimationFrame(function(){ requestAnimationFrame(function(){
        el.style.opacity = '1';
        el.style.transform = 'translateX(0)';
    }); });

    // Auto-dismiss after 5s
    setTimeout(function(){ dismissFlash(); }, 5000);
})();

function dismissFlash() {
    var el = document.getElementById('flash-banner');
    if (!el) return;
    el.style.opacity = '0';
    el.style.transform = 'translateX(100%)';
    setTimeout(function(){ if (el) el.remove(); }, 400);
}
</script>
@endif
