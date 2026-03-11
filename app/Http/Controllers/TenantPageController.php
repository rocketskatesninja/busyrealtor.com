<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\SiteSettings;
use App\Models\LegalPage;
use App\Models\Appointment;
use App\Models\StaffMember;
use App\Models\PropertyView;
use Illuminate\Http\Request;

class TenantPageController extends Controller
{
    private function getSettings()
    {
        $tenant = app('tenant');
        return SiteSettings::where('tenant_id', $tenant->id)->first()
            ?? new SiteSettings(['tenant_id' => $tenant->id]);
    }

    public function home($account)
    {
        $tenant   = app('tenant');
        $settings = $this->getSettings();
        $featured = Property::where('is_featured', true)->where('listing_status', 'active')->limit(6)->get();
        $staff    = \App\Models\StaffMember::where('display_on_homepage', true)->orderBy('sort_order')->get();

        return view('tenant.home', compact('tenant', 'settings', 'featured', 'staff'));
    }

    public function gallery($account, Request $request)
    {
        $tenant   = app('tenant');
        $settings = $this->getSettings();

        $query = Property::with('images');
        if ($request->search)        $query->where(function($q) use ($request) { $q->where('title','like',"%{$request->search}%")->orWhere('address_street','like',"%{$request->search}%")->orWhere('address_city','like',"%{$request->search}%"); });
        if ($request->type)          $query->where('property_type', $request->type);
        if ($request->status)        $query->where('listing_status', $request->status);
        if ($request->price_min)     $query->where('price', '>=', $request->price_min);
        if ($request->price_max)     $query->where('price', '<=', $request->price_max);
        if ($request->beds)          $query->where('bedrooms', '>=', $request->beds);
        if ($request->baths)         $query->where('bathrooms', '>=', $request->baths);
        if ($request->sqft_min)      $query->where('square_feet', '>=', $request->sqft_min);
        if ($request->sqft_max)      $query->where('square_feet', '<=', $request->sqft_max);
        if ($request->year_min)      $query->where('year_built', '>=', $request->year_min);
        if ($request->year_max)      $query->where('year_built', '<=', $request->year_max);
        if ($request->garage_spaces) $query->where('garage', '>=', $request->garage_spaces);
        if ($request->hoa === 'yes') $query->where('hoa_fee', '>', 0);
        if ($request->hoa === 'no')  $query->where(function($q) { $q->whereNull('hoa_fee')->orWhere('hoa_fee', 0); });
        if ($request->hoa_max)       $query->where('hoa_fee', '<=', $request->hoa_max);
        if ($request->features) {
            foreach ((array) $request->features as $feature) {
                $query->whereJsonContains('amenities', $feature);
            }
        }

        $sort = $request->sort ?? 'newest';
        match($sort) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'oldest'     => $query->orderBy('created_at', 'asc'),
            default      => $query->orderBy('created_at', 'desc'),
        };

