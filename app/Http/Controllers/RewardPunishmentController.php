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

    /**
     * Get points history for a specific kid (for parent view)
     */
    public function kidPointsHistory(Request $request, $kidId)
    {
        // Verify that the kid belongs to the parent
        $parent = UserParents::find($request->user()->id);
        if (!$parent || !$parent->kids()->where('users.id', $kidId)->exists()) {
            return response()->json(['message' => 'Forbidden: kid not managed by parent'], 403);
        }

        // Get the points history
        $history = RewardPunishment::where('child_id', $kidId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['history' => $history]);
    }

    /**
     * Add points (reward or punishment) for a specific kid (parent functionality)
     */
    public function addPoints(Request $request, $kidId)
    {
        // Validate input
        $data = $request->validate([
            'points' => 'required|integer|min:1|max:100',
            'type' => 'required|in:reward,punishment',
            'description' => 'required|string',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png|max:2048'
        ]);

        // Verify that the kid belongs to the parent
        $parent = UserParents::find($request->user()->id);
        if (!$parent || !$parent->kids()->where('users.id', $kidId)->exists()) {
            return response()->json(['message' => 'Forbidden: kid not managed by parent'], 403);
        }

        // Handle evidence file upload if provided
        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $evidencePath = $request->file('evidence')->store('evidence', 's3_public');
        }

        // Create the reward/punishment record
        $record = RewardPunishment::create([
            'child_id' => $kidId,
            'points' => $data['points'],
            'type' => $data['type'],
            'description' => $data['description'],
            'evidence_path' => $evidencePath,
        ]);

        return response()->json([
            'message' => 'Points added successfully',
            'record' => $record
        ], 201);
    }
}
