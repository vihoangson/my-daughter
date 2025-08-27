<?php
namespace App\Http\Controllers;

use App\Models\UserParents;
use App\Models\UserKid;
use Illuminate\Http\Request;

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
}

