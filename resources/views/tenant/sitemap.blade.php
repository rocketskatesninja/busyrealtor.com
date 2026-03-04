<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    {{-- Homepage --}}
    <url>
        <loc>{{ url('/' . $account) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    {{-- Gallery --}}
    <url>
        <loc>{{ url('/' . $account . '/gallery') }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    {{-- Map --}}
    <url>
        <loc>{{ url('/' . $account . '/map') }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
    </url>
    {{-- Contact --}}
    <url>
        <loc>{{ url('/' . $account . '/contact') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    {{-- Active property listings --}}
    @foreach($properties as $property)
    <url>
        <loc>{{ url('/' . $account . '/property/' . $property->id) }}</loc>
        <lastmod>{{ $property->updated_at->format('Y-m-d') }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach
    {{-- Legal pages --}}
    <url>
        <loc>{{ url('/' . $account . '/privacy-policy') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.2</priority>
    </url>
    <url>
        <loc>{{ url('/' . $account . '/terms') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.2</priority>
    </url>
</urlset>
