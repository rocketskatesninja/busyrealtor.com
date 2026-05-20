{{-- ─────────────────────────────────────────────────────────────────
  Shared dark-mode rules used by layouts/tenant.blade.php and
  layouts/admin.blade.php. Both layouts already define :root --primary
  before including this file, so var(--primary) works either way.

  Tailwind utilities are scoped via `.dark` (see app.css custom-variant).
  These overrides translate raw gray-* utilities into a slate palette
  for dark mode. We use `!important` only where Tailwind's source order
  would otherwise win.
───────────────────────────────────────────────────────────────────── --}}

/* ===================== DARK MODE ===================== */
.dark, .dark body { color-scheme: dark; }
.dark body { background-color: #0f172a !important; color: #f1f5f9; }

/* Backgrounds */
.dark .bg-white    { background-color: #1e293b !important; }
.dark .bg-gray-50  { background-color: #0f172a !important; }
.dark .bg-gray-100 { background-color: #1e293b !important; }
.dark .bg-gray-200 { background-color: #334155 !important; }
.dark .bg-gray-800 { background-color: #020617 !important; }
.dark .bg-gray-900 { background-color: #020617 !important; }

/* Text */
.dark .text-gray-900 { color: #f1f5f9 !important; }
.dark .text-gray-800 { color: #e2e8f0 !important; }
.dark .text-gray-700 { color: #cbd5e1 !important; }
.dark .text-gray-600 { color: #94a3b8 !important; }
.dark .text-gray-500 { color: #64748b !important; }
.dark .text-gray-400 { color: #94a3b8 !important; }

/* Borders */
.dark .border-gray-50  { border-color: #1e293b !important; }
.dark .border-gray-100 { border-color: #1e293b !important; }
.dark .border-gray-200 { border-color: #334155 !important; }
.dark .border-gray-300 { border-color: #475569 !important; }
.dark .border-t, .dark .border-b, .dark .border-l, .dark .border-r,
.dark .border { border-color: #334155 !important; }

/* Divide */
.dark .divide-y > * + *, .dark .divide-x > * + *,
.dark .divide-y > :not(:last-child),
.dark .divide-x > :not(:last-child) { border-color: #334155 !important; }

/* Inputs, selects, textareas */
.dark input:not([type=checkbox]):not([type=radio]):not([type=range]),
.dark select,
.dark textarea {
    background-color: #334155 !important;
    color: #f1f5f9 !important;
    border-color: #475569 !important;
}
.dark input::placeholder,
.dark textarea::placeholder { color: #64748b !important; }

/* Hover states */
.dark .hover\:bg-gray-50:hover  { background-color: #1e293b !important; }
.dark .hover\:bg-gray-100:hover { background-color: #334155 !important; }
.dark .hover\:bg-gray-200:hover { background-color: #475569 !important; }

/* Tables */
.dark table thead { background-color: #1e293b !important; }
.dark table tbody tr { border-color: #334155; }
.dark table tbody tr:hover { background-color: #1e293b !important; }

/* Status badges — soften */
.dark .bg-green-50   { background-color: rgba(16,185,129,0.1)  !important; }
.dark .bg-green-100  { background-color: rgba(16,185,129,0.15) !important; }
.dark .bg-yellow-50  { background-color: rgba(234,179,8,0.1)   !important; }
.dark .bg-yellow-100 { background-color: rgba(234,179,8,0.15)  !important; }
.dark .bg-blue-50    { background-color: rgba(59,130,246,0.1)  !important; }
.dark .bg-blue-100   { background-color: rgba(59,130,246,0.15) !important; }
.dark .bg-red-50     { background-color: rgba(239,68,68,0.1)   !important; }
.dark .bg-red-100    { background-color: rgba(239,68,68,0.15)  !important; }
.dark .bg-purple-50  { background-color: rgba(168,85,247,0.1)  !important; }
.dark .bg-purple-100 { background-color: rgba(168,85,247,0.15) !important; }
.dark .bg-indigo-50  { background-color: rgba(99,102,241,0.1)  !important; }
.dark .bg-indigo-100 { background-color: rgba(99,102,241,0.15) !important; }
.dark .hover\:bg-blue-100:hover  { background-color: rgba(59,130,246,0.25) !important; }
.dark .hover\:bg-green-100:hover { background-color: rgba(16,185,129,0.25) !important; }
.dark .hover\:bg-red-100:hover   { background-color: rgba(239,68,68,0.25)  !important; }

/* Shadows become softer */
.dark .shadow-sm { box-shadow: 0 1px 2px rgba(0,0,0,0.5)  !important; }
.dark .shadow    { box-shadow: 0 1px 6px rgba(0,0,0,0.5)  !important; }
.dark .shadow-lg { box-shadow: 0 4px 20px rgba(0,0,0,0.6) !important; }
.dark .shadow-xl { box-shadow: 0 8px 30px rgba(0,0,0,0.7) !important; }

/* Rings */
.dark .ring-1, .dark .ring-2 { --tw-ring-color: #475569; }

/* Nav (shared by tenant + admin headers) */
.dark header.bg-white     { background-color: #1e293b !important; }
.dark nav a.text-gray-700 { color: #cbd5e1 !important; }
