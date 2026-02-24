<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class PropertyImagesController extends Controller
{
    private function ownedProperty(int $propertyId): Property
    {
        $tenant = app('tenant');
        return Property::where('tenant_id', $tenant->id)->findOrFail($propertyId);
    }

    private function ownedImage(int $imageId): PropertyImage
    {
        $tenant = app('tenant');
        $image  = PropertyImage::findOrFail($imageId);
        if ($image->tenant_id !== $tenant->id) abort(403);
        return $image;
    }

    public function index($account, $id)
    {
        $property = $this->ownedProperty((int) $id);
        $images = PropertyImage::where('property_id', $property->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($i) => [
                'id'         => $i->id,
                'url'        => asset('storage/' . $i->image_url),
                'is_primary' => $i->is_primary,
                'sort_order' => $i->sort_order,
            ]);
        return response()->json($images);
    }

    public function store($account, Request $request)
    {
        $request->validate(['image' => 'required|image|max:10240', 'property_id' => 'required|integer']);
        $tenant   = app('tenant');
        $property = $this->ownedProperty((int) $request->property_id);
        $dir      = "tenants/{$tenant->id}/properties";
        Storage::disk('public')->makeDirectory($dir);
        $filename  = uniqid() . '.jpg';
        $path      = $dir . '/' . $filename;
        $img       = Image::read($request->file('image'))->scale(width: 1200);
        Storage::disk('public')->put($path, $img->toJpeg(85));
        $isPrimary = $property->images()->count() === 0;
        $image = PropertyImage::create([
            'property_id' => $property->id,
            'tenant_id'   => $tenant->id,
            'image_url'   => $path,
            'sort_order'  => $property->images()->count(),
            'is_primary'  => $isPrimary,
        ]);
        return response()->json(['id' => $image->id, 'url' => asset('storage/' . $path), 'is_primary' => $isPrimary]);
    }

    public function setPrimary($account, $id)
    {
        $image = $this->ownedImage((int) $id);
        PropertyImage::where('property_id', $image->property_id)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);
        return response()->json(['success' => true]);
    }

    public function reorder($account, Request $request, $id)
    {
        $image = $this->ownedImage((int) $id);
        $image->update(['sort_order' => (int) $request->sort_order]);
        return response()->json(['success' => true]);
    }

    public function destroy($account, $id)
    {
        $image = $this->ownedImage((int) $id);
        Storage::disk('public')->delete($image->image_url);
        $image->delete();
        return response()->json(['success' => true]);
    }
}
