{{-- Shared filter form fields — included in gallery sidebar and map panel --}}

{{-- Property Type --}}
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Property Type</label>
    <select name="type" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">All Types</option>
        @foreach(['house' => 'House', 'condo' => 'Condo', 'townhouse' => 'Townhouse', 'land' => 'Land', 'commercial' => 'Commercial', 'multi-family' => 'Multi-Family'] as $fVal => $fLabel)
            <option value="{{ $fVal }}" {{ request('type') === $fVal ? 'selected' : '' }}>{{ $fLabel }}</option>
        @endforeach
    </select>
</div>

{{-- Price Range --}}
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
    <div class="grid grid-cols-2 gap-2">
        <input type="number" name="price_min" value="{{ request('price_min') }}" placeholder="Min $"
               class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <input type="number" name="price_max" value="{{ request('price_max') }}" placeholder="Max $"
               class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
</div>

{{-- Beds & Baths --}}
<div class="grid grid-cols-2 gap-2">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Min Beds</label>
        <select name="beds" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Any</option>
            @foreach(range(1, 6) as $n)
                <option value="{{ $n }}" {{ request('beds') == $n ? 'selected' : '' }}>{{ $n }}+</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Min Baths</label>
        <select name="baths" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Any</option>
            @foreach(range(1, 5) as $n)
                <option value="{{ $n }}" {{ request('baths') == $n ? 'selected' : '' }}>{{ $n }}+</option>
            @endforeach
        </select>
    </div>
</div>

{{-- Square Footage --}}
<div class="pt-4 border-t">
    <label class="block text-sm font-medium text-gray-700 mb-2">Square Footage</label>
    <div class="grid grid-cols-2 gap-2">
        <input type="number" name="sqft_min" value="{{ request('sqft_min') }}" placeholder="Min"
               class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <input type="number" name="sqft_max" value="{{ request('sqft_max') }}" placeholder="Max"
               class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
</div>

{{-- Garage & HOA --}}
<div class="pt-4 border-t grid grid-cols-2 gap-2">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Garage</label>
        <select name="garage_spaces" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Any</option>
            @foreach(range(1, 4) as $n)
                <option value="{{ $n }}" {{ request('garage_spaces') == $n ? 'selected' : '' }}>{{ $n }}+</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">HOA</label>
        <select name="hoa" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                onchange="var w=document.getElementById('hoaMaxWrap{{ $filterSuffix ?? '' }}');if(w)w.style.display=this.value==='yes'?'block':'none'">
            <option value="">Any</option>
            <option value="yes" {{ request('hoa') === 'yes' ? 'selected' : '' }}>Yes</option>
            <option value="no"  {{ request('hoa') === 'no'  ? 'selected' : '' }}>No</option>
        </select>
    </div>
</div>
<div id="hoaMaxWrap{{ $filterSuffix ?? '' }}" style="{{ request('hoa') === 'yes' ? '' : 'display:none' }}">
    <input type="number" name="hoa_max" value="{{ request('hoa_max') }}" placeholder="Max HOA $/mo"
           class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 mt-2">
</div>

{{-- Features --}}
<div class="pt-4 border-t">
    <label class="block text-sm font-medium text-gray-700 mb-3">Features</label>
    <div class="grid grid-cols-2 gap-2">
        @foreach(['pool' => 'Pool', 'waterfront' => 'Waterfront', 'fireplace' => 'Fireplace', 'basement' => 'Basement'] as $fVal => $fLabel)
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="features[]" value="{{ $fVal }}"
                   {{ in_array($fVal, (array) request('features', [])) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-blue-600">
            <span class="text-sm text-gray-700">{{ $fLabel }}</span>
        </label>
        @endforeach
    </div>
</div>
