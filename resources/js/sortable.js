/*
 | SortableJS, bundled. Same story as chart.js: a 45 KB hand-vendored copy with no content
 | hash, declared in package.json but never imported.
 |
 | Exposed on window because the four screens that drag things still call `new Sortable()` and
 | `Sortable.create()` inline, several behind a `typeof Sortable !== 'undefined'` guard. That
 | guard is why the ordering matters: a module runs after the classic inline scripts at the end
 | of the body, so any caller that ran immediately would see `undefined`, skip silently, and
 | leave drag-and-drop quietly dead. Every current caller runs inside a function, an Alpine
 | init or a DOMContentLoaded handler — checked before this was written.
 */
import Sortable from 'sortablejs';

window.Sortable = Sortable;
