<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Was this request made by the browser speculatively, rather than by someone opening a link?
 *
 * Two screens mark a record as seen while rendering it — opening a message marks it read,
 * opening a feedback item marks it reviewed. That is the behaviour people want, but it means
 * a GET has a side effect, and browsers fetch links nobody clicked: Chrome and Safari
 * prefetch or prerender on hover, link previews fetch on paste, and extensions scan. Any of
 * those consumed the unread state of something the admin had not looked at.
 *
 * The request tells us when it is speculative. Sec-Purpose is the current standard;
 * Purpose and X-Moz are the older spellings still in use. The page itself renders exactly
 * as before either way — only the write is skipped.
 */
class Prefetch
{
    public static function detected(Request $request): bool
    {
        foreach (['Sec-Purpose', 'Purpose', 'X-Purpose', 'X-Moz'] as $header) {
            $value = strtolower((string) $request->header($header));

            if (str_contains($value, 'prefetch') || str_contains($value, 'prerender') || str_contains($value, 'preview')) {
                return true;
            }
        }

        return false;
    }
}
