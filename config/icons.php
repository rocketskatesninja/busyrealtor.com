<?php

/*
 | Inline SVG that appeared three or more times across the views, lifted out so it lives
 | once. Each entry keeps the original element's attributes and inner markup verbatim, so
 | <x-icon> renders byte-identical output — the class is the only thing that varied per
 | use, and that is passed through.
 |
 | Names follow the Heroicons they came from. To add one: copy the svg's non-class
 | attributes and its inner markup here, then replace the occurrences.
 */

return [
    'bars' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>',
    ],
    'bolt' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
    ],
    'building' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
    ],
    'calendar' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
    ],
    'chat-bubble' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
    ],
    'check' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
    ],
    'check-bold' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>',
    ],
    'check-solid' => [
        'attrs' => 'fill="currentColor" viewBox="0 0 20 20"',
        'inner' => '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>',
    ],
    'chevron-left' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>',
    ],
    'chevron-right' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>',
    ],
    'envelope' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
    ],
    'external-link' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>',
    ],
    'home' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
    ],
    'magnifying-glass' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>',
    ],
    'map-pin' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
    ],
    'map-pin-light' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
    ],
    'pencil' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
    ],
    'star-solid' => [
        'attrs' => 'fill="currentColor" viewBox="0 0 20 20"',
        'inner' => '<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>',
    ],
    'trash' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
    ],
    'x-mark' => [
        'attrs' => 'fill="none" stroke="currentColor" viewBox="0 0 24 24"',
        'inner' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
    ],
];
