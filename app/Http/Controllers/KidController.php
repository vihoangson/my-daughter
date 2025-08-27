<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserKid;
use App\Models\RewardPunishment;
use Illuminate\Support\Facades\Auth;

class KidController extends Controller
{
    /**
     * Get kid profile information
     *
     * @return \Illuminate\Http\Response
     */
    public function profile()
    {
        $user = Auth::user();
        $kid = UserKid::with('parents')->findOrFail($user->id);

        return response()->json([
            'profile' => $user,
            'parents_count' => $kid->parents->count()
        ]);
    }

    /**
     * Get kid dashboard data
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        $user = Auth::user();
        $kid = UserKid::findOrFail($user->id);

        // Get all reward/punishment records
        $records = RewardPunishment::where('child_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate total points
        $totalPoints = $records->sum(function ($record) {
            return $record->type === 'reward' ? $record->points : -$record->points;
        });

        // Get statistics
        $totalRecords = $records->count();
        $rewardCount = $records->where('type', 'reward')->count();
        $punishmentCount = $records->where('type', 'punishment')->count();

        // Get recent activities (last 10)
        $recentActivities = $records->take(10);

        return response()->json([
            'total_points' => $totalPoints,
            'statistics' => [
                'total_records' => $totalRecords,
                'reward_count' => $rewardCount,
                'punishment_count' => $punishmentCount
            ],
            'recent_activities' => $recentActivities,
            'point_history' => $records
        ]);
    }
}
