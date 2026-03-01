@extends('layouts.admin')
@section('title', 'Staff')
@section('content')
@php $account = $tenant->slug; @endphp
<div class="max-w-5xl mx-auto px-4">

    <div x-data="{ showForm: false }">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Staff Members</h1>
        <button @click="showForm = !showForm"
                class="btn-primary px-5 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Member
        </button>
    </div>

    {{-- Add Form --}}
    <div>
        <div x-show="showForm" x-cloak class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-8">
            <h3 class="font-semibold text-gray-800 mb-4">Add Staff Member</h3>
            <form method="POST" enctype="multipart/form-data" action="{{ route('tenant.admin.staff.store', $account) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                    <textarea name="bio" rows="3" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('bio') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Profile Photo</label>
                    <input type="file" name="profile_image" accept="image/*" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none">
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
                    <button type="button" @click="showForm = false" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition">Add Member</button>
                </div>
            </form>
        </div>
    </div>
    </div>

    {{-- Staff List --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b">
            <p class="text-sm text-gray-500">{{ $staff->count() }} {{ Str::plural('member', $staff->count()) }} — drag to reorder</p>
        </div>
        @if($staff->count())
        <div id="staff-list" class="divide-y">
            @foreach($staff as $member)
            <div class="flex items-center gap-4 p-5 hover:bg-gray-50 transition-colors" data-id="{{ $member->id }}">
                <div class="cursor-grab text-gray-300 hover:text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                </div>
                <div class="w-14 h-14 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0">
                    @if($member->profile_image)
                        <img src="{{ asset('storage/'.$member->profile_image) }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center" style="background-color: rgba(var(--primary-rgb), 0.1)">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-800">{{ $member->name }}</p>
                    @if($member->title) <p class="text-sm text-gray-500">{{ $member->title }}</p> @endif
                    <div class="flex items-center gap-3 mt-1">
                        @if($member->display_on_homepage) <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Homepage</span> @endif
                        @if($member->accepts_appointments) <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Appointments</span> @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="editMember({{ $member->id }}, '{{ addslashes($member->name) }}', '{{ addslashes($member->title ?? '') }}', '{{ addslashes($member->bio ?? '') }}', '{{ $member->email ?? '' }}', '{{ $member->phone ?? '' }}', {{ $member->display_on_homepage ? 'true' : 'false' }}, {{ $member->accepts_appointments ? 'true' : 'false' }})"
                            class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <form method="POST" action="{{ route('tenant.admin.staff.destroy', [$account, $member->id]) }}" onsubmit="return confirm('Remove this staff member?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-16 text-gray-400">
            <svg class="w-14 h-14 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <p class="font-medium">No staff members yet</p>
        </div>
        @endif
    </div>
</div>

{{-- Edit modal hidden form --}}
<div id="edit-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl">
        <h3 class="font-bold text-gray-800 text-lg mb-4">Edit Staff Member</h3>
        <form id="edit-form" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Name *</label><input type="text" id="edit-name" name="name" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Title</label><input type="text" id="edit-title" name="title" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Email</label><input type="email" id="edit-email" name="email" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">Phone</label><input type="tel" id="edit-phone" name="phone" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
            </div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Bio</label><textarea id="edit-bio" name="bio" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none resize-none"></textarea></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">New Profile Photo (optional)</label><input type="file" name="profile_image" accept="image/*" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"></div>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="edit-homepage" name="display_on_homepage" class="rounded"> Homepage</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="edit-appts" name="accepts_appointments" class="rounded"> Appointments</label>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')" class="px-5 py-2 border border-gray-200 rounded-xl text-sm font-medium">Cancel</button>
                <button type="submit" class="btn-primary px-6 py-2 rounded-xl font-semibold text-sm">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
// Drag-drop reordering
const list = document.getElementById('staff-list');
if (list) {
    let dragged = null;
    list.querySelectorAll('[data-id]').forEach(row => {
        row.draggable = true;
        row.addEventListener('dragstart', () => { dragged = row; row.style.opacity = '0.5'; });
        row.addEventListener('dragend', () => { row.style.opacity = ''; saveOrder(); });
        row.addEventListener('dragover', e => { e.preventDefault(); const after = row.getBoundingClientRect().top + row.offsetHeight / 2 > e.clientY; list.insertBefore(dragged, after ? row : row.nextSibling); });
    });
}
function saveOrder() {
    const order = [...document.querySelectorAll('#staff-list [data-id]')].map(el => el.dataset.id);
    fetch('{{ route('tenant.admin.api.staff-order', $account) }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ order }) });
}
function editMember(id, name, title, bio, email, phone, homepage, appts) {
    const form = document.getElementById('edit-form');
    form.action = `{{ url('/' . $account . '/admin/staff') }}/${id}`;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-title').value = title;
    document.getElementById('edit-bio').value = bio;
    document.getElementById('edit-email').value = email;
    document.getElementById('edit-phone').value = phone;
    document.getElementById('edit-homepage').checked = homepage;
    document.getElementById('edit-appts').checked = appts;
    document.getElementById('edit-modal').classList.remove('hidden');
}
@endsection
