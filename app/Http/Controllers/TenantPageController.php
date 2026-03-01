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
        $property = Property::with('images')->findOrFail($id);

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

    public function confirmAppointment($account, $token)
    {
        $tenant      = app('tenant');
        $settings    = $this->getSettings();
        $appointment = Appointment::where('confirmation_token', $token)->firstOrFail();
        $appointment->update(['status' => 'confirmed']);
        return view('tenant.confirm-appointment', compact('tenant', 'settings', 'appointment'));
    }
}
