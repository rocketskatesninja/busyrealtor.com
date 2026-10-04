<?php

namespace App\Support;

use Illuminate\Foundation\Vite;

/**
 * The built stylesheet, written into the page instead of linked from it.
 *
 * Firefox does not hold the first paint for an external stylesheet here. Measured on the
 * marketing page in Firefox 140: first contentful paint at 413-445ms, styles not applied
 * until 693-709ms -- so roughly a quarter of a second of completely unstyled page, browser
 * -default serif text and crammed underlined links, which is exactly what it looks like.
 * Chromium paints later than its own stylesheet arrives and so never shows it, which is why
 * this survived several rounds of measurement: every one of them was the wrong engine.
 *
 * Three things it is NOT, each tested and ruled out: the size of the sheet (an 868-byte one
 * flashed the same), the `rel=preload` tag Vite emits beside the stylesheet, and the
 * cross-origin Google Fonts sheet. Only removing the request fixes it, and inlining the
 * same CSS made the identical page clean across repeated runs.
 *
 * It is also why this only shows on a hard refresh: an ordinary reload has the sheet in
 * cache and applies it instantly, leaving no window to see.
 *
 * Cost is about 15KB gzipped per response, against one fewer request. That is worth paying
 * on a first visit and wasteful on the hundredth; the way to make it cheap is to stop one
 * CSS entry scanning all 56 views, which is already on the plan.
 */
class InlineStyles
{
    /** @var array<string,string> */
    private static array $memo = [];

    public static function tag(string $entry = 'resources/css/app.css'): string
    {
        // The dev server injects styles over its own websocket; leave it alone.
        if (is_file(public_path('hot'))) {
            return (string) app(Vite::class)([$entry]);
        }

        $css = self::$memo[$entry] ??= self::read($entry);

        // No build to read is not a reason to serve a page with no styling at all.
        return $css === '' ? (string) app(Vite::class)([$entry]) : '<style>'.$css.'</style>';
    }

    private static function read(string $entry): string
    {
        $manifest = public_path('build/manifest.json');

        if (! is_file($manifest)) {
            return '';
        }

        $file = json_decode((string) file_get_contents($manifest), true)[$entry]['file'] ?? null;
        $path = $file ? public_path('build/'.$file) : null;

        return $path && is_file($path) ? (string) file_get_contents($path) : '';
    }
}
