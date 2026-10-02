@extends('layouts.admin')
@section('title', isset($property) ? 'Edit Property' : 'Add Property')
@section('page-subtitle', isset($property) ? 'Update listing details' : 'Create a new listing')
@section('foot')
@vite('resources/js/property-form.js')
@endsection

@section('content')
@php
$account = $tenant->slug;
$isEdit = isset($property);
$formConfig = [
    'isEdit' => $isEdit,
    'propertyId' => $isEdit ? $property->id : null,
    'uploadUrl' => route('tenant.admin.api.property-images.store', $account),
    'reorderUrlTpl' => route('tenant.admin.api.property-images.reorder', [$account, '__ID__']),
    'deleteUrlTpl' => route('tenant.admin.api.property-images.destroy', [$account, '__ID__']),
    'primaryUrlTpl' => route('tenant.admin.api.property-images.primary', [$account, '__ID__']),
];
@endphp
<div class="max-w-5xl mx-auto px-4">
    <form method="POST" enctype="multipart/form-data"
          action="{{ $isEdit ? route('tenant.admin.properties.update', [$account, $property->id]) : route('tenant.admin.properties.store', $account) }}"
          id="property-form"
          data-config='@json($formConfig, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG)'
          x-data="propertyForm"
          @submit="submitCreate($event)"
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
                    <x-icon name="check" class="w-4 h-4" />
                    {{ $isEdit ? 'Save Changes' : 'Create Property' }}
                </button>
            </div>
        </div>

        {{-- Basic Info Tab --}}
        <div x-show="activeTab === 'basic'" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label for="f-title" class="block text-sm font-medium text-gray-700 mb-1">Property Title *</label>
                    <input type="text" id="f-title" name="title" value="{{ old('title', $property->title ?? '') }}" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label for="f-listing_status" class="block text-sm font-medium text-gray-700 mb-1">Listing Status *</label>
                    <select id="f-listing_status" name="listing_status" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                        @foreach(['active'=>'Active','pending'=>'Pending','sold'=>'Sold','off-market'=>'Off Market','withdrawn'=>'Withdrawn'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('listing_status', $property->listing_status ?? 'active') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="f-property_type" class="block text-sm font-medium text-gray-700 mb-1">Property Type *</label>
                    <select id="f-property_type" name="property_type" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                        @foreach(['house'=>'House','condo'=>'Condo','townhouse'=>'Townhouse','land'=>'Land','commercial'=>'Commercial','multi_family'=>'Multi-Family'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('property_type', $property->property_type ?? '') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-gray-500 text-sm">$</span>
                        <input type="number" name="price" value="{{ old('price', $property->price ?? '') }}" class="w-full border border-gray-200 rounded-lg pl-7 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                    </div>
                </div>
                <div>
                    <label for="f-hoa_fees" class="block text-sm font-medium text-gray-700 mb-1">HOA Fees ($/mo)</label>
                    <input type="number" id="f-hoa_fees" name="hoa_fees" value="{{ old('hoa_fees', $property->hoa_fees ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div class="md:col-span-2">
                    <div class="mb-1">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    </div>
                    <textarea id="description" name="description" rows="5" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)] resize-none">{{ old('description', $property->description ?? '') }}</textarea>
                </div>
                <div>
                    <label for="f-mls_number" class="block text-sm font-medium text-gray-700 mb-1">MLS Number</label>
                    <input type="text" id="f-mls_number" name="mls_number" value="{{ old('mls_number', $property->mls_number ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label for="f-virtual_tour_url" class="block text-sm font-medium text-gray-700 mb-1">Virtual Tour URL</label>
                    <input type="url" id="f-virtual_tour_url" name="virtual_tour_url" value="{{ old('virtual_tour_url', $property->virtual_tour_url ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label for="f-staff_member_id" class="block text-sm font-medium text-gray-700 mb-1">Assigned Agent / Staff</label>
                    <select id="f-staff_member_id" name="staff_member_id" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
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
                    <input type="number" name="{{ $field }}" value="{{ old($field, $property->$field ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
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
                    <label for="f-address" class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                    <input type="text" id="f-address" name="address" value="{{ old('address', $property->address ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div class="md:col-span-2">
                    <label for="f-address_line_2" class="block text-sm font-medium text-gray-700 mb-1">Address Line 2 <span class="text-gray-400 font-normal">(Apt, Suite, Unit)</span></label>
                    <input type="text" id="f-address_line_2" name="address_line_2" value="{{ old('address_line_2', $property->address_line_2 ?? '') }}" placeholder="Apt 4B, Suite 200, Unit 12..." class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div class="md:col-span-2">
                    <div class="grid grid-cols-4 gap-5">
                        <div class="col-span-2">
                            <label for="f-city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                            <input type="text" id="f-city" name="city" value="{{ old('city', $property->city ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                        </div>
                        <div>
                            <label for="f-state" class="block text-sm font-medium text-gray-700 mb-1">State</label>
                            <select id="f-state" name="state" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)] bg-white">
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
                            <label for="f-zip" class="block text-sm font-medium text-gray-700 mb-1">ZIP Code</label>
                            <input type="text" id="f-zip" name="zip" value="{{ old('zip', $property->zip ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                        </div>
                    </div>
                </div>
                <div>
                    <label for="f-latitude" class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                    <input type="number" step="any" id="f-latitude" name="latitude" value="{{ old('latitude', $property->latitude ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label for="f-longitude" class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                    <input type="number" step="any" id="f-longitude" name="longitude" value="{{ old('longitude', $property->longitude ?? '') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
            </div>
        </div>

        {{-- Media Tab --}}
        <div x-show="activeTab === 'media'" x-cloak class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">

            {{-- Photo grid (edit: server images, create: local previews) --}}
            <div class="flex items-center justify-between mb-3" id="photos-header" @if(!$isEdit) style="display:none!important" @endif>
                <h3 class="font-semibold text-gray-800">Photos</h3>
                <span class="text-xs text-gray-400 flex items-center gap-1">
                    <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    Drag to reorder &mdash; first photo is the main listing image
                </span>
            </div>
            <div class="grid grid-cols-3 md:grid-cols-6 gap-3 mb-4" id="image-grid">
                @if($isEdit)
                @foreach($property->images->sortBy('sort_order') as $img)
                <div class="relative group rounded-xl overflow-hidden aspect-square bg-gray-100 cursor-grab active:cursor-grabbing select-none"
                     data-id="{{ $img->id }}"
                     data-primary-url="{{ route('tenant.admin.api.property-images.primary', [$account, $img->id]) }}"
                     data-delete-url="{{ route('tenant.admin.api.property-images.destroy', [$account, $img->id]) }}">
                    <img src="{{ asset('storage/'.$img->thumb_path) }}" loading="lazy" class="w-full h-full object-cover pointer-events-none">
                    <span class="main-badge absolute top-1.5 left-1.5 items-center gap-1 bg-emerald-500 text-white text-xs px-1.5 py-0.5 rounded-md font-semibold shadow"
                          style="display:{{ $loop->first ? 'inline-flex' : 'none' }}">
                        <x-icon name="star-solid" class="w-3 h-3" />
                        Main
                    </span>
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                        <button type="button" data-action="delete-image"
                                class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg transition-colors pointer-events-auto" title="Delete photo">
                            <x-icon name="x-mark" class="w-3.5 h-3.5" />
                        </button>
                    </div>
                    <div class="absolute bottom-1 right-1 text-white/70 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        <svg width="16" height="16" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 6a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm8-16a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4zm0 8a2 2 0 110-4 2 2 0 010 4z"/></svg>
                    </div>
                </div>
                @endforeach
                @endif
            </div>

            {{-- Upload zone --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ $isEdit ? 'Add More Photos' : 'Upload Photos' }}</label>
                <label for="images" id="upload-zone" class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center hover:border-blue-400 hover:bg-blue-50 transition-colors cursor-pointer">
                    <svg width="40" height="40" id="upload-icon" class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span id="upload-label" class="text-gray-500 text-sm">Click to select photos <span class="text-gray-400">(JPG, PNG, WebP — max 10MB each)</span></span>
                    <input type="file" id="images" multiple accept="image/*" class="sr-only">
                </label>
            </div>
        </div>


    </form>
</div>
@endsection

