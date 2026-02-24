<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Message;
use App\Models\Appointment;
use App\Models\SiteSettings;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index($account)
    {
        $tenant   = app('tenant');
        $settings = SiteSettings::where('tenant_id', $tenant->id)->first();
        $dashConfig = $settings->dashboard_config ?? [];
        // Default all widgets to visible if not configured
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
        ];

        // Chart: views by property (real data)
        $viewsByProperty = Property::where('view_count', '>', 0)
            ->orderBy('view_count', 'desc')
            ->limit(10)
            ->get(['title', 'address_street', 'view_count']);

        $propertiesByType   = Property::selectRaw('property_type, count(*) as count')
            ->whereNotNull('property_type')
            ->groupBy('property_type')
            ->pluck('count', 'property_type');

        $propertiesByStatus = Property::selectRaw('listing_status, count(*) as count')
            ->groupBy('listing_status')
            ->pluck('count', 'listing_status');

        // Tables
        $recentMessages   = Message::latest()->limit(5)->get();
        $upcomingAppts    = Appointment::where('status', 'pending')
            ->orderBy('appointment_date')->limit(5)->get();
        $topProperties    = Property::with('images')->orderBy('view_count', 'desc')->limit(5)->get();
        $recentProperties = Property::with('images')->latest()->limit(5)->get();
        $needsAttention   = Property::with('images')
            ->where('listing_status', 'active')
            ->where(function($q) {
                $q->where('view_count', 0)
                  ->orWhere('created_at', '<=', now()->subDays(14)->toDateTimeString());
            })
            ->orderBy('view_count')
            ->limit(5)
            ->get();

        return view('tenant.admin.dashboard', compact(
            'tenant', 'settings', 'stats', 'show', 'dashConfig',
            'viewsByProperty', 'propertiesByType', 'propertiesByStatus',
            'recentMessages', 'upcomingAppts', 'topProperties',
            'recentProperties', 'needsAttention'
        ));
    }
}
