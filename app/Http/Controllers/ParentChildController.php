<?php
namespace App\Http\Controllers;

use App\Models\UserParents;
use App\Models\UserKid;
use App\Models\AcoinTransaction; // added
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage; // added
use Illuminate\Support\Facades\Validator; // added

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
        if(!$parent) return response()->json(['kids' => []]);

        $kids = $parent->kids()->withCount(['parents', 'requests as pending_requests' => function($query) {
            $query->where('status', 'pending');
        }])->get();

        // Add the total points for each kid
        foreach($kids as $kid) {
            $totalPoints = 0;
            $rewardPunishments = $kid->rewardPunishments()->get();
            foreach($rewardPunishments as $rp) {
                $totalPoints += $rp->type === 'reward' ? $rp->points : -$rp->points;
            }
            $kid->total_points = $totalPoints;
        }

        return response()->json(['kids' => $kids]);
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
            'avatar' => 'nullable|image|max:10240'
        ]);

        $kid->name = $data['name'];
        $kid->email = $data['email'];
        if(!empty($data['password'])) $kid->password = Hash::make($data['password']);

        // Handle avatar upload
        if($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if($kid->avatar) {
                try {
                    Storage::disk('s3_public')->delete($kid->avatar);
                } catch(\Throwable $e) {
                    Storage::delete($kid->avatar);
                }
            }

            // Upload new avatar
            $avatarFile = $request->file('avatar');
            $avatarPath = 'avatars/kids/' . time() . '_' . $avatarFile->getClientOriginalName();

            try {
                Storage::disk('s3_public')->put($avatarPath, file_get_contents($avatarFile));
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
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'], 403);

        $parent = UserParents::find($user->id);
        if(!$parent) return response()->json(['message'=>'Parent not found'], 404);

        return response()->json([
            'profile' => $parent
        ]);
    }

    // Add updateProfile method to handle POST requests
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'], 403);

        $parent = UserParents::find($user->id);
        if(!$parent) return response()->json(['message'=>'Parent not found'], 404);

        // Validate basic fields first
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Only validate avatar if it's provided and is a file
        if ($request->hasFile('avatar')) {
            $avatarValidator = Validator::make($request->all(), [
                'avatar' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
            ]);

            if ($avatarValidator->fails()) {
                return response()->json([
                    'message' => 'Avatar validation failed',
                    'errors' => $avatarValidator->errors()
                ], 422);
            }
        }

        // Update basic fields
        $parent->name = $request->input('name');
        if ($request->has('phone')) $parent->phone = $request->input('phone');
        if ($request->has('address')) $parent->address = $request->input('address');

        // Handle avatar upload if provided and valid
        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            // Delete old avatar if exists
            if ($parent->avatar) {
                Storage::disk('public')->delete($parent->avatar);
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $parent->avatar = $avatarPath;
        }

        $parent->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $parent,
            'avatar_url' => $parent->avatar ? asset('storage/' . $parent->avatar) : null
        ]);
    }

    public function fundAcoin(Request $request, UserKid $kid)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'], 403);

        $parent = UserParents::find($user->id);
        if(!$parent || !$parent->kids()->where('users.id',$kid->id)->exists()) {
            return response()->json(['message'=>'Not related'], 403);
        }

        $data = $request->validate([
            'amount' => 'required|integer|min:1|max:100000000' // cap to prevent overflow
        ]);

        $before = (int)$kid->acoin_balance;
        $kid->acoin_balance = $before + $data['amount'];
        $kid->save();

        // log transaction
        AcoinTransaction::create([
            'kid_id' => $kid->id,
            'parent_id' => $user->id,
            'amount' => $data['amount'],
            'type' => 'fund',
            'description' => 'Nạp Acoin bởi phụ huynh',
            'balance_after' => $kid->acoin_balance,
        ]);

        return response()->json([
            'message' => 'Nạp Acoin thành công',
            'kid_id' => $kid->id,
            'balance_before' => $before,
            'balance_after' => (int)$kid->acoin_balance,
            'added' => (int)$data['amount']
        ]);
    }

    public function acoinTransactions(Request $request, UserKid $kid)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'], 403);
        $parent = UserParents::find($user->id);
        if(!$parent || !$parent->kids()->where('users.id',$kid->id)->exists()) {
            return response()->json(['message'=>'Not related'], 403);
        }
        $limit = (int) $request->query('limit', 100);
        $transactions = AcoinTransaction::where('kid_id',$kid->id)
            ->orderByDesc('id')
            ->limit(min($limit, 500))
            ->get();
        return response()->json([
            'kid_id' => $kid->id,
            'balance' => (int)$kid->acoin_balance,
            'transactions' => $transactions,
        ]);
    }
}
