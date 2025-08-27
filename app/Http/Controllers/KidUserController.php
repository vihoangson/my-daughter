<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserKid;

class KidUserController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        // Get kid details with reward punishment history
        $kid = UserKid::with(['rewardPunishments' => function($query) {
            $query->orderBy('created_at', 'desc');
        }, 'parents'])->find($user->id);

        // Calculate total points
        $totalPoints = $kid->rewardPunishments->sum(function($rp) {
            return $rp->type === 'reward' ? $rp->points : -$rp->points;
        });

        // Statistics
        $totalRecords = $kid->rewardPunishments->count();
        $rewardCount = $kid->rewardPunishments->where('type', 'reward')->count();
        $punishmentCount = $kid->rewardPunishments->where('type', 'punishment')->count();

        // Recent activities (last 10)
        $recentActivities = $kid->rewardPunishments->take(10);

        return response()->json([
            'kid' => $kid,
            'total_points' => $totalPoints,
            'statistics' => [
                'total_records' => $totalRecords,
                'reward_count' => $rewardCount,
                'punishment_count' => $punishmentCount
            ],
            'recent_activities' => $recentActivities,
            'point_history' => $kid->rewardPunishments
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $kid = UserKid::with('parents')->find($user->id);

        return response()->json([
            'profile' => $kid,
            'parents_count' => $kid->parents->count()
        ]);
    }
}
