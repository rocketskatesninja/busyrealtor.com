{{-- Nearby Places — tabbed section showing schools, healthcare, shopping --}}
@if($nearbyPlaces && (count($nearbyPlaces['schools'] ?? []) || count($nearbyPlaces['hospitals'] ?? []) || count($nearbyPlaces['shopping'] ?? [])))
<div id="nearby-section" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow border border-gray-200 dark:border-gray-700 min-w-0 overflow-hidden"
     x-data="{ tab: '{{ count($nearbyPlaces['schools'] ?? []) ? 'schools' : (count($nearbyPlaces['hospitals'] ?? []) ? 'hospitals' : 'shopping') }}' }">
    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2"><svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Nearby Places</h2>

    {{-- Tabs --}}
    <div class="flex gap-2 mb-5 border-b border-gray-200 dark:border-gray-600">
        @foreach(['schools' => 'Schools', 'hospitals' => 'Healthcare', 'shopping' => 'Shopping'] as $key => $label)
        <button @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}'
                    ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-2 text-sm font-medium -mb-px transition">
            {{ $label }}
            @if(count($nearbyPlaces[$key] ?? []))
                <span class="ml-1 text-xs text-gray-400">({{ count($nearbyPlaces[$key]) }})</span>
            @endif
        </button>
        @endforeach
    </div>

    {{-- Tab content --}}
    @foreach(['schools', 'hospitals', 'shopping'] as $cat)
    <div x-show="tab === '{{ $cat }}'" x-cloak>
        @if(count($nearbyPlaces[$cat] ?? []))
            <div class="space-y-3">
                @foreach($nearbyPlaces[$cat] as $place)
                <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                    {{-- Icon --}}
                    <div class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center
                        {{ $cat === 'schools' ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400' : '' }}
                        {{ $cat === 'hospitals' ? 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' : '' }}
                        {{ $cat === 'shopping' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400' : '' }}">
                        @if($cat === 'schools')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                        @elseif($cat === 'hospitals')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        @endif
                    </div>
                    {{-- Details --}}
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 dark:text-gray-100 text-sm">{{ $place['name'] }}</p>
                        <div class="flex items-center gap-3 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            @if($place['distance_miles'] !== null)
                                <span>{{ $place['distance_miles'] }} mi</span>
                            @endif
                            @if($place['rating'])
                                <span class="flex items-center gap-0.5">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= round($place['rating']))
                                            <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        @else
                                            <svg class="w-3 h-3 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        @endif
                                    @endfor
                                    <span class="ml-0.5">{{ $place['rating'] }}</span>
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">{{ $place['address'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500 py-4 text-center">No {{ $cat === 'hospitals' ? 'healthcare facilities' : $cat }} found within 10 miles</p>
        @endif
    </div>
    @endforeach
</div>
@endif
