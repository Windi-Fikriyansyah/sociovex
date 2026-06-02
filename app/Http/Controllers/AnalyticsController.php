<?php

namespace App\Http\Controllers;

use App\Models\Analytics;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $tenant = Auth::user()->tenant;

        // Check plan
        if ($tenant->package && !$tenant->package->has_analytics) {
            return view('analytics.upgrade', compact('tenant'));
        }

        $period = $request->get('period', '30'); // days
        $accountId = $request->get('account_id');

        $analyticsQuery = Analytics::where('tenant_id', $tenant->id)
            ->where('date', '>=', now()->subDays((int) $period));

        if ($accountId) {
            $analyticsQuery->where('social_account_id', $accountId);
        }

        $analytics = $analyticsQuery->orderBy('date')->get();

        $socialAccounts = SocialAccount::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->get();

        // Aggregate totals
        $totals = [
            'reach'       => $analytics->sum('reach'),
            'impressions' => $analytics->sum('impressions'),
            'likes'       => $analytics->sum('likes'),
            'comments'    => $analytics->sum('comments'),
            'shares'      => $analytics->sum('shares'),
            'followers'   => $analytics->last()?->followers ?? 0,
        ];

        // Engagement rate
        $totalPosts = max(1, $analytics->count());
        $totals['engagement_rate'] = $totals['impressions'] > 0
            ? round((($totals['likes'] + $totals['comments'] + $totals['shares']) / $totals['impressions']) * 100, 2)
            : 0;

        // Chart data
        $chartData = [
            'labels'      => $analytics->pluck('date')->map(fn($d) => $d->format('d M'))->toArray(),
            'impressions' => $analytics->pluck('impressions')->toArray(),
            'reach'       => $analytics->pluck('reach')->toArray(),
            'likes'       => $analytics->pluck('likes')->toArray(),
            'comments'    => $analytics->pluck('comments')->toArray(),
        ];

        return view('analytics.index', compact(
            'analytics', 'socialAccounts', 'totals', 'chartData', 'period', 'accountId', 'tenant'
        ));
    }
}
