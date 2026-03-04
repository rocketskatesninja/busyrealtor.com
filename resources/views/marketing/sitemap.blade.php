<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>{{ url('/marketing-sitemap.xml') }}</loc>
        <lastmod>{{ now()->format('Y-m-d') }}</lastmod>
    </sitemap>
    @foreach($tenants as $tenant)
    <sitemap>
        <loc>{{ url('/' . $tenant->slug . '/sitemap.xml') }}</loc>
        <lastmod>{{ now()->format('Y-m-d') }}</lastmod>
    </sitemap>
    @endforeach
</sitemapindex>
