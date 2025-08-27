<?php
namespace App\Http\Controllers;

use App\Models\UserParents;
use App\Models\UserKid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ParentChildController extends Controller
{
    public function parents()
    {
        return response()->json(UserParents::withCount('kids')->get());
    }

    public function kids()
    {
        return response()->json(UserKid::withCount('parents')->get());
    }

    public function attachKids(UserParents $parent, Request $request)
    {
        $data = $request->validate([
            'kid_ids' => 'required|array',
            'kid_ids.*' => 'exists:users,id'
        ]);
        $parent->kids()->syncWithoutDetaching($data['kid_ids']);
        return response()->json(['message' => 'Kids attached', 'kids' => $parent->kids]);
    }

    public function detachKid(UserParents $parent, UserKid $kid)
    {
        $parent->kids()->detach($kid->id);
        return response()->json(['message' => 'Kid detached']);
    }

    public function currentKids(Request $request)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'], 403);
        $parent = UserParents::find($user->id);
        if(!$parent) return response()->json([]);
        return response()->json($parent->kids()->withCount('parents')->get());
    }

    public function storeKid(Request $request)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'], 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|string|min:4'
        ]);
        $password = $data['password'] ?? 'password';
        $kid = UserKid::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($password),
            'type' => 'child'
        ]);
        $parent = UserParents::find($user->id);
        if($parent) {
            $parent->kids()->syncWithoutDetaching([$kid->id]);
        }
        return response()->json(['kid' => $kid, 'default_password' => $password], 201);
    }

    public function updateKid(Request $request, UserKid $kid)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'], 403);
        $parent = UserParents::find($user->id);
        if(!$parent || !$parent->kids()->where('users.id',$kid->id)->exists()) return response()->json(['message'=>'Not related'], 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$kid->id,
            'password' => 'nullable|string|min:4',
            'avatar' => 'nullable|image|max:2048'
        ]);

        $kid->name = $data['name'];
        $kid->email = $data['email'];
        if(!empty($data['password'])) $kid->password = Hash::make($data['password']);

        // Handle avatar upload
        if($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if($kid->avatar) {
                try {
                    \Storage::disk('s3_public')->delete($kid->avatar);
                } catch(\Throwable $e) {
                    \Storage::delete($kid->avatar);
                }
            }

            // Upload new avatar
            $avatarFile = $request->file('avatar');
            $avatarPath = 'avatars/kids/' . time() . '_' . $avatarFile->getClientOriginalName();

            try {
                \Storage::disk('s3_public')->put($avatarPath, file_get_contents($avatarFile));
                $kid->avatar = $avatarPath;
            } catch(\Throwable $e) {
                $kid->avatar = $avatarFile->store('avatars/kids', 'public');
            }
        }

        $kid->save();
        return response()->json(['kid'=>$kid]);
    }

    public function destroyKid(Request $request, UserKid $kid)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'], 403);
        $parent = UserParents::find($user->id);
        if(!$parent || !$parent->kids()->where('users.id',$kid->id)->exists()) return response()->json(['message'=>'Not related'], 403);
        // Detach relation first (optional cascade)
        $parent->kids()->detach($kid->id);
        // Optionally fully delete the kid account:
        $kid->delete();
        return response()->json(['message'=>'Deleted']);
    }

    public function kidDetails(Request $request, UserKid $kid)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'], 403);

        $parent = UserParents::find($user->id);
        if(!$parent || !$parent->kids()->where('users.id',$kid->id)->exists()) {
            return response()->json(['message'=>'Not related'], 403);
        }

        // Get kid with reward punishment history
        $kidDetails = UserKid::with(['rewardPunishments' => function($query) {
            $query->orderBy('created_at', 'desc');
        }])->find($kid->id);

        // Calculate total points
        $totalPoints = $kidDetails->rewardPunishments->sum(function($rp) {
            return $rp->type === 'reward' ? $rp->points : -$rp->points;
        });

        // Count total times received points
        $totalRecords = $kidDetails->rewardPunishments->count();

        return response()->json([
            'kid' => $kidDetails,
            'total_points' => $totalPoints,
            'total_records' => $totalRecords,
            'point_history' => $kidDetails->rewardPunishments
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $parent = UserParents::with('kids')->find($user->id);

        return response()->json([
            'profile' => $parent,
        ]);
    }
}
