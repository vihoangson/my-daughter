<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Family;
use App\Models\KidRequest;
use App\Models\User;
use App\Models\UserKid;
use App\Models\UserParents;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class AdminController extends Controller
{
    /**
     * Return basic system statistics for the admin overview dashboard.
     */
    public function systemStats(): JsonResponse
    {
        $today = Carbon::today();

        $families = Family::query()->count();
        $users = User::query()->count();
        $kids = UserKid::query()->count();
        $parents = UserParents::query()->count();

        $requestsTotal = KidRequest::query()->count();
        $requestsToday = KidRequest::query()->whereDate('created_at', $today)->count();
        $requestsByStatus = [
            'pending' => KidRequest::query()->where('status', KidRequest::STATUS_PENDING)->count(),
            'approved' => KidRequest::query()->where('status', KidRequest::STATUS_APPROVED)->count(),
            'rejected' => KidRequest::query()->where('status', KidRequest::STATUS_REJECTED)->count(),
            'completed' => KidRequest::query()->where('status', KidRequest::STATUS_COMPLETED)->count(),
        ];

        $achievements = class_exists(Achievement::class) ? Achievement::query()->count() : 0;

        return response()->json([
            'families' => $families,
            'users' => $users,
            'kids' => $kids,
            'parents' => $parents,
            'requests_total' => $requestsTotal,
            'requests_today' => $requestsToday,
            'requests_by_status' => $requestsByStatus,
            'achievements' => $achievements,
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}

