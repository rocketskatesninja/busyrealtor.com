<?php

/*
 * Slug values that must NOT be allowed as a tenant URL slug.
 *
 * Three categories:
 *   1. Real top-level routes that would be shadowed (admin, login, etc.)
 *   2. Asset / build paths Apache or .htaccess might serve directly
 *   3. JS/PHP reserved words and prototype-pollution names that
 *      shouldn't be valid identifiers anywhere in the app
 *
 * If you add a new top-level route to web.php, add the path here too
 * so a tenant can't accidentally register a colliding slug.
 */

return [
    // Top-level app routes (web.php)
    'admin', 'super-admin', 'api', 'login', 'logout', 'register',
    'forgot-password', 'reset-password', 'email', 'auth', 'up',

    // Public legal / informational pages
    'terms', 'privacy-policy', 'about', 'contact', 'help', 'support', 'status',

    // Static asset / build directories
    'js', 'img', 'images', 'css', 'assets', 'javascript', 'storage',
    'build', 'vendor', 'app', 'public',

    // Common subdomain-like names a tenant might try to grab
    'www', 'mail', 'email', 'smtp', 'imap', 'pop', 'ftp', 'ssh',
    'vpn', 'proxy', 'cdn', 'static',

    // Billing / Stripe surface
    'stripe', 'webhook', 'webhooks', 'payment', 'payments',
    'billing', 'checkout', 'subscribe', 'subscription',

    // Laravel ecosystem packages (in case any are added later)
    'oauth', 'sanctum', 'livewire', 'telescope', 'horizon',
    'debugbar', '_ignition', 'nova',

    // JS/PHP reserved or prototype-pollution names
    'null', 'undefined', 'true', 'false', 'constructor',
    'prototype', '__proto__', 'this', 'self',
];
