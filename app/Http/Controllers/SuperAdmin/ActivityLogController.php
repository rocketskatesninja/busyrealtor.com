<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with(['user', 'tenant'])->latest('created_at');

        if ($request->filled('action')) {
            $query->forAction($request->action);
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->between($request->date_from, $request->date_to);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', "%{$term}%")
                  ->orWhereHas('tenant', fn ($t) => $t->where('name', 'like', "%{$term}%"));
            });
        }

        $logs = $query->paginate(50)->withQueryString();

        $actions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('super-admin.activity', compact('logs', 'actions'));
    }

    public function clear()
    {
        $count = ActivityLog::count();
        ActivityLog::query()->delete();
        logActivity('deleted', "Cleared activity log ({$count} entries)");
        return redirect()->route('super.activity')->with('success', "Activity log cleared ({$count} entries removed).");
    }

    public function export(Request $request)
    {
        $query = ActivityLog::with(['user', 'tenant'])->latest('created_at');

        if ($request->filled('action')) {
            $query->forAction($request->action);
        }
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->between($request->date_from, $request->date_to);
        }
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', "%{$term}%")
                  ->orWhereHas('tenant', fn ($t) => $t->where('name', 'like', "%{$term}%"));
            });
        }

        $filename = 'activity-log-' . now()->format('Y-m-d') . '.csv';

        return response()->stream(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'User', 'Tenant', 'Action', 'Description', 'IP Address']);

            $query->chunk(500, function ($logs) use ($out) {
                foreach ($logs as $log) {
                    fputcsv($out, [
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->user->name ?? 'System',
                        $log->tenant->name ?? '—',
                        $log->action,
                        $log->description,
                        $log->ip_address,
                    ]);
                }
            });

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
