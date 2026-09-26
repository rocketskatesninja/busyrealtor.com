{{-- One inline SVG, named. Shapes live in config/icons.php.

     470 svg elements were written out by hand across the views, 221 of them distinct shapes: the
     envelope appeared 16 times with 12 different class strings. This renders the stored element
     verbatim and passes the class through, because the class was the only thing that varied per
     use.

     An unknown name renders nothing rather than throwing: a missing icon should not take a page
     down. --}}
@props(['name'])
@php($icon = config("icons.{$name}"))
@if ($icon)<svg {!! $icon['attrs'] !!}{{ $attributes }}>{!! $icon['inner'] !!}</svg>@endif
