@extends('layouts.super-admin')
@section('title', 'Backups')
@section('page-title', 'Backups')
@section('page-description', 'Platform database and tenant uploads')

@section('content')
@php
    $mb = fn ($bytes) => $bytes >= 1073741824
        ? number_format($bytes / 1073741824, 1).' GB'
        : number_format($bytes / 1048576, 1).' MB';
@endphp
<div class="space-y-6">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    {{-- At a glance: the question this page exists to answer is "is there a recent one". --}}
    <div class="grid sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Most recent</p>
            @if($lastRun)
                <p class="text-xl font-bold text-gray-900">{{ $lastRun->diffForHumans() }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $lastRun->format('D j M Y, H:i') }}</p>
            @else
                <p class="text-xl font-bold text-red-600">None yet</p>
                <p class="text-xs text-gray-500 mt-1">Nothing has been backed up</p>
            @endif
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Kept</p>
            <p class="text-xl font-bold text-gray-900">{{ count($backups) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $mb($total) }} on disk</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Free space</p>
            <p class="text-xl font-bold text-gray-900">{{ $mb($free) }}</p>
            <p class="text-xs text-gray-500 mt-1">Nightly at 02:30, 14 kept</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-4">
            <div>
                <h2 class="font-bold text-gray-900">Backups</h2>
                <p class="text-xs text-gray-500 mt-0.5">Database and every tenant's uploads, in one archive</p>
            </div>
            <form method="POST" action="{{ route('super.backups.store') }}">
                @csrf
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                    Back up now
                </button>
            </form>
        </div>

        @if(empty($backups))
            <p class="px-5 py-10 text-center text-sm text-gray-500">No backups yet. The nightly job runs at 02:30.</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left font-semibold px-5 py-3">File</th>
                        <th class="text-left font-semibold px-5 py-3">Taken</th>
                        <th class="text-right font-semibold px-5 py-3">Size</th>
                        <th class="text-right font-semibold px-5 py-3">&nbsp;</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($backups as $backup)
                    <tr>
                        <td class="px-5 py-3 font-mono text-xs text-gray-700">{{ $backup['name'] }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $backup['created_at']->format('D j M Y, H:i') }}</td>
                        <td class="px-5 py-3 text-right text-gray-600">{{ $mb($backup['size']) }}</td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('super.backups.download', $backup['name']) }}"
                                   class="text-blue-600 hover:text-blue-700 font-medium">Download</a>
                                {{-- <details> rather than a confirm dialog: no script, and the
                                     filename has to be typed, which a misclick cannot do. --}}
                                <details class="relative">
                                    <summary class="text-red-600 hover:text-red-700 font-medium cursor-pointer list-none">Delete</summary>
                                    <form method="POST" action="{{ route('super.backups.destroy', $backup['name']) }}"
                                          class="absolute right-0 z-10 mt-2 w-80 bg-white border border-gray-200 rounded-xl shadow-lg p-3 space-y-2">
                                        @csrf
                                        @method('DELETE')
                                        <p class="text-xs text-gray-600">Type the filename to confirm. This is the only copy on this server.</p>
                                        <input type="text" name="confirm" autocomplete="off" placeholder="{{ $backup['name'] }}"
                                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-red-200">
                                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-2 rounded-lg transition-colors">
                                            Delete permanently
                                        </button>
                                    </form>
                                </details>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Restoring is not a button. Saying so here is the point: an operator who needs it
         in an emergency should not have to go looking for whether one exists. --}}
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
        <h3 class="font-bold text-amber-900 text-sm mb-1">Restoring</h3>
        <p class="text-xs text-amber-800 leading-relaxed">
            There is deliberately no restore button. A restore replaces every tenant, every user and
            the platform's own credentials, so it is done on the server where it can be taken one step
            at a time. Download the archive, then follow the procedure in
            <span class="font-mono">app/Console/Commands/RunBackup.php</span>. Take a fresh backup first.
        </p>
    </div>
</div>
@endsection
