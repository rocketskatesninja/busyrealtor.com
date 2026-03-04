<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\SiteSettings;
use App\Models\LegalPage;
use App\Models\Appointment;
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

        $query = Property::query();
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

    public function favicon($account)
    {
        $tenant   = \App\Models\Tenant::where('slug', $account)->firstOrFail();
        $settings = \App\Models\SiteSettings::where('tenant_id', $tenant->id)->first();

        $presets = [
            'house' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="PCOLOR" d="M16 3 1 16h4v13h8v-8h6v8h8V16h4z"/></svg>',
            'key' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="10" cy="13" r="7" fill="none" stroke="PCOLOR" stroke-width="3.5"/><rect x="16" y="11.5" width="15" height="3" rx="1" fill="PCOLOR"/><rect x="24" y="14.5" width="3" height="5" rx="1" fill="PCOLOR"/><rect x="18.5" y="14.5" width="3" height="4" rx="1" fill="PCOLOR"/></svg>',
            'pin' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="PCOLOR" d="M16 1C9.9 1 5 5.9 5 12c0 8.5 11 19 11 19s11-10.5 11-19c0-6.1-4.9-11-11-11zm0 15a4 4 0 110-8 4 4 0 010 8z"/></svg>',
            'building' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="3" y="6" width="14" height="25" rx="1" fill="PCOLOR"/><rect x="19" y="12" width="10" height="19" rx="1" fill="PCOLOR" opacity=".75"/><rect x="6" y="10" width="3" height="3" rx=".5" fill="white" opacity=".8"/><rect x="11" y="10" width="3" height="3" rx=".5" fill="white" opacity=".8"/><rect x="6" y="16" width="3" height="3" rx=".5" fill="white" opacity=".8"/><rect x="11" y="16" width="3" height="3" rx=".5" fill="white" opacity=".8"/><rect x="22" y="16" width="4" height="3" rx=".5" fill="white" opacity=".8"/><rect x="6" y="22" width="3" height="3" rx=".5" fill="white" opacity=".8"/><rect x="11" y="22" width="3" height="3" rx=".5" fill="white" opacity=".8"/><rect x="22" y="22" width="4" height="3" rx=".5" fill="white" opacity=".8"/></svg>',
            'shield' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="PCOLOR" d="M16 2 3 8v9c0 7.8 5.6 13 13 15 7.4-2 13-7.2 13-15V8z"/><polyline points="10,16 14,20 22,12" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'star' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><polygon fill="PCOLOR" points="16,2 20.2,11.5 31,13 23.5,20.3 25.4,31 16,26 6.6,31 8.5,20.3 1,13 11.8,11.5"/></svg>',
            'search' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="13" cy="13" r="8" fill="none" stroke="PCOLOR" stroke-width="3.5"/><line x1="19.5" y1="19.5" x2="28" y2="28" stroke="PCOLOR" stroke-width="3.5" stroke-linecap="round"/></svg>',
            'door' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="6" y="2" width="20" height="28" rx="1.5" fill="PCOLOR" opacity=".3"/><rect x="8" y="2" width="16" height="26" rx="1" fill="PCOLOR"/><circle cx="20.5" cy="16" r="2" fill="white" opacity=".85"/></svg>',
            'chart' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="2" y="16" width="6" height="14" rx="1" fill="PCOLOR"/><rect x="10" y="10" width="6" height="20" rx="1" fill="PCOLOR"/><rect x="18" y="5" width="6" height="25" rx="1" fill="PCOLOR"/><rect x="26" y="12" width="4" height="18" rx="1" fill="PCOLOR" opacity=".75"/></svg>',
            'leaf' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="PCOLOR" d="M6 26C7 14 16 5 28 4 27 16 18 25 6 26z"/><path fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" opacity=".6" d="M7 25Q18 14 27 5"/></svg>',
            'fence' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="PCOLOR" d="M2 9l3-5 3 5v15H2zM10 9l3-5 3 5v15h-6zM18 9l3-5 3 5v15h-6zM25 9l3-5 3 5v15h-6z"/><rect x="1" y="13" width="30" height="3" rx="1" fill="PCOLOR"/><rect x="1" y="19" width="30" height="3" rx="1" fill="PCOLOR"/></svg>',
            'garage' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="PCOLOR" d="M16 2L3 11v19h26V11z"/><rect x="8" y="15" width="16" height="15" rx="1" fill="white" opacity=".9"/><rect x="8" y="18.5" width="16" height="2" rx=".5" fill="PCOLOR" opacity=".35"/><rect x="8" y="22.5" width="16" height="2" rx=".5" fill="PCOLOR" opacity=".35"/><rect x="8" y="26.5" width="16" height="2" rx=".5" fill="PCOLOR" opacity=".35"/></svg>',
            'sofa' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="4" y="10" width="24" height="11" rx="2" fill="PCOLOR"/><rect x="1" y="17" width="30" height="8" rx="2" fill="PCOLOR" opacity=".8"/><rect x="1" y="14" width="5" height="11" rx="2" fill="PCOLOR"/><rect x="26" y="14" width="5" height="11" rx="2" fill="PCOLOR"/><rect x="5" y="25" width="3" height="5" rx="1" fill="PCOLOR"/><rect x="24" y="25" width="3" height="5" rx="1" fill="PCOLOR"/></svg>',
            'compass' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="16" cy="16" r="13" fill="none" stroke="PCOLOR" stroke-width="2.5"/><polygon fill="PCOLOR" points="16,5 19,16 16,14 13,16"/><polygon fill="PCOLOR" points="16,27 13,16 16,18 19,16" opacity=".35"/><circle cx="16" cy="16" r="2" fill="PCOLOR"/></svg>',
            'house_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="PCOLOR" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" d="M16 4 2 15.5h3V28h8v-7h6v7h8V15.5h3z"/></svg>',
            'key_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="10" cy="13" r="7" fill="none" stroke="PCOLOR" stroke-width="2"/><line x1="17" y1="13" x2="30" y2="13" stroke="PCOLOR" stroke-width="2" stroke-linecap="round"/><line x1="26" y1="13" x2="26" y2="18" stroke="PCOLOR" stroke-width="2" stroke-linecap="round"/><line x1="22" y1="13" x2="22" y2="17" stroke="PCOLOR" stroke-width="2" stroke-linecap="round"/></svg>',
            'pin_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="PCOLOR" stroke-width="2" stroke-linejoin="round" d="M16 2C10 2 5 7 5 13c0 8.5 11 18 11 18s11-9.5 11-18c0-6-5-11-11-11z"/><circle cx="16" cy="13" r="3.5" fill="none" stroke="PCOLOR" stroke-width="2"/></svg>',
            'building_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="3" y="6" width="14" height="25" rx="1" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="19" y="12" width="10" height="19" rx="1" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="6" y="10" width="3" height="3" rx=".5" fill="PCOLOR"/><rect x="11" y="10" width="3" height="3" rx=".5" fill="PCOLOR"/><rect x="6" y="16" width="3" height="3" rx=".5" fill="PCOLOR"/><rect x="11" y="16" width="3" height="3" rx=".5" fill="PCOLOR"/><rect x="22" y="16" width="4" height="3" rx=".5" fill="PCOLOR"/></svg>',
            'shield_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="PCOLOR" stroke-width="2" stroke-linejoin="round" d="M16 3 3 9v9c0 7.8 5.6 12 13 14 7.4-2 13-6.2 13-14V9z"/><polyline points="10,16 14,20 22,12" fill="none" stroke="PCOLOR" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'star_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><polygon fill="none" stroke="PCOLOR" stroke-width="2" stroke-linejoin="round" points="16,2 20.2,11.5 31,13 23.5,20.3 25.4,31 16,26 6.6,31 8.5,20.3 1,13 11.8,11.5"/></svg>',
            'search_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="13" cy="13" r="8" fill="none" stroke="PCOLOR" stroke-width="2.5"/><line x1="19.5" y1="19.5" x2="28" y2="28" stroke="PCOLOR" stroke-width="2.5" stroke-linecap="round"/></svg>',
            'door_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="7" y="2" width="18" height="28" rx="1.5" fill="none" stroke="PCOLOR" stroke-width="2"/><circle cx="20.5" cy="16" r="2" fill="PCOLOR"/></svg>',
            'chart_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="2" y="16" width="6" height="14" rx="1" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="10" y="10" width="6" height="20" rx="1" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="18" y="5" width="6" height="25" rx="1" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="26" y="12" width="4" height="18" rx="1" fill="none" stroke="PCOLOR" stroke-width="2"/></svg>',
            'leaf_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="PCOLOR" stroke-width="2" stroke-linejoin="round" d="M6 26C7 14 16 5 28 4 27 16 18 25 6 26z"/><path fill="none" stroke="PCOLOR" stroke-width="1.5" stroke-linecap="round" d="M7 25Q18 14 27 5"/></svg>',
            'fence_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="PCOLOR" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M2 24V11l3-5 3 5v13M10 24V11l3-5 3 5v13M18 24V11l3-5 3 5v13M25 24V11l3-5 3 5v13"/><line x1="1" y1="14.5" x2="31" y2="14.5" stroke="PCOLOR" stroke-width="2" stroke-linecap="round"/><line x1="1" y1="20.5" x2="31" y2="20.5" stroke="PCOLOR" stroke-width="2" stroke-linecap="round"/></svg>',
            'garage_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="none" stroke="PCOLOR" stroke-width="2" stroke-linejoin="round" d="M16 2L3 11v19h26V11z"/><rect x="8" y="15" width="16" height="15" rx="1" fill="none" stroke="PCOLOR" stroke-width="1.5"/><line x1="8" y1="19" x2="24" y2="19" stroke="PCOLOR" stroke-width="1.5" stroke-linecap="round"/><line x1="8" y1="23" x2="24" y2="23" stroke="PCOLOR" stroke-width="1.5" stroke-linecap="round"/><line x1="8" y1="27" x2="24" y2="27" stroke="PCOLOR" stroke-width="1.5" stroke-linecap="round"/></svg>',
            'sofa_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect x="4" y="10" width="24" height="11" rx="2" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="1" y="17" width="30" height="7" rx="2" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="1" y="14" width="5" height="10" rx="2" fill="none" stroke="PCOLOR" stroke-width="2"/><rect x="26" y="14" width="5" height="10" rx="2" fill="none" stroke="PCOLOR" stroke-width="2"/><line x1="5" y1="24" x2="5" y2="28" stroke="PCOLOR" stroke-width="2" stroke-linecap="round"/><line x1="27" y1="24" x2="27" y2="28" stroke="PCOLOR" stroke-width="2" stroke-linecap="round"/></svg>',
            'compass_outline' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="16" cy="16" r="13" fill="none" stroke="PCOLOR" stroke-width="2"/><path fill="none" stroke="PCOLOR" stroke-width="1.5" stroke-linejoin="round" d="M16 5l3 11-3-2-3 2z"/><path fill="none" stroke="PCOLOR" stroke-width="1.5" stroke-linejoin="round" d="M16 27l-3-11 3 2 3-2z"/><circle cx="16" cy="16" r="2" fill="PCOLOR"/></svg>',
        ];

        $preset = $settings->favicon_preset ?? null;
        if (!$preset || !isset($presets[$preset])) {
            abort(404);
        }

        $pcolor = $settings->primary_color ?? '#3B82F6';
        $svg    = str_replace('PCOLOR', $pcolor, $presets[$preset]);

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