        $properties = $query->paginate(12)->withQueryString();
        return view('tenant.gallery', compact('tenant', 'settings', 'properties'));
    }

    public function map($account)
    {
        $tenant     = app('tenant');
        $settings   = $this->getSettings();
        $properties = Property::whereNotNull('latitude')->whereNotNull('longitude')->get();
        return view('tenant.map', compact('tenant', 'settings', 'properties'));
    }

    public function property($account, $id)
    {
        $tenant   = app('tenant');
        $settings = $this->getSettings();
        $property = Property::with(['images', 'staffMember'])->findOrFail($id);

        // Increment view count (simple, no rate limiting needed for MVP)
        try {
            PropertyView::create(['property_id' => $property->id, 'tenant_id' => $tenant->id, 'ip_address' => request()->ip()]);
            $property->increment('view_count');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("PropertyView create failed: " . $e->getMessage());
        }

        $related = Property::with('images')->where('property_type', $property->property_type)
            ->where('id', '!=', $property->id)
            ->where('listing_status', 'active')
            ->limit(3)->get();

        return view('tenant.property', compact('tenant', 'settings', 'property', 'related'));
    }

    public function contact($account)
    {
        $tenant   = app('tenant');
        $settings = $this->getSettings();
        return view('tenant.contact', compact('tenant', 'settings'));
    }

    public function chat($account)
    {
        $tenant   = app('tenant');
        $settings = $this->getSettings();
        if (!($settings->chatbot_enabled ?? false)) {
            return redirect()->route('tenant.contact', $account);
        }
        return view('tenant.chat', compact('tenant', 'settings'));
    }

    public function privacy($account)
    {
        $tenant   = app('tenant');
        $settings = $this->getSettings();
        $page     = LegalPage::where('page_type', 'privacy')->firstOrNew(['tenant_id' => $tenant->id, 'page_type' => 'privacy']);
        return view('tenant.legal', compact('tenant', 'settings', 'page'));
    }

    public function terms($account)
    {
        $tenant   = app('tenant');
        $settings = $this->getSettings();
        $page     = LegalPage::where('page_type', 'terms')->firstOrNew(['tenant_id' => $tenant->id, 'page_type' => 'terms']);
        return view('tenant.legal', compact('tenant', 'settings', 'page'));
    }

    public function favicon($account, \Illuminate\Http\Request $request)
    {
        $tenant   = \App\Models\Tenant::where('slug', $account)->firstOrFail();
        $settings = \App\Models\SiteSettings::where('tenant_id', $tenant->id)->first();

        $preset = $settings->favicon_preset ?? null;

        // Allow an override color via ?color=rrggbb (used by email templates for white icons)
        $rawColor = ltrim($request->query('color', ''), '#');
        $pcolor   = preg_match('/^[0-9a-fA-F]{3,6}$/', $rawColor)
            ? '#' . $rawColor
            : ($settings->primary_color ?? '#3B82F6');

        $svg = $preset ? \App\Models\SiteSettings::faviconSvg($preset, $pcolor) : null;

        if (!$svg) {
            abort(404);
        }

        return response($svg, 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function sitemap($account)
    {
        $tenant = \App\Models\Tenant::where('slug', $account)->firstOrFail();
        $properties = \App\Models\Property::where('tenant_id', $tenant->id)
            ->whereIn('listing_status', ['active', 'pending'])
            ->get(['id', 'updated_at']);
        return response()
            ->view('tenant.sitemap', compact('account', 'properties'))
            ->header('Content-Type', 'text/xml');
    }


    public function llms($account)
    {
        $tenant     = app('tenant');
        $settings   = SiteSettings::where('tenant_id', $tenant->id)->first();
        $baseUrl    = url('/' . $account);

        $agencyName = $settings?->site_title    ?: '[Your Agency Name] — Real Estate';
        $tagline    = $settings?->tagline        ?: 'Your trusted local real estate experts';
        $description = $settings?->site_description;

        $properties = Property::where('tenant_id', $tenant->id)
            ->whereIn('listing_status', ['active', 'pending'])
            ->orderBy('listing_status')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'listing_status', 'property_type', 'price', 'address_street', 'address_city', 'address_state', 'bedrooms', 'bathrooms', 'square_feet']);

        $staff = StaffMember::where('tenant_id', $tenant->id)
            ->orderBy('sort_order')
            ->get(['name', 'role', 'email']);

        $lines = [];
        $lines[] = "# {$agencyName}";
        $lines[] = "";
        $lines[] = "> {$tagline}";
        $lines[] = "";

        if ($description) {
            $lines[] = $description;
            $lines[] = "";
        }

        // Contact
        $hasContact = $settings && ($settings->contact_email || $settings->contact_phone || $settings->contact_address);
        if ($hasContact) {
            $lines[] = "## Contact";
            $lines[] = "";
            if ($settings->contact_email)   $lines[] = "- Email: {$settings->contact_email}";
            if ($settings->contact_phone)   $lines[] = "- Phone: {$settings->contact_phone}";
            if ($settings->contact_address) $lines[] = "- Address: {$settings->contact_address}";
            $lines[] = "";
        }

        // Listings
        if ($properties->isNotEmpty()) {
            $lines[] = "## Listings";
            $lines[] = "";
            foreach ($properties as $p) {
                $price  = $p->price ? '$' . number_format($p->price) : 'Price on request';
                $beds   = $p->bedrooms   ? "{$p->bedrooms} bed"  . ($p->bedrooms   != 1 ? 's' : '') : null;
                $baths  = $p->bathrooms  ? "{$p->bathrooms} bath" . ($p->bathrooms  != 1 ? 's' : '') : null;
                $sqft   = $p->square_feet ? number_format($p->square_feet) . ' sqft' : null;
                $details = implode(', ', array_filter([$beds, $baths, $sqft]));
                $addr   = trim("{$p->address_street}, {$p->address_city}, {$p->address_state}", ', ');
                $status = $p->listing_status === 'pending' ? ' (Pending)' : '';
                $detail_str = $details ? " — {$details}" : '';
                $lines[] = "- [{$p->title}]({$baseUrl}/property/{$p->id}): {$addr} — {$price}{$detail_str}{$status}";
            }
            $lines[] = "";
        }

        // Staff
        if ($staff->isNotEmpty()) {
            $lines[] = "## Team";
            $lines[] = "";
            foreach ($staff as $s) {
                $entry = "- {$s->name}";
                if ($s->role) $entry .= " — {$s->role}";
                if ($s->email) $entry .= " ({$s->email})";
                $lines[] = $entry;
            }
            $lines[] = "";
        }

        // Pages
        $lines[] = "## Pages";
        $lines[] = "";
        $lines[] = "- [Home]({$baseUrl}/): Property listings and agency overview";
        $lines[] = "- [Gallery]({$baseUrl}/gallery): Browse and filter all listings";
        $lines[] = "- [Map]({$baseUrl}/map): Interactive map of all listings";
        $lines[] = "- [Contact]({$baseUrl}/contact): Get in touch";
        $lines[] = "";

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }

    public function confirmAppointment($account, $token)
    {
        $tenant      = app('tenant');
        $settings    = $this->getSettings();
        $appointment = Appointment::where('confirmation_token', $token)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();
        $appointment->update(['status' => 'confirmed']);
        return view('tenant.confirm-appointment', compact('tenant', 'settings', 'appointment'));
    }
}
