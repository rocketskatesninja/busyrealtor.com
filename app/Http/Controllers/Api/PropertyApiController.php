<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyApiController extends Controller
{
    public function index($account, Request $request)
    {
        $query = Property::with('images')
            ->whereNotNull('latitude')->whereNotNull('longitude');

        if ($request->type)      $query->where('property_type', $request->type);
        if ($request->status)    $query->where('listing_status', $request->status);
        if ($request->min_price) $query->where('price', '>=', $request->min_price);
        if ($request->max_price) $query->where('price', '<=', $request->max_price);

        $properties = $query->get()->map(fn($p) => [
            'id'         => $p->id,
            'title'      => $p->title,
            'address'    => $p->address,
            'price'      => $p->price,
            'lat'        => $p->latitude,
            'lng'        => $p->longitude,
            'type'       => $p->property_type,
            'status'     => $p->listing_status,
            'bedrooms'   => $p->bedrooms,
            'bathrooms'  => $p->bathrooms,
            'sqft'       => $p->sqft,
            'image'      => $p->primaryImage ? asset('storage/' . $p->primaryImage->image_path) : null,
            'url'        => url(request()->segment(1) . '/property/' . $p->id),
        ]);

        return response()->json($properties);
    }
}
