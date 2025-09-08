<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
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
    public function profile(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'child') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'profile' => $user,
            'avatar_url' => $user->avatar_url // Use the accessor which now uses public disk
        ]);
    }

    /**
     * Get kid dashboard data
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'child') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Get reward punishments for this kid
        $rewardPunishments = RewardPunishment::where('child_id', $user->id)->get();

        // Calculate total points
        $totalPoints = 0;
        foreach ($rewardPunishments as $rp) {
            $totalPoints += $rp->type === 'reward' ? $rp->points : -$rp->points;
        }

        // Get statistics
        $statistics = [
            'total_records' => $rewardPunishments->count(),
            'reward_count' => $rewardPunishments->where('type', 'reward')->count(),
            'punishment_count' => $rewardPunishments->where('type', 'punishment')->count(),
        ];

        // Get recent activities (last 10)
        $recentActivities = RewardPunishment::where('child_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total_points' => $totalPoints,
            'statistics' => $statistics,
            'recent_activities' => $recentActivities
        ]);
    }

    /**
     * Update kid profile information
     *
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'child') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Only validate if this is actually an update request (PUT/POST)
        if (!$request->isMethod('put') && !$request->isMethod('post')) {
            return response()->json(['message' => 'Method not allowed'], 405);
        }

        // Handle both multipart form data and JSON
        $rules = [
            'name' => 'required|string|max:255'
        ];

        // Only validate avatar if it's present
        if ($request->hasFile('avatar')) {
            $rules['avatar'] = 'image|mimes:jpeg,png,jpg,gif|max:10240';
        }

        try {
            $data = $request->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Update name
        $user->name = $data['name'];

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            try {
                // Delete old avatar if exists
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }

                // Store new avatar with unique name
                $file = $request->file('avatar');
                $fileName = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
                $avatarPath = $file->storeAs('avatars', $fileName, 'public');
                $user->avatar = $avatarPath;

            } catch (\Exception $e) {
                \Log::error('Avatar upload failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'message' => 'Avatar upload failed',
                    'errors' => ['avatar' => ['Không thể tải lên ảnh đại diện. Vui lòng thử lại.']]
                ], 422);
            }
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $user,
            'avatar_url' => $user->avatar_url // Use the accessor for consistency
        ]);
    }

    /**
     * Change kid password
     *
     * @return \Illuminate\Http\Response
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'child') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:4|confirmed'
        ]);

        // Check current password
        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        // Update password
        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }

    /**
     * Get points history and stats
     *
     * @return \Illuminate\Http\Response
     */
    public function points(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'child') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $records = RewardPunishment::where('child_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        $totalPoints = 0; $rewardCount = 0; $punishmentCount = 0;
        foreach ($records as $rp) {
            if ($rp->type === 'reward') { $totalPoints += $rp->points; $rewardCount++; }
            else { $totalPoints -= $rp->points; $punishmentCount++; }
        }
        return response()->json([
            'total_points' => $totalPoints,
            'statistics' => [
                'total_records' => $records->count(),
                'reward_count' => $rewardCount,
                'punishment_count' => $punishmentCount,
            ],
            'records' => $records,
        ]);
    }
}
