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
            'days_on_market'   => (int) ($activeProps->clone()->selectRaw('AVG(DATEDIFF(NOW(), created_at)) as avg_days')->value('avg_days') ?? 0),
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

        // Price range distribution — single CASE WHEN query
        $pdRaw = Property::selectRaw("
            SUM(CASE WHEN price < 200000 THEN 1 ELSE 0 END) as under_200k,
            SUM(CASE WHEN price >= 200000 AND price < 400000 THEN 1 ELSE 0 END) as p200_400k,
            SUM(CASE WHEN price >= 400000 AND price < 600000 THEN 1 ELSE 0 END) as p400_600k,
            SUM(CASE WHEN price >= 600000 AND price < 1000000 THEN 1 ELSE 0 END) as p600k_1m,
            SUM(CASE WHEN price >= 1000000 THEN 1 ELSE 0 END) as over_1m
        ")->first();
        $priceDistribution = collect([
            'Under $200K' => (int) $pdRaw->under_200k,
            '$200–400K'   => (int) $pdRaw->p200_400k,
            '$400–600K'   => (int) $pdRaw->p400_600k,
            '$600K–1M'    => (int) $pdRaw->p600k_1m,
            'Over $1M'    => (int) $pdRaw->over_1m,
        ]);

        // Listings added per month — last 12 months (single GROUP BY query)
        $rawListings = Property::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as count")
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('ym')->pluck('count', 'ym');
        $listingsOverTime = collect(range(11, 0))->map(fn($m) => [
            'label' => now()->subMonths($m)->format('M y'),
            'count' => (int) ($rawListings[now()->subMonths($m)->format('Y-m')] ?? 0),
        ]);

        // Monthly revenue from sold properties — last 12 months (single GROUP BY query)
        $rawRevenue = Property::where('listing_status', 'sold')
            ->where('updated_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(updated_at, '%Y-%m') as ym, SUM(price) as revenue")
            ->groupBy('ym')->pluck('revenue', 'ym');
        $revenueTrend = collect(range(11, 0))->map(fn($m) => [
            'label'   => now()->subMonths($m)->format('M y'),
            'revenue' => (int) ($rawRevenue[now()->subMonths($m)->format('Y-m')] ?? 0),
        ]);

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

        // ── Chart & table ordering ─────────────────────────────────────────
        $defaultChartOrder = ['type_chart', 'status_chart', 'views_chart', 'views_30days', 'messages_7days',
                              'price_distribution', 'listings_over_time', 'revenue_trend', 'appt_status', 'message_sources'];
        $chartOrder = $dashConfig['_chart_order'] ?? $defaultChartOrder;
        $chartOrder = array_values(array_filter($chartOrder, fn($k) => $show($k)));

        $defaultTableOrder = ['top_properties', 'recent_messages', 'starred_messages',
                              'upcoming_appts', 'recent_properties', 'needs_attention'];
        $tableOrder = $dashConfig['_table_order'] ?? $defaultTableOrder;
        $tableOrder = array_values(array_filter($tableOrder, fn($k) => $show($k)));

        // ── Chart label/data arrays for Chart.js ───────────────────────────
        $typeLabels   = $propertiesByType->keys()->map(fn($k) => ucfirst(str_replace('-', ' ', $k)))->values()->all();
        $typeData     = $propertiesByType->values()->all();
        $statusLabels = $propertiesByStatus->keys()->map(fn($k) => ucfirst($k))->values()->all();
        $statusData   = $propertiesByStatus->values()->all();
        $viewLabels   = $viewsByProperty->map(fn($p) => \Illuminate\Support\Str::limit($p->title ?: $p->address_street, 20))->all();
        $viewData     = $viewsByProperty->pluck('view_count')->all();
        $v30Labels    = $views30days->pluck('label')->all();
        $v30Data      = $views30days->pluck('count')->all();
        $msg7Labels   = $messages7days->pluck('label')->all();
        $msg7Data     = $messages7days->pluck('count')->all();
        $priceLabels  = $priceDistribution->keys()->all();
        $priceData    = $priceDistribution->values()->all();
        $ltLabels     = $listingsOverTime->pluck('label')->all();
        $ltData       = $listingsOverTime->pluck('count')->all();
        $revLabels    = $revenueTrend->pluck('label')->all();
        $revData      = $revenueTrend->pluck('revenue')->all();
        $apptLabels   = $apptByStatus->keys()->map(fn($k) => ucfirst($k))->values()->all();
        $apptData     = $apptByStatus->values()->all();
        $srcLabels    = $messageSources->keys()->map(fn($k) => ucfirst($k))->values()->all();
        $srcData      = $messageSources->values()->all();

        return view('tenant.admin.dashboard', compact(
            'tenant', 'settings', 'stats', 'show', 'dashConfig',
            'viewsByProperty', 'propertiesByType', 'propertiesByStatus',
            'views30days', 'messages7days', 'priceDistribution',
            'listingsOverTime', 'revenueTrend', 'apptByStatus', 'messageSources',
            'recentMessages', 'starredMessages', 'upcomingAppts',
            'topProperties', 'recentProperties', 'needsAttention',
            'chartOrder', 'tableOrder',
            'typeLabels', 'typeData', 'statusLabels', 'statusData',
            'viewLabels', 'viewData', 'v30Labels', 'v30Data',
            'msg7Labels', 'msg7Data', 'priceLabels', 'priceData',
            'ltLabels', 'ltData', 'revLabels', 'revData',
            'apptLabels', 'apptData', 'srcLabels', 'srcData'
        ));
    }
}
