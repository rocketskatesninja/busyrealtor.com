<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use App\Jobs\PostPropertyToSocial;
use App\Models\StaffMember;

class PropertyController extends Controller
{
    public function index($account, Request $request)
    {
        $tenant = app('tenant');
        $query  = Property::with('images');

        if ($request->search)  $query->where(function($q) use ($request) { $q->where('title','like',"%{$request->search}%")->orWhere('address_street','like',"%{$request->search}%"); });
        if ($request->type)    $query->where('property_type', $request->type);
        if ($request->status)  $query->where('listing_status', $request->status);

        $sort = $request->sort ?? 'newest';
        match($sort) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'views'      => $query->orderBy('view_count', 'desc'),
            default      => $query->orderBy('created_at', 'desc'),
        };

        $properties = $query->paginate(20)->withQueryString();
        return view('tenant.admin.properties.index', compact('tenant', 'properties'));
    }

    public function create($account)
    {
        $tenant        = app('tenant');
        $propertyLimit = $tenant->propertyLimit();
        $propertyCount = $propertyLimit !== null ? Property::count() : null;
        $staffMembers  = StaffMember::where('tenant_id', $tenant->id)->orderBy('name')->get();
        return view('tenant.admin.properties.form', compact('tenant', 'propertyLimit', 'propertyCount', 'staffMembers'));
    }

    public function store($account, Request $request)
    {
        $tenant = app('tenant');
        $limit  = $tenant->propertyLimit();
        if ($limit !== null && Property::count() >= $limit) {
            return back()->with('error', "Your Starter plan allows up to {$limit} active listings. Upgrade to Pro for unlimited listings.");
        }
        $data = $this->validateAndPrepare($request);
        $property = Property::create($data);
        $this->handleImages($request, $property);
        logActivity('created', "Created property: {$property->title}", $property);
        if (($data['listing_status'] ?? '') === 'active') {
            if ($tenant->isPro()) {
                PostPropertyToSocial::dispatch($property->fresh()->load('images', 'tenant'), 'new_listing');
            }
        }
        if ($request->expectsJson()) {
            return response()->json([
                'id' => $property->id,
                'redirect' => route('tenant.admin.properties.index', ['account' => app('tenant')->slug]),
            ]);
        }
        return redirect()->route('tenant.admin.properties.index', ['account' => app('tenant')->slug])
            ->with('success', 'Property created successfully.');
    }

    public function edit($account, $id)
    {
        $tenant       = app('tenant');
        $property     = Property::with('images')->where('tenant_id', $tenant->id)->findOrFail($id);
        $staffMembers = StaffMember::where('tenant_id', $tenant->id)->orderBy('name')->get();
        return view('tenant.admin.properties.form', compact('tenant', 'property', 'staffMembers'));
    }

    public function update($account, Request $request, $id)
    {
        $tenant    = app('tenant');
        $property  = Property::where('tenant_id', $tenant->id)->findOrFail($id);
        $oldStatus = $property->listing_status;
        $data      = $this->validateAndPrepare($request);
        $property->update($data);
        $this->handleImages($request, $property);
        logActivity('updated', "Updated property: {$property->title}", $property);
        if ($oldStatus !== $data['listing_status']) {
            if ($data['listing_status'] === 'active') {
                if ($tenant->isPro()) {
                    PostPropertyToSocial::dispatch($property->fresh()->load('images', 'tenant'), 'new_listing');
                }
            } elseif ($data['listing_status'] === 'sold') {
                if ($tenant->isPro()) {
                    PostPropertyToSocial::dispatch($property->fresh()->load('images', 'tenant'), 'sold');
                }
            }
        }
        return redirect()->route('tenant.admin.properties.index', ['account' => app('tenant')->slug])
            ->with('success', 'Property updated.');
    }

    public function destroy($account, $id)
    {
        $tenant   = app('tenant');
        $property = Property::where('tenant_id', $tenant->id)->findOrFail($id);
        logActivity('deleted', "Deleted property: {$property->title}", $property);
        $property->delete();
        return redirect()->route('tenant.admin.properties.index', ['account' => app('tenant')->slug])
            ->with('success', 'Property deleted.');
    }

    private function validateAndPrepare(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:300',
            'listing_status' => 'required|in:active,pending,sold,off-market,featured,withdrawn',
            'property_type'  => 'required|string',
            'price'          => 'nullable|numeric',
            'images'         => 'nullable|array|max:20',
            'images.*'       => 'image|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'bedrooms'       => 'nullable|integer|min:0|max:99',
            'bathrooms'      => 'nullable|numeric|min:0|max:99',
            'half_baths'     => 'nullable|integer|min:0|max:99',
            'sqft'           => 'nullable|integer|min:0',
        ]);

        $tenant = app('tenant');
        return [
            'tenant_id'       => $tenant->id,
            'title'           => $request->title,
            'listing_status'  => $request->listing_status,
            'property_type'   => $request->property_type,
            'price'           => $request->price,
            'address_street'  => $request->address,
            'address_line_2'  => $request->address_line_2,
            'address_city'    => $request->city,
            'address_state'   => $request->state,
            'address_zip'     => $request->zip,
            'latitude'        => $request->latitude,
            'longitude'       => $request->longitude,
            'bedrooms'        => $request->bedrooms,
            'bathrooms'       => $request->bathrooms,
            'half_baths'      => $request->half_baths,
            'square_feet'     => $request->sqft,
            'lot_size'        => $request->lot_size,
            'year_built'      => $request->year_built,
            'garage'          => $request->garage_spaces ?? 0,
            'hoa_fee'         => $request->hoa_fees,
            'description'     => $request->description,
            'mls_number'      => $request->mls_number,
            'virtual_tour_url'=> $request->virtual_tour_url,
            'amenities'       => $request->amenities ?? [],
            'is_featured'     => $request->boolean('is_featured'),
            'staff_member_id' => $request->staff_member_id ?: null,
        ];
    }

    private function handleImages(Request $request, Property $property)
    {
        if (!$request->hasFile('images')) return;

        $tenant  = app('tenant');
        $dir     = "tenants/{$tenant->id}/properties";
        Storage::disk('public')->makeDirectory($dir);

        foreach ($request->file('images') as $i => $file) {
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $path     = $dir . '/' . $filename;
            $img      = Image::read($file)->scale(width: 1200);
            Storage::disk('public')->put($path, $img->toJpeg(85));

            $existingCount = $property->images()->count();
            $isPrimary     = ($i === 0 && $existingCount === 0);
            PropertyImage::create([
                'property_id' => $property->id,
                'tenant_id'   => $tenant->id,
                'image_url'   => $path,
                'sort_order'  => $existingCount,
                'is_primary'  => $isPrimary,
            ]);
        }
    }
}
