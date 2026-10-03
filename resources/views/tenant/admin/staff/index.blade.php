@extends('layouts.admin')
@section('title', 'Staff')
@section('page-subtitle', 'Manage your team')
@section('content')
@php $account = $tenant->slug; @endphp
<div class="max-w-7xl mx-auto px-4">

    <div class="flex justify-end mb-6">
        <button data-toggle-add-staff
                class="btn-primary px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition flex items-center gap-2">
            <svg width="16" height="16" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Member
        </button>
    </div>

    {{-- Add Form --}}
    <div>
        <div id="add-staff-panel" style="display:none" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-8">
            <h3 class="font-semibold text-gray-800 mb-1">Add Staff Member</h3>
            <p class="text-xs text-gray-500 mb-4 flex items-start gap-1.5">
                <svg width="16" height="16" class="w-4 h-4 mt-px text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Assigning a staff member to a property routes that property's appointment requests to them by email.</span>
            </p>
            <form method="POST" enctype="multipart/form-data" action="{{ route('tenant.admin.staff.store', $account) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label for="f-name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" id="f-name" name="name" value="{{ old('name') }}" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label for="f-title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" id="f-title" name="title" value="{{ old('title') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label for="f-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="f-email" name="email" value="{{ old('email') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div>
                    <label for="f-phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="tel" id="f-phone" name="phone" value="{{ old('phone') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                </div>
                <div class="md:col-span-2">
                    <label for="f-bio" class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                    <textarea id="f-bio" name="bio" rows="3" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)] resize-none">{{ old('bio') }}</textarea>
                </div>
                <div>
                    <label for="f-profile_image" class="block text-sm font-medium text-gray-700 mb-1">Profile Photo</label>
                    <input type="file" id="f-profile_image" name="profile_image" accept="image/*" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none">
                </div>
                <div class="flex flex-col gap-3 justify-center">
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" name="display_on_homepage" value="1" class="rounded" {{ old('display_on_homepage') ? 'checked' : '' }}>
                        Display on Homepage
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" name="accepts_appointments" value="1" class="rounded" {{ old('accepts_appointments') ? 'checked' : '' }}>
                        Accepts Appointments
                    </label>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3">
                    <button type="button" data-hide-add-staff class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">Add Member</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Staff List --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b">
            <p class="text-sm text-gray-500">{{ $staff->count() }} {{ Str::plural('member', $staff->count()) }} — drag to reorder</p>
        </div>
        @if($staff->count())

        {{-- Mobile cards --}}
        <div class="md:hidden divide-y divide-gray-100">
            @foreach($staff as $member)
            <div class="p-4 flex items-start gap-3">
                <div class="w-14 h-14 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0">
                    @if($member->photo_url)
                        <img src="{{ asset('storage/'.$member->photo_url) }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center" style="background-color: rgba(var(--primary-rgb), 0.1)">
                            <svg width="20" height="20" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-800 text-sm">{{ $member->name }}</p>
                    @if($member->title) <p class="text-xs text-gray-500">{{ $member->title }}</p> @endif
                    <div class="flex flex-wrap gap-1 mt-1">
                        @if($member->display_on_homepage) <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Homepage</span> @endif
                        @if($member->accepts_appointments) <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Appointments</span> @endif
                    </div>
                    @if($member->email)
                        <a href="mailto:{{ $member->email }}" class="block text-xs text-blue-600 mt-1 truncate">{{ $member->email }}</a>
                    @endif
                    @if($member->phone)
                        <a href="tel:{{ $member->phone }}" class="block text-xs text-blue-600 mt-0.5">{{ $member->phone }}</a>
                    @endif
                </div>
                <div class="flex gap-1 flex-shrink-0">
                    <button type="button" class="edit-member-btn p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition"
                            data-member="{{ json_encode($member->only(['id','name','title','bio','email','phone','display_on_homepage','accepts_appointments'])) }}"
                            title="Edit">
                        <x-icon name="pencil" class="w-4 h-4" />
                    </button>
                    <form method="POST" action="{{ route('tenant.admin.staff.destroy', [$account, $member->id]) }}" data-confirm="Remove this staff member?">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition" title="Delete">
                            <x-icon name="trash" class="w-4 h-4" />
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Desktop table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left w-8"></th>
                        <th class="px-5 py-3 text-left">Staff Member</th>
                        <th class="px-5 py-3 text-left">Email</th>
                        <th class="px-5 py-3 text-left">Phone</th>
                        <th class="px-5 py-3 text-left">Flags</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="staff-list"
                       data-config='@json(["orderUrl" => route("tenant.admin.api.staff-order", $account), "staffBaseUrl" => url("/" . $account . "/admin/staff")], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG)'
                       class="divide-y divide-gray-100">
                    @foreach($staff as $member)
                    <tr class="hover:bg-gray-50 transition-colors" data-id="{{ $member->id }}">
                        <td class="px-4 py-4">
                            <div class="cursor-grab text-gray-300 hover:text-gray-500">
                                <svg width="20" height="20" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-14 h-10 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0">
                                    @if($member->photo_url)
                                        <img src="{{ asset('storage/'.$member->photo_url) }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center" style="background-color: rgba(var(--primary-rgb), 0.1)">
                                            <svg width="20" height="20" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800 text-sm">{{ $member->name }}</p>
                                    @if($member->title) <p class="text-xs text-gray-500">{{ $member->title }}</p> @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-sm">
                            @if($member->email)
                                <a href="mailto:{{ $member->email }}" class="text-blue-600 hover:underline">{{ $member->email }}</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-sm">
                            @if($member->phone)
                                <a href="tel:{{ $member->phone }}" class="text-blue-600 hover:underline">{{ $member->phone }}</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                @if($member->display_on_homepage) <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Homepage</span> @endif
                                @if($member->accepts_appointments) <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Appointments</span> @endif
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" class="edit-member-btn p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition"
                                        data-member="{{ json_encode($member->only(['id','name','title','bio','email','phone','display_on_homepage','accepts_appointments'])) }}"
                                        title="Edit">
                                    <x-icon name="pencil" class="w-4 h-4" />
                                </button>
                                <form method="POST" action="{{ route('tenant.admin.staff.destroy', [$account, $member->id]) }}" data-confirm="Remove this staff member?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition" title="Delete">
                                        <x-icon name="trash" class="w-4 h-4" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @else
        <div class="text-center py-16 text-gray-400">
            <svg width="56" height="56" class="w-14 h-14 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <p class="font-medium">No staff members yet</p>
        </div>
        @endif
    </div>
</div>

{{-- Edit modal --}}
<div id="edit-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl">
        <h3 class="font-bold text-gray-800 text-lg mb-4">Edit Staff Member</h3>
        <form id="edit-form" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div><label for="edit-name" class="block text-xs font-medium text-gray-600 mb-1">Name *</label><input type="text" id="edit-name" name="name" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]"></div>
                <div><label for="edit-title" class="block text-xs font-medium text-gray-600 mb-1">Title</label><input type="text" id="edit-title" name="title" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]"></div>
                <div><label for="edit-email" class="block text-xs font-medium text-gray-600 mb-1">Email</label><input type="email" id="edit-email" name="email" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]"></div>
                <div><label for="edit-phone" class="block text-xs font-medium text-gray-600 mb-1">Phone</label><input type="tel" id="edit-phone" name="phone" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]"></div>
            </div>
            <div><label for="edit-bio" class="block text-xs font-medium text-gray-600 mb-1">Bio</label><textarea id="edit-bio" name="bio" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none resize-none"></textarea></div>
            <div><label for="f-profile_image-2" class="block text-xs font-medium text-gray-600 mb-1">New Profile Photo (optional)</label><input type="file" id="f-profile_image-2" name="profile_image" accept="image/*" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"></div>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="edit-homepage" name="display_on_homepage" class="rounded"> Homepage</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="edit-appts" name="accepts_appointments" class="rounded"> Appointments</label>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" data-close-edit-modal class="px-5 py-2 border border-gray-200 rounded-xl text-sm font-medium">Cancel</button>
                <button type="submit" class="btn-primary px-6 py-2 rounded-xl font-semibold text-sm">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('foot')
@vite('resources/js/staff.js')
@endsection
