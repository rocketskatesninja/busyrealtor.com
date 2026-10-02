{{-- One inline SVG, named. Shapes live in config/icons.php.

     470 svg elements were written out by hand across the views, 221 of them distinct shapes: the
     envelope appeared 16 times with 12 different class strings. This renders the stored element
     verbatim and passes the class through, because the class was the only thing that varied per
     use.

     An unknown name renders nothing rather than throwing: a missing icon should not take a page
     down.

     The width and height attributes are a floor, not a size. Any CSS rule beats a
     presentation attribute, so the w-/h- classes still decide how big the icon is
     whenever the stylesheet is there. When it is not -- a hashed asset deleted by a
     rebuild, a failed request -- an svg with no dimensions lays out at 100% of the
     viewport, and the page fills with one enormous icon. --}}
@props(['name'])
@php($icon = config("icons.{$name}"))
@if ($icon)<svg {!! $icon['attrs'] !!} {{ $attributes->merge(['width' => 20, 'height' => 20]) }}>{!! $icon['inner'] !!}</svg>@endif
