<?php
namespace App\Http\Controllers;

use App\Models\RewardPunishment;
use App\Models\UserKid;
use App\Models\UserParents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:4096'
        ]);
        // ownership check if parent logged in
        if($request->user() && $request->user()->type === 'parent') {
            $parent = UserParents::find($request->user()->id);
            if(!$parent || !$parent->kids()->where('users.id',$data['child_id'])->exists()) {
                return response()->json(['message' => 'Forbidden: kid not managed by parent'], 403);
            }
        }
        $evidencePath = null;
        if($request->hasFile('evidence')) {
            $evidencePath = $request->file('evidence')->store('evidence','s3_public');
        }
        $reward = RewardPunishment::create([
            'child_id'=>$data['child_id'],
            'points'=>$data['points'],
            'type'=>$data['type'],
            'description'=>$data['description'] ?? null,
            'evidence_path'=>$evidencePath,
        ]);
        $reward->load('child');
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
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:4096'
        ]);
        if($request->user() && $request->user()->type === 'parent') {
            $parent = UserParents::find($request->user()->id);
            if(!$parent || !$parent->kids()->where('users.id',$data['child_id'])->exists()) {
                return response()->json(['message' => 'Forbidden: kid not managed by parent'], 403);
            }
        }
        if($request->hasFile('evidence')) {
            // delete old if exists
            if($rewardPunishment->evidence_path) {
                try { Storage::disk('s3_public')->delete($rewardPunishment->evidence_path); } catch(\Throwable $e) {}
            }
            $rewardPunishment->evidence_path = $request->file('evidence')->store('evidence','s3_public');
        }
        $rewardPunishment->child_id = $data['child_id'];
        $rewardPunishment->points = $data['points'];
        $rewardPunishment->type = $data['type'];
        $rewardPunishment->description = $data['description'] ?? null;
        $rewardPunishment->save();
        $rewardPunishment->load('child');
        return response()->json($rewardPunishment);
    }

    public function destroy(RewardPunishment $rewardPunishment)
    {
        $rewardPunishment->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    public function children()
    {
        return response()->json(UserKid::all());
    }
}
