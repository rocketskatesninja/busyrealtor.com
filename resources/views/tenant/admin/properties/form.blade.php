@extends('layouts.admin')
@section('title', isset($property) ? 'Edit Property' : 'Add Property')
@section('page-subtitle', isset($property) ? 'Update listing details' : 'Create a new listing')
@section('content')
@php $account = $tenant->slug; $isEdit = isset($property); @endphp
<div class="max-w-5xl mx-auto px-4">
    <form method="POST" enctype="multipart/form-data"
          action="{{ $isEdit ? route('tenant.admin.properties.update', [$account, $property->id]) : route('tenant.admin.properties.store', $account) }}"
          x-data="propertyForm()"
          class="space-y-6">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Tabs + Action Buttons --}}
        <div class="flex items-center justify-between gap-4">
            <div class="flex gap-1 bg-gray-100 p-1 rounded-xl">
                @foreach(['Basic Info' => 'basic', 'Details' => 'details', 'Location' => 'location', 'Media' => 'media'] as $label => $tab)
                <button type="button" @click="activeTab = '{{ $tab }}'"
                        :class="activeTab === '{{ $tab }}' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all">{{ $label }}</button>
                @endforeach
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                <a href="{{ route('tenant.admin.properties.index', $account) }}" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                <button type="submit" class="btn-primary inline-flex items-center gap-2 px-6 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ $isEdit ? 'Save Changes' : 'Create Property' }}
                </button>
            </div>
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
                        @foreach(['house'=>'House','condo'=>'Condo','townhouse'=>'Townhouse','land'=>'Land','commercial'=>'Commercial','multi_family'=>'Multi-Family'] as $v=>$l)
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
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Agent / Staff</label>
                    <select name="staff_member_id" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— None —</option>
                        @foreach($staffMembers as $sm)
                        <option value="{{ $sm->id }}" {{ old('staff_member_id', $property->staff_member_id ?? '') == $sm->id ? 'selected' : '' }}>{{ $sm->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">This person receives email notifications for inquiries on this property.</p>
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
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 2 <span class="text-gray-400 font-normal">(Apt, Suite, Unit)</span></label>
                    <input type="text" name="address_line_2" value="{{ old('address_line_2', $property->address_line_2 ?? '') }}" placeholder="Apt 4B, Suite 200, Unit 12..." class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <div class="grid grid-cols-4 gap-5">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                            <input type="text" name="city" value="{{ old('city', $property->city ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                            <select name="state" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="" {{ old('state', $property->state ?? '') == '' ? 'selected' : '' }}>Select State</option>
                            <option value="AL" {{ old('state', $property->state ?? '') == 'AL' ? 'selected' : '' }}>AL — Alabama</option>
                            <option value="AK" {{ old('state', $property->state ?? '') == 'AK' ? 'selected' : '' }}>AK — Alaska</option>
                            <option value="AZ" {{ old('state', $property->state ?? '') == 'AZ' ? 'selected' : '' }}>AZ — Arizona</option>
                            <option value="AR" {{ old('state', $property->state ?? '') == 'AR' ? 'selected' : '' }}>AR — Arkansas</option>
                            <option value="CA" {{ old('state', $property->state ?? '') == 'CA' ? 'selected' : '' }}>CA — California</option>
                            <option value="CO" {{ old('state', $property->state ?? '') == 'CO' ? 'selected' : '' }}>CO — Colorado</option>
                            <option value="CT" {{ old('state', $property->state ?? '') == 'CT' ? 'selected' : '' }}>CT — Connecticut</option>
                            <option value="DE" {{ old('state', $property->state ?? '') == 'DE' ? 'selected' : '' }}>DE — Delaware</option>
                            <option value="DC" {{ old('state', $property->state ?? '') == 'DC' ? 'selected' : '' }}>DC — District of Columbia</option>
                            <option value="FL" {{ old('state', $property->state ?? '') == 'FL' ? 'selected' : '' }}>FL — Florida</option>
                            <option value="GA" {{ old('state', $property->state ?? '') == 'GA' ? 'selected' : '' }}>GA — Georgia</option>
                            <option value="HI" {{ old('state', $property->state ?? '') == 'HI' ? 'selected' : '' }}>HI — Hawaii</option>
                            <option value="ID" {{ old('state', $property->state ?? '') == 'ID' ? 'selected' : '' }}>ID — Idaho</option>
                            <option value="IL" {{ old('state', $property->state ?? '') == 'IL' ? 'selected' : '' }}>IL — Illinois</option>
                            <option value="IN" {{ old('state', $property->state ?? '') == 'IN' ? 'selected' : '' }}>IN — Indiana</option>
                            <option value="IA" {{ old('state', $property->state ?? '') == 'IA' ? 'selected' : '' }}>IA — Iowa</option>
                            <option value="KS" {{ old('state', $property->state ?? '') == 'KS' ? 'selected' : '' }}>KS — Kansas</option>
                            <option value="KY" {{ old('state', $property->state ?? '') == 'KY' ? 'selected' : '' }}>KY — Kentucky</option>
                            <option value="LA" {{ old('state', $property->state ?? '') == 'LA' ? 'selected' : '' }}>LA — Louisiana</option>
                            <option value="ME" {{ old('state', $property->state ?? '') == 'ME' ? 'selected' : '' }}>ME — Maine</option>
                            <option value="MD" {{ old('state', $property->state ?? '') == 'MD' ? 'selected' : '' }}>MD — Maryland</option>
                            <option value="MA" {{ old('state', $property->state ?? '') == 'MA' ? 'selected' : '' }}>MA — Massachusetts</option>
                            <option value="MI" {{ old('state', $property->state ?? '') == 'MI' ? 'selected' : '' }}>MI — Michigan</option>
                            <option value="MN" {{ old('state', $property->state ?? '') == 'MN' ? 'selected' : '' }}>MN — Minnesota</option>
                            <option value="MS" {{ old('state', $property->state ?? '') == 'MS' ? 'selected' : '' }}>MS — Mississippi</option>
                            <option value="MO" {{ old('state', $property->state ?? '') == 'MO' ? 'selected' : '' }}>MO — Missouri</option>
                            <option value="MT" {{ old('state', $property->state ?? '') == 'MT' ? 'selected' : '' }}>MT — Montana</option>
                            <option value="NE" {{ old('state', $property->state ?? '') == 'NE' ? 'selected' : '' }}>NE — Nebraska</option>
                            <option value="NV" {{ old('state', $property->state ?? '') == 'NV' ? 'selected' : '' }}>NV — Nevada</option>
                            <option value="NH" {{ old('state', $property->state ?? '') == 'NH' ? 'selected' : '' }}>NH — New Hampshire</option>
                            <option value="NJ" {{ old('state', $property->state ?? '') == 'NJ' ? 'selected' : '' }}>NJ — New Jersey</option>
                            <option value="NM" {{ old('state', $property->state ?? '') == 'NM' ? 'selected' : '' }}>NM — New Mexico</option>
                            <option value="NY" {{ old('state', $property->state ?? '') == 'NY' ? 'selected' : '' }}>NY — New York</option>
                            <option value="NC" {{ old('state', $property->state ?? '') == 'NC' ? 'selected' : '' }}>NC — North Carolina</option>
                            <option value="ND" {{ old('state', $property->state ?? '') == 'ND' ? 'selected' : '' }}>ND — North Dakota</option>
                            <option value="OH" {{ old('state', $property->state ?? '') == 'OH' ? 'selected' : '' }}>OH — Ohio</option>
                            <option value="OK" {{ old('state', $property->state ?? '') == 'OK' ? 'selected' : '' }}>OK — Oklahoma</option>
                            <option value="OR" {{ old('state', $property->state ?? '') == 'OR' ? 'selected' : '' }}>OR — Oregon</option>
                            <option value="PA" {{ old('state', $property->state ?? '') == 'PA' ? 'selected' : '' }}>PA — Pennsylvania</option>
                            <option value="RI" {{ old('state', $property->state ?? '') == 'RI' ? 'selected' : '' }}>RI — Rhode Island</option>
                            <option value="SC" {{ old('state', $property->state ?? '') == 'SC' ? 'selected' : '' }}>SC — South Carolina</option>
                            <option value="SD" {{ old('state', $property->state ?? '') == 'SD' ? 'selected' : '' }}>SD — South Dakota</option>
                            <option value="TN" {{ old('state', $property->state ?? '') == 'TN' ? 'selected' : '' }}>TN — Tennessee</option>
                            <option value="TX" {{ old('state', $property->state ?? '') == 'TX' ? 'selected' : '' }}>TX — Texas</option>
                            <option value="UT" {{ old('state', $property->state ?? '') == 'UT' ? 'selected' : '' }}>UT — Utah</option>
                            <option value="VT" {{ old('state', $property->state ?? '') == 'VT' ? 'selected' : '' }}>VT — Vermont</option>
                            <option value="VA" {{ old('state', $property->state ?? '') == 'VA' ? 'selected' : '' }}>VA — Virginia</option>
                            <option value="WA" {{ old('state', $property->state ?? '') == 'WA' ? 'selected' : '' }}>WA — Washington</option>
                            <option value="WV" {{ old('state', $property->state ?? '') == 'WV' ? 'selected' : '' }}>WV — West Virginia</option>
                            <option value="WI" {{ old('state', $property->state ?? '') == 'WI' ? 'selected' : '' }}>WI — Wisconsin</option>
                            <option value="WY" {{ old('state', $property->state ?? '') == 'WY' ? 'selected' : '' }}>WY — Wyoming</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code</label>
                            <input type="text" name="zip" value="{{ old('zip', $property->zip ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
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

            {{-- EDIT: always show sortable grid (even if no photos yet) --}}
            @if($isEdit)
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-800">Photos</h3>
                <span class="text-xs text-gray-400 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    Drag to reorder &mdash; first photo is the main listing image
                </span>
            </div>
            <div class="grid grid-cols-3 md:grid-cols-6 gap-3 mb-4" id="image-grid">
                @foreach($property->images->sortBy('sort_order') as $img)
                <div class="relative group rounded-xl overflow-hidden aspect-square bg-gray-100 cursor-grab active:cursor-grabbing select-none"
                     data-id="{{ $img->id }}"
                     data-primary-url="{{ route('tenant.admin.api.property-images.primary', [$account, $img->id]) }}"
                     data-delete-url="{{ route('tenant.admin.api.property-images.destroy', [$account, $img->id]) }}">
                    <img src="{{ asset('storage/'.$img->image_path) }}" class="w-full h-full object-cover pointer-events-none">
                    <span class="main-badge absolute top-1.5 left-1.5 items-center gap-1 bg-emerald-500 text-white text-xs px-1.5 py-0.5 rounded-md font-semibold shadow"
                          style="display:{{ $loop->first ? 'inline-flex' : 'none' }}">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        Main
                    </span>
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                        <button type="button" onclick="deleteImage({{ $img->id }}, this)"
                                class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg transition-colors pointer-events-auto" title="Delete photo">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="absolute bottom-1 right-1 text-white/70 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 6a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm8-16a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4z"/></svg>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Upload zone --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ $isEdit ? 'Add More Photos' : 'Upload Photos' }}</label>
                <label for="images" id="upload-zone" class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center hover:border-blue-400 hover:bg-blue-50 transition-colors cursor-pointer">
                    <svg id="upload-icon" class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span id="upload-label" class="text-gray-500 text-sm">Click to select photos <span class="text-gray-400">(JPG, PNG, WebP — max 10MB each)</span></span>
                    {{-- Edit: no name → AJAX handles upload; Create: name="images[]" → form handles upload --}}
                    <input type="file" id="images" {{ $isEdit ? '' : 'name="images[]"' }} multiple accept="image/*" class="sr-only" onchange="handleFileSelect(this.files)">
                </label>

                {{-- CREATE only: sortable preview grid --}}
                @if(!$isEdit)
                <div class="flex items-center justify-between mt-4 mb-2" id="preview-header" style="display:none!important">
                    <h3 class="font-semibold text-gray-800 text-sm">Selected Photos</h3>
                    <span class="text-xs text-gray-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                        Drag to reorder &mdash; first photo will be the main listing image
                    </span>
                </div>
                <div class="grid grid-cols-3 md:grid-cols-6 gap-3" id="preview-grid"></div>
                @endif
            </div>
        </div>


    </form>
</div>
@endsection

@section('scripts')
// ─── Shared constants (injected from Blade) ───────────────────────────────
const _isEdit       = {{ $isEdit ? 'true' : 'false' }};
const _propertyId   = {{ $isEdit ? $property->id : 'null' }};
const _uploadUrl    = '{{ route("tenant.admin.api.property-images.store", $account) }}';
const _reorderUrlTpl= '{{ route("tenant.admin.api.property-images.reorder", [$account, "__ID__"]) }}';
const _csrf         = '{{ csrf_token() }}';

// ─── Alpine component ─────────────────────────────────────────────────────
function propertyForm() {
    return {
        activeTab: 'basic',
        generating: false,
        _sortableReady: false,
        init() {
            this.$watch('activeTab', (val) => {
                if (val === 'media' && !this._sortableReady) {
                    this._sortableReady = true;
                    this.$nextTick(() => {
                        if (_isEdit) {
                            // Edit: init sortable on the persistent grid
                            const grid = document.getElementById('image-grid');
                            if (grid && typeof Sortable !== 'undefined') {
                                refreshMainBadge();
                                Sortable.create(grid, {
                                    animation: 150, ghostClass: 'opacity-30', dragClass: 'shadow-xl',
                                    onEnd() { refreshMainBadge(); persistOrder(); }
                                });
                            }
                        } else {
                            // Create: init sortable on preview grid (may be empty initially)
                            initPreviewSortable();
                        }
                    });
                }
            });
            // Create: rebuild FileList in sorted order before form submit
            if (!_isEdit) {
                this.$el.closest('form').addEventListener('submit', function() {
                    const grid = document.getElementById('preview-grid');
                    const input = document.getElementById('images');
                    if (!grid || !input) return;
                    const dt = new DataTransfer();
                    grid.querySelectorAll('[data-preview]').forEach(card => { if (card._file) dt.items.add(card._file); });
                    input.files = dt.files;
                });
            }
        },
        async generateDescription() {
            const form = document.querySelector('form');
            const data = new FormData(form);
            this.generating = true;
            try {
                const res = await fetch('{{ route('tenant.admin.api.generate-description', $account) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrf },
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

// ─── File select handler — branches on edit vs create ────────────────────
function handleFileSelect(files) {
    if (_isEdit) {
        Array.from(files).forEach(file => uploadImmediate(file));
    } else {
        Array.from(files).forEach(file => addPreviewCard(file));
        // show header
        const hdr = document.getElementById('preview-header');
        if (hdr && document.querySelectorAll('#preview-grid [data-preview]').length > 0) {
            hdr.style.removeProperty('display');
        }
    }
    // reset input so same files can be re-selected
    document.getElementById('images').value = '';
}

// ─── EDIT: upload a single file immediately via AJAX ─────────────────────
function uploadImmediate(file) {
    const zone  = document.getElementById('upload-zone');
    const label = document.getElementById('upload-label');
    if (label) label.textContent = 'Uploading…';

    const fd = new FormData();
    fd.append('image', file);
    fd.append('property_id', _propertyId);

    fetch(_uploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': _csrf }, body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.id) {
                const deleteUrl  = '{{ route("tenant.admin.api.property-images.destroy", [$account, "__ID__"]) }}'.replace('__ID__', data.id);
                const primaryUrl = '{{ route("tenant.admin.api.property-images.primary", [$account, "__ID__"]) }}'.replace('__ID__', data.id);
                const card = buildGridCard(data.id, data.url, deleteUrl, primaryUrl);
                document.getElementById('image-grid').appendChild(card);
                refreshMainBadge();
                persistOrder();
            }
        })
        .finally(() => {
            if (label) label.innerHTML = 'Click to select photos <span class="text-gray-400">(JPG, PNG, WebP — max 10MB each)</span>';
        });
}

// ─── Build a grid card element (used after AJAX upload on edit) ───────────
function buildGridCard(id, url, deleteUrl, primaryUrl) {
    const card = document.createElement('div');
    card.className = 'relative group rounded-xl overflow-hidden aspect-square bg-gray-100 cursor-grab active:cursor-grabbing select-none';
    card.dataset.id         = id;
    card.dataset.deleteUrl  = deleteUrl;
    card.dataset.primaryUrl = primaryUrl;
    card.innerHTML = `
        <img src="${url}" class="w-full h-full object-cover pointer-events-none">
        <span class="main-badge absolute top-1.5 left-1.5 items-center gap-1 bg-emerald-500 text-white text-xs px-1.5 py-0.5 rounded-md font-semibold shadow" style="display:none">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            Main
        </span>
        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
            <button type="button" onclick="deleteImage(${id}, this)" class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg transition-colors pointer-events-auto" title="Delete photo">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="absolute bottom-1 right-1 text-white/70 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 6a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm8-16a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4z"/></svg>
        </div>`;
    return card;
}

// ─── CREATE: add a local preview card to #preview-grid ───────────────────
let _previewSortable = null;
function addPreviewCard(file) {
    const grid = document.getElementById('preview-grid');
    if (!grid) return;
    const reader = new FileReader();
    reader.onload = e => {
        const card = document.createElement('div');
        card.className = 'relative group rounded-xl overflow-hidden aspect-square bg-gray-100 cursor-grab active:cursor-grabbing select-none';
        card.dataset.preview = '1';
        card._file = file;
        card.innerHTML = `
            <img src="${e.target.result}" class="w-full h-full object-cover pointer-events-none">
            <span class="main-badge absolute top-1.5 left-1.5 items-center gap-1 bg-emerald-500 text-white text-xs px-1.5 py-0.5 rounded-md font-semibold shadow" style="display:none">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                Main
            </span>
            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                <button type="button" onclick="removePreview(this)" class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg transition-colors pointer-events-auto" title="Remove">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>`;
        grid.appendChild(card);
        refreshPreviewBadge();
        // show the header hint
        const hdr = document.getElementById('preview-header');
        if (hdr) hdr.style.removeProperty('display');
        // ensure sortable is active on preview grid
        if (!_previewSortable && typeof Sortable !== 'undefined') {
            _previewSortable = Sortable.create(grid, {
                animation: 150, ghostClass: 'opacity-30', dragClass: 'shadow-xl',
                onEnd() { refreshPreviewBadge(); }
            });
        }
    };
    reader.readAsDataURL(file);
}

function initPreviewSortable() {
    const grid = document.getElementById('preview-grid');
    if (grid && !_previewSortable && typeof Sortable !== 'undefined') {
        _previewSortable = Sortable.create(grid, {
            animation: 150, ghostClass: 'opacity-30', dragClass: 'shadow-xl',
            onEnd() { refreshPreviewBadge(); }
        });
    }
}

function removePreview(btn) {
    btn.closest('[data-preview]').remove();
    refreshPreviewBadge();
    const hdr = document.getElementById('preview-header');
    if (hdr && !document.querySelectorAll('#preview-grid [data-preview]').length) {
        hdr.style.setProperty('display', 'none', 'important');
    }
}

// ─── Badge helpers ────────────────────────────────────────────────────────
function refreshMainBadge() {
    document.querySelectorAll('#image-grid > [data-id]').forEach(function(card, i) {
        const b = card.querySelector('.main-badge');
        if (b) b.style.display = i === 0 ? 'inline-flex' : 'none';
    });
}
function refreshPreviewBadge() {
    document.querySelectorAll('#preview-grid > [data-preview]').forEach(function(card, i) {
        const b = card.querySelector('.main-badge');
        if (b) b.style.display = i === 0 ? 'inline-flex' : 'none';
    });
}

// ─── Persist sort order to server (edit only) ─────────────────────────────
function persistOrder() {
    document.querySelectorAll('#image-grid > [data-id]').forEach(function(card, i) {
        const id  = card.dataset.id;
        const url = _reorderUrlTpl.replace('__ID__', id);
        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': _csrf, 'Content-Type': 'application/json' }, body: JSON.stringify({ sort_order: i }) });
        if (i === 0) fetch(card.dataset.primaryUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': _csrf } });
    });
}

// ─── Delete a saved photo (edit only) ────────────────────────────────────
function deleteImage(id, btn) {
    if (!confirm('Delete this photo?')) return;
    const card = btn.closest('[data-id]');
    fetch(card.dataset.deleteUrl, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': _csrf } })
        .then(r => {
            if (r.ok) {
                card.remove();
                refreshMainBadge();
                persistOrder();
            }
        });
}
@endsection
