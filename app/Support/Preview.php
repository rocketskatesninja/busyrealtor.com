<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Is this page being rendered as decoration inside someone else's page?
 *
 * The marketing site embeds a tenant's public home page twice, in iframes scaled to 0.72
 * with pointer-events disabled. Those frames are an advert for the product, not a visit:
 * nobody can click them, nothing scrolls inside them, and whatever they run is running
 * for no one. So a preview renders the page plainly -- no continuous animation, no
 * cookie banner, and therefore no analytics consent and no pageview.
 *
 * The flag is a query parameter rather than a header or a referer check because the two
 * iframes are the only thing that sets it, and a query parameter is the one part of the
 * request an iframe's src can actually control.
 */
class Preview
{
    public static function active(?Request $request = null): bool
    {
        return ($request ?? request())->boolean('preview');
    }
}
