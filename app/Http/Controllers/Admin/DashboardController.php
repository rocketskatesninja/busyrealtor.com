<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Message;
use App\Models\Appointment;
use App\Models\SiteSettings;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index($account)
    {
        $tenant   = app('tenant');
        $settings = SiteSettings::where('tenant_id', $tenant->id)->first();
        $dashConfig = $settings->dashboard_config ?? [];
        $show = fn($key) => $dashConfig[$key] ?? true;

        $activeProps   = Property::where('listing_status', 'active');
        $totalMessages = Message::count();
        $readMessages  = Message::where('is_read', true)->count();

        $stats = [
            'active_listings'  => $activeProps->clone()->count(),
            'portfolio_value'  => $activeProps->clone()->sum('price'),
            'unread_messages'  => Message::where('is_read', false)->count(),
            'appointments'     => Appointment::where('status', 'pending')->count(),
            'total_properties' => Property::count(),
            'sold_properties'  => Property::where('listing_status', 'sold')->count(),
            'new_this_week'    => Property::where('created_at', '>=', now()->subDays(7))->count(),
            'avg_price'        => (int) $activeProps->clone()->avg('price'),
            'pending_listings' => Property::where('listing_status', 'pending')->count(),
            'total_revenue'    => Property::where('listing_status', 'sold')->sum('price'),
            'response_rate'    => $totalMessages > 0 ? round(($readMessages / $totalMessages) * 100) : 0,
            'days_on_market'   => (int) (Property::where('listing_status', 'active')->selectRaw('AVG(DATEDIFF(NOW(), created_at)) as avg_days')->value('avg_days') ?? 0),
            'views_month'      => DB::table('property_views')->where('tenant_id', $tenant->id)->where('viewed_at', '>=', now()->subDays(30))->count(),
        ];

        // ── Existing charts ────────────────────────────────────────────────
        $viewsByProperty = Property::where('view_count', '>', 0)
            ->orderBy('view_count', 'desc')->limit(10)
            ->get(['title', 'address_street', 'view_count']);

        $propertiesByType = Property::selectRaw('property_type, count(*) as count')
            ->whereNotNull('property_type')->groupBy('property_type')
            ->pluck('count', 'property_type');

        $propertiesByStatus = Property::selectRaw('listing_status, count(*) as count')
            ->groupBy('listing_status')->pluck('count', 'listing_status');

        // ── New charts ─────────────────────────────────────────────────────

        // Daily views — last 30 days
        $rawViews = DB::table('property_views')
            ->where('tenant_id', $tenant->id)
            ->where('viewed_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
            ->groupBy('date')->pluck('count', 'date');
        $views30days = collect(range(29, 0))->map(function ($d) use ($rawViews) {
            $date = now()->subDays($d)->toDateString();
            return ['label' => now()->subDays($d)->format('M j'), 'count' => $rawViews[$date] ?? 0];
        });

        // Daily messages — last 7 days
        $rawMsgs = Message::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')->pluck('count', 'date');
        $messages7days = collect(range(6, 0))->map(function ($d) use ($rawMsgs) {
            $date = now()->subDays($d)->toDateString();
            return ['label' => now()->subDays($d)->format('D'), 'count' => $rawMsgs[$date] ?? 0];
        });

        // Price range distribution
        $priceRanges = [
            'Under $200K'  => [0,        200000],
            '$200–400K'    => [200000,   400000],
            '$400–600K'    => [400000,   600000],
            '$600K–1M'     => [600000,  1000000],
            'Over $1M'     => [1000000, PHP_INT_MAX],
        ];
        $priceDistribution = collect($priceRanges)->map(fn($r) =>
            Property::where('price', '>=', $r[0])->where('price', '<', $r[1])->count()
        );

        // Listings added per month — last 12 months
        $listingsOverTime = collect(range(11, 0))->map(function ($m) {
            $dt = now()->subMonths($m);
            return [
                'label' => $dt->format('M y'),
                'count' => Property::whereYear('created_at', $dt->year)->whereMonth('created_at', $dt->month)->count(),
            ];
        });

        // Monthly revenue from sold properties — last 12 months
        $revenueTrend = collect(range(11, 0))->map(function ($m) {
            $dt = now()->subMonths($m);
            return [
                'label'   => $dt->format('M y'),
                'revenue' => (int) Property::where('listing_status', 'sold')
                    ->whereYear('updated_at', $dt->year)->whereMonth('updated_at', $dt->month)
                    ->sum('price'),
            ];
        });

        // Appointment status breakdown
        $apptByStatus = Appointment::selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status');

        // Message sources
        $messageSources = Message::selectRaw('COALESCE(source, "direct") as source, count(*) as count')
            ->groupBy('source')->pluck('count', 'source');

        // ── Tables ─────────────────────────────────────────────────────────
        $recentMessages   = Message::latest()->limit(5)->get();
        $starredMessages  = Message::where('is_starred', true)->latest()->limit(5)->get();
        $upcomingAppts    = Appointment::where('status', 'pending')
            ->orderBy('appointment_date')->limit(5)->get();
        $topProperties    = Property::with('images')->orderBy('view_count', 'desc')->limit(5)->get();
        $recentProperties = Property::with('images')->latest()->limit(5)->get();

        // Needs attention: low views, no photos, listing age > 30 days with low views
        $needsAttention = Property::with('images')
            ->where('listing_status', 'active')
            ->where(function ($q) {
                $q->where('view_count', 0)
                  ->orWhere(function ($q2) {
                      $q2->where('view_count', '<', 10)
                         ->where('created_at', '<=', now()->subDays(14));
                  })
                  ->orWhereDoesntHave('images');
            })
            ->orderBy('view_count')
            ->limit(8)
            ->get()
            ->map(function ($p) {
                $reasons = [];
                if ($p->images->isEmpty()) $reasons[] = 'No photos uploaded';
                if ($p->view_count == 0)   $reasons[] = 'Zero views';
                elseif ($p->view_count < 10 && $p->created_at <= now()->subDays(14)) $reasons[] = 'Low visibility (' . $p->view_count . ' views)';
                $p->attention_reasons = $reasons;
                return $p;
            });

        return view('tenant.admin.dashboard', compact(
            'tenant', 'settings', 'stats', 'show', 'dashConfig',
            'viewsByProperty', 'propertiesByType', 'propertiesByStatus',
            'views30days', 'messages7days', 'priceDistribution',
            'listingsOverTime', 'revenueTrend', 'apptByStatus', 'messageSources',
            'recentMessages', 'starredMessages', 'upcomingAppts',
            'topProperties', 'recentProperties', 'needsAttention'
        ));
    }
}
