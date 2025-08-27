<?php
namespace App\Http\Controllers;

use App\Models\RewardPunishment;
use App\Models\Child;
use Illuminate\Http\Request;

class RewardPunishmentController extends Controller
{
    public function index()
    {
        $rewards = RewardPunishment::with('child')->latest()->paginate(10);
        return response()->json($rewards);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'child_id' => 'required|exists:users,id',
            'points' => 'required|integer',
            'type' => 'required|in:reward,punishment',
            'description' => 'nullable|string',
        ]);
        $reward = RewardPunishment::create($data);
        return response()->json($reward, 201);
    }

    public function show(RewardPunishment $rewardPunishment)
    {
        $rewardPunishment->load('child');
        return response()->json($rewardPunishment);
    }

    public function update(Request $request, RewardPunishment $rewardPunishment)
    {
        $data = $request->validate([
            'child_id' => 'required|exists:users,id',
            'points' => 'required|integer',
            'type' => 'required|in:reward,punishment',
            'description' => 'nullable|string',
        ]);
        $rewardPunishment->update($data);
        return response()->json($rewardPunishment);
    }

    public function destroy(RewardPunishment $rewardPunishment)
    {
        $rewardPunishment->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    public function children()
    {
        $children = Child::all();
        return response()->json($children);
    }
}
