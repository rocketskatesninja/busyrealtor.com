@extends('layouts.admin')
@section('title', isset($property) ? 'Edit Property' : 'Add Property')
@section('page-subtitle', isset($property) ? 'Update listing details' : 'Create a new listing')
@section('content')
@php $account = $tenant->slug; $isEdit = isset($property); @endphp
<div class="max-w-5xl mx-auto px-4">
    <div class="mb-6">
        <a href="{{ route('tenant.admin.properties.index', $account) }}" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-gray-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Properties
        </a>
    </div>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $isEdit ? route('tenant.admin.properties.update', [$account, $property->id]) : route('tenant.admin.properties.store', $account) }}"
          x-data="propertyForm()"
          class="space-y-6">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Tabs --}}
        <div class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit">
            @foreach(['Basic Info' => 'basic', 'Details' => 'details', 'Location' => 'location', 'Media' => 'media'] as $label => $tab)
            <button type="button" @click="activeTab = '{{ $tab }}'"
                    :class="activeTab === '{{ $tab }}' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all">{{ $label }}</button>
            @endforeach
        </div>

        {{-- Basic Info Tab --}}
        <div x-show="activeTab === 'basic'" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Property Title *</label>
                    <input type="text" name="title" value="{{ old('title', $property->title ?? '') }}" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Listing Status *</label>
                    <select name="listing_status" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(['active'=>'Active','pending'=>'Pending','sold'=>'Sold','off-market'=>'Off Market'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('listing_status', $property->listing_status ?? 'active') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Property Type *</label>
                    <select name="property_type" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(['house'=>'House','condo'=>'Condo','townhouse'=>'Townhouse','land'=>'Land','commercial'=>'Commercial','multi-family'=>'Multi-Family'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('property_type', $property->property_type ?? '') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-gray-500 text-sm">$</span>
                        <input type="number" name="price" value="{{ old('price', $property->price ?? '') }}" class="w-full border border-gray-200 rounded-lg pl-7 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">HOA Fees ($/mo)</label>
                    <input type="number" name="hoa_fees" value="{{ old('hoa_fees', $property->hoa_fees ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <button type="button" @click="generateDescription()" :disabled="generating"
                                class="text-xs px-3 py-1.5 rounded-lg font-medium transition flex items-center gap-1.5"
                                style="background-color: rgba(var(--primary-rgb), 0.1); color: var(--primary)">
                            <svg class="w-3.5 h-3.5" :class="generating ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span x-text="generating ? 'Generating...' : 'Generate with AI'"></span>
                        </button>
                    </div>
                    <textarea id="description" name="description" rows="5" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('description', $property->description ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">MLS Number</label>
                    <input type="text" name="mls_number" value="{{ old('mls_number', $property->mls_number ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Virtual Tour URL</label>
                    <input type="url" name="virtual_tour_url" value="{{ old('virtual_tour_url', $property->virtual_tour_url ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_featured" id="is_featured" class="rounded" {{ old('is_featured', $property->is_featured ?? false) ? 'checked' : '' }}>
                    <label for="is_featured" class="text-sm font-medium text-gray-700">Featured on Homepage</label>
                </div>
            </div>
        </div>

        {{-- Details Tab --}}
        <div x-show="activeTab === 'details'" x-cloak class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                @foreach(['bedrooms'=>'Bedrooms','bathrooms'=>'Bathrooms','half_baths'=>'Half Baths','sqft'=>'Sq Ft','lot_size'=>'Lot Size','year_built'=>'Year Built','garage_spaces'=>'Garage Spaces'] as $field => $label)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                    <input type="number" name="{{ $field }}" value="{{ old($field, $property->$field ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                @endforeach
            </div>
            <div class="mt-5">
                <label class="block text-sm font-medium text-gray-700 mb-3">Amenities</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    @foreach(['Pool','Garage','Basement','Fireplace','Gym','Spa','Garden','Patio','Deck','Balcony','Ocean View','Mountain View','City View','Waterfront','Gated Community','Security System','Smart Home','Solar Panels','EV Charging','Hardwood Floors'] as $amenity)
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" name="amenities[]" value="{{ $amenity }}" class="rounded"
                               {{ in_array($amenity, old('amenities', $property->amenities ?? [])) ? 'checked' : '' }}>
                        {{ $amenity }}
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Location Tab --}}
        <div x-show="activeTab === 'location'" x-cloak class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                    <input type="text" name="address" value="{{ old('address', $property->address ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" name="city" value="{{ old('city', $property->city ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <input type="text" name="state" value="{{ old('state', $property->state ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code</label>
                    <input type="text" name="zip" value="{{ old('zip', $property->zip ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                    <input type="number" step="any" name="latitude" value="{{ old('latitude', $property->latitude ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                    <input type="number" step="any" name="longitude" value="{{ old('longitude', $property->longitude ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        {{-- Media Tab --}}
        <div x-show="activeTab === 'media'" x-cloak class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            @if($isEdit && $property->images->count())
            <h3 class="font-semibold text-gray-800 mb-4">Current Images (drag to reorder)</h3>
            <div class="grid grid-cols-3 md:grid-cols-6 gap-3 mb-6" id="image-grid">
                @foreach($property->images->sortBy('sort_order') as $img)
                <div class="relative group rounded-xl overflow-hidden aspect-square bg-gray-100" data-id="{{ $img->id }}">
                    <img src="{{ asset('storage/'.$img->image_path) }}" class="w-full h-full object-cover">
                    @if($img->is_primary) <span class="absolute top-1 left-1 bg-blue-500 text-white text-xs px-1.5 py-0.5 rounded font-medium">Primary</span> @endif
                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        @if(!$img->is_primary)
                        <button type="button" onclick="setPrimary({{ $img->id }}, '{{ route('tenant.admin.api.property-images.primary', [$account, $img->id]) }}')" class="bg-blue-500 text-white text-xs px-2 py-1 rounded font-medium">Primary</button>
                        @endif
                        <button type="button" onclick="deleteImage({{ $img->id }}, '{{ route('tenant.admin.api.property-images.destroy', [$account, $img->id]) }}')" class="bg-red-500 text-white p-1 rounded">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ $isEdit ? 'Add More Images' : 'Upload Images' }}</label>
                <div class="border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center hover:border-blue-400 transition-colors cursor-pointer" onclick="document.getElementById('images').click()">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p class="text-gray-500 text-sm">Click to upload images (JPG, PNG, max 10MB each)</p>
                    <input type="file" id="images" name="images[]" multiple accept="image/*" class="hidden" onchange="previewImages(this)">
                </div>
                <div id="image-previews" class="grid grid-cols-4 md:grid-cols-8 gap-2 mt-3"></div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('tenant.admin.properties.index', $account) }}" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Cancel</a>
            <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">
                {{ $isEdit ? 'Save Changes' : 'Create Property' }}
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
function propertyForm() {
    return {
        activeTab: 'basic',
        generating: false,
        async generateDescription() {
            const form = document.querySelector('form');
            const data = new FormData(form);
            this.generating = true;
            try {
                const res = await fetch('{{ route('tenant.admin.api.generate-description', $account) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({
                        title: data.get('title'), property_type: data.get('property_type'),
                        price: data.get('price'), bedrooms: data.get('bedrooms'),
                        bathrooms: data.get('bathrooms'), sqft: data.get('sqft'),
                        address: data.get('address'), city: data.get('city'), state: data.get('state'),
                        amenities: [...document.querySelectorAll('input[name="amenities[]"]:checked')].map(i=>i.value).join(', ')
                    })
                });
                const d = await res.json();
                if (d.description) document.getElementById('description').value = d.description;
            } catch(e) { alert('AI generation failed. Please try again.'); }
            this.generating = false;
        }
    };
}
function previewImages(input) {
    const container = document.getElementById('image-previews');
    container.innerHTML = '';
    for (const file of input.files) {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'aspect-square rounded-lg overflow-hidden bg-gray-100';
            div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    }
}
function setPrimary(id, url) {
    fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }).then(() => location.reload());
}
function deleteImage(id, url) {
    if (!confirm('Delete this image?')) return;
    fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }).then(() => location.reload());
}
@endsection
