<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Message;
use App\Models\Appointment;
use App\Models\SiteSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /** Cache key prefix — must stay in sync with InvalidatesDashboardCache trait. */
    private const CACHE_KEY_PREFIX = 'dashboard:';
    private const CACHE_KEY_SUFFIX = ':v1';
    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    public function index($account)
    {
        $tenant   = app('tenant');
        $tid      = $tenant->id;
        $settings = SiteSettings::where('tenant_id', $tid)->first();

        // Redirect to setup wizard if not completed
        if ($settings && !$settings->setup_completed) {
            return redirect()->route('tenant.admin.setup', ['account' => $account]);
        }

        // Pull the heavy query payload from cache. Property/Message/
        // Appointment/PropertyView writes flush this key via the
        // InvalidatesDashboardCache trait, so the dashboard refreshes
        // immediately on real data changes; the 5-minute TTL is just a
        // safety net.
        $data = Cache::remember(
            self::CACHE_KEY_PREFIX . $tid . self::CACHE_KEY_SUFFIX,
            self::CACHE_TTL_SECONDS,
            fn () => $this->gatherDashboardData($tid)
        );

        // Settings-driven (cheap) data lives outside the cache so the
        // user's tab toggle / widget reorder takes effect instantly.
        $dashConfig = $settings->dashboard_config ?? [];
        $show       = fn ($key) => $dashConfig[$key] ?? true;

        $defaultChartOrder = ['type_chart', 'status_chart', 'views_chart', 'views_30days', 'messages_7days',
                              'price_distribution', 'listings_over_time', 'revenue_trend', 'appt_status', 'message_sources'];
        $chartOrder = $dashConfig['_chart_order'] ?? $defaultChartOrder;
        $chartOrder = array_values(array_filter($chartOrder, fn ($k) => $show($k)));

        $defaultTableOrder = ['top_properties', 'recent_messages', 'starred_messages',
                              'upcoming_appts', 'recent_properties', 'needs_attention'];
        $tableOrder = $dashConfig['_table_order'] ?? $defaultTableOrder;
        $tableOrder = array_values(array_filter($tableOrder, fn ($k) => $show($k)));

        // Chart.js label/data arrays — derived from the cached collections.
        $typeLabels   = $data['propertiesByType']->keys()->map(fn ($k) => ucfirst(str_replace('-', ' ', $k)))->values()->all();
        $typeData     = $data['propertiesByType']->values()->all();
        $statusLabels = $data['propertiesByStatus']->keys()->map(fn ($k) => ucfirst($k))->values()->all();
        $statusData   = $data['propertiesByStatus']->values()->all();
        $viewLabels   = $data['viewsByProperty']->map(fn ($p) => \Illuminate\Support\Str::limit($p->title ?: $p->address_street, 20))->all();
        $viewData     = $data['viewsByProperty']->pluck('view_count')->all();
        $v30Labels    = $data['views30days']->pluck('label')->all();
        $v30Data      = $data['views30days']->pluck('count')->all();
        $msg7Labels   = $data['messages7days']->pluck('label')->all();
        $msg7Data     = $data['messages7days']->pluck('count')->all();
        $priceLabels  = $data['priceDistribution']->keys()->all();
        $priceData    = $data['priceDistribution']->values()->all();
        $ltLabels     = $data['listingsOverTime']->pluck('label')->all();
        $ltData       = $data['listingsOverTime']->pluck('count')->all();
        $revLabels    = $data['revenueTrend']->pluck('label')->all();
        $revData      = $data['revenueTrend']->pluck('revenue')->all();
        $apptLabels   = $data['apptByStatus']->keys()->map(fn ($k) => ucfirst($k))->values()->all();
        $apptData     = $data['apptByStatus']->values()->all();
        $srcLabels    = $data['messageSources']->keys()->map(fn ($k) => ucfirst($k))->values()->all();
        $srcData      = $data['messageSources']->values()->all();

        return view('tenant.admin.dashboard', array_merge(
            $data,
            compact(
                'tenant', 'settings', 'show', 'dashConfig',
                'chartOrder', 'tableOrder',
                'typeLabels', 'typeData', 'statusLabels', 'statusData',
                'viewLabels', 'viewData', 'v30Labels', 'v30Data',
                'msg7Labels', 'msg7Data', 'priceLabels', 'priceData',
                'ltLabels', 'ltData', 'revLabels', 'revData',
                'apptLabels', 'apptData', 'srcLabels', 'srcData'
            )
        ));
    }

    /**
     * Gather every query the dashboard needs for a tenant. Every query
     * MUST stay scoped to $tid — without that, every realtor's dashboard
     * would aggregate ALL tenants' data (cross-tenant leak).
     */
    private function gatherDashboardData(int $tid): array
    {
        $activeProps   = Property::where('tenant_id', $tid)->where('listing_status', 'active');
        $totalMessages = Message::where('tenant_id', $tid)->count();
        $readMessages  = Message::where('tenant_id', $tid)->where('is_read', true)->count();

        $stats = [
            'active_listings'  => $activeProps->clone()->count(),
            'portfolio_value'  => $activeProps->clone()->sum('price'),
            'unread_messages'  => Message::where('tenant_id', $tid)->where('is_read', false)->count(),
            'appointments'     => Appointment::where('tenant_id', $tid)->where('status', 'pending')->count(),
            'total_properties' => Property::where('tenant_id', $tid)->count(),
            'sold_properties'  => Property::where('tenant_id', $tid)->where('listing_status', 'sold')->count(),
            'new_this_week'    => Property::where('tenant_id', $tid)->where('created_at', '>=', now()->subDays(7))->count(),
            'avg_price'        => (int) $activeProps->clone()->avg('price'),
            'pending_listings' => Property::where('tenant_id', $tid)->where('listing_status', 'pending')->count(),
            'total_revenue'    => Property::where('tenant_id', $tid)->where('listing_status', 'sold')->sum('price'),
            'response_rate'    => $totalMessages > 0 ? round(($readMessages / $totalMessages) * 100) : 0,
            'days_on_market'   => (int) ($activeProps->clone()->selectRaw('AVG(DATEDIFF(NOW(), created_at)) as avg_days')->value('avg_days') ?? 0),
            'views_month'      => DB::table('property_views')->where('tenant_id', $tid)->where('viewed_at', '>=', now()->subDays(30))->count(),
        ];

        $viewsByProperty = Property::where('tenant_id', $tid)
            ->where('view_count', '>', 0)
            ->orderBy('view_count', 'desc')->limit(10)
            ->get(['title', 'address_street', 'view_count']);

        $propertiesByType = Property::where('tenant_id', $tid)
            ->selectRaw('property_type, count(*) as count')
            ->whereNotNull('property_type')->groupBy('property_type')
            ->pluck('count', 'property_type');

        $propertiesByStatus = Property::where('tenant_id', $tid)
            ->selectRaw('listing_status, count(*) as count')
            ->groupBy('listing_status')->pluck('count', 'listing_status');

        // Daily views — last 30 days
        $rawViews = DB::table('property_views')
            ->where('tenant_id', $tid)
            ->where('viewed_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
            ->groupBy('date')->pluck('count', 'date');
        $views30days = collect(range(29, 0))->map(function ($d) use ($rawViews) {
            $date = now()->subDays($d)->toDateString();
            return ['label' => now()->subDays($d)->format('M j'), 'count' => $rawViews[$date] ?? 0];
        });

        // Daily messages — last 7 days
        $rawMsgs = Message::where('tenant_id', $tid)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')->pluck('count', 'date');
        $messages7days = collect(range(6, 0))->map(function ($d) use ($rawMsgs) {
            $date = now()->subDays($d)->toDateString();
            return ['label' => now()->subDays($d)->format('D'), 'count' => $rawMsgs[$date] ?? 0];
        });

        // Price range distribution — single CASE WHEN query
        $pdRaw = Property::where('tenant_id', $tid)->selectRaw("
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

        // Listings added per month — last 12 months
        $rawListings = Property::where('tenant_id', $tid)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as count")
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('ym')->pluck('count', 'ym');
        $listingsOverTime = collect(range(11, 0))->map(fn ($m) => [
            'label' => now()->subMonths($m)->format('M y'),
            'count' => (int) ($rawListings[now()->subMonths($m)->format('Y-m')] ?? 0),
        ]);

        // Monthly revenue from sold properties — last 12 months
        $rawRevenue = Property::where('tenant_id', $tid)
            ->where('listing_status', 'sold')
            ->where('updated_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(updated_at, '%Y-%m') as ym, SUM(price) as revenue")
            ->groupBy('ym')->pluck('revenue', 'ym');
        $revenueTrend = collect(range(11, 0))->map(fn ($m) => [
            'label'   => now()->subMonths($m)->format('M y'),
            'revenue' => (int) ($rawRevenue[now()->subMonths($m)->format('Y-m')] ?? 0),
        ]);

        $apptByStatus = Appointment::where('tenant_id', $tid)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status');

        $messageSources = Message::where('tenant_id', $tid)
            ->selectRaw('COALESCE(source, "direct") as source, count(*) as count')
            ->groupBy('source')->pluck('count', 'source');

        $recentMessages   = Message::where('tenant_id', $tid)->latest()->limit(5)->get();
        $starredMessages  = Message::where('tenant_id', $tid)->where('is_starred', true)->latest()->limit(5)->get();
        $upcomingAppts    = Appointment::where('tenant_id', $tid)->where('status', 'confirmed')
            ->where('appointment_date', '>=', now()->toDateString())
            ->orderBy('appointment_date')->limit(5)->get();
        $topProperties    = Property::where('tenant_id', $tid)->with('images')->orderBy('view_count', 'desc')->limit(5)->get();
        $recentProperties = Property::where('tenant_id', $tid)->with('images')->latest()->limit(5)->get();

        $needsAttention = Property::where('tenant_id', $tid)
            ->with('images')
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

        return compact(
            'stats',
            'viewsByProperty', 'propertiesByType', 'propertiesByStatus',
            'views30days', 'messages7days', 'priceDistribution',
            'listingsOverTime', 'revenueTrend', 'apptByStatus', 'messageSources',
            'recentMessages', 'starredMessages', 'upcomingAppts',
            'topProperties', 'recentProperties', 'needsAttention'
        );
    }
}
