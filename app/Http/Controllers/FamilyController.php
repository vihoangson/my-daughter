<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FamilyController extends Controller
{
    protected function getOrCreateFamily(User $user): Family
    {
        if ($user->family) {
            return $user->family;
        }
        // Create a default family for this parent
        return DB::transaction(function() use ($user) {
            $invite = $this->generateUniqueInviteCode();
            $family = Family::create([
                'name' => 'Gia đình ' . ($user->name ?: 'Parent#'.$user->id),
                'invite_code' => $invite,
                'motto' => null,
                'timezone' => 'Asia/Ho_Chi_Minh',
                'country' => 'VN',
                'primary_parent_id' => $user->id,
                'settings' => [
                    'auto_accept_children' => true,
                ],
            ]);
            $user->family_id = $family->id;
            $user->save();
            return $family;
        });
    }

    protected function generateUniqueInviteCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Family::where('invite_code', $code)->exists());
        return $code;
    }

    public function show(Request $request)
    {
        $user = $request->user();
        if ($user->type !== 'parent') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $family = $this->getOrCreateFamily($user);
        $family->load(['users:id,name,email,type,family_id']);
        return response()->json([
            'family' => $family,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        if ($user->type !== 'parent') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $family = $this->getOrCreateFamily($user);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'motto' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:64',
            'country' => 'nullable|string|max:3',
        ]);
        $family->fill($data);
        $family->save();
        return response()->json(['message' => 'Cập nhật thành công', 'family' => $family->fresh()]);
    }

    public function regenerateInvite(Request $request)
    {
        $user = $request->user();
        if ($user->type !== 'parent') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $family = $this->getOrCreateFamily($user);
        $family->invite_code = $this->generateUniqueInviteCode();
        $family->save();
        return response()->json(['message' => 'Đã tạo mã mới', 'invite_code' => $family->invite_code]);
    }

    public function addMember(Request $request)
    {
        $user = $request->user();
        if ($user->type !== 'parent') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $family = $this->getOrCreateFamily($user);
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);
        if ($data['user_id'] == $user->id) {
            return response()->json(['message' => 'Đã là thành viên'], 422);
        }
        $target = User::find($data['user_id']);
        if ($target->family_id && $target->family_id !== $family->id) {
            return response()->json(['message' => 'User đang thuộc gia đình khác'], 422);
        }
        $target->family_id = $family->id;
        $target->save();
        return response()->json(['message' => 'Đã thêm thành viên', 'member' => $target]);
    }

    public function removeMember(Request $request, User $member)
    {
        $user = $request->user();
        if ($user->type !== 'parent') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $family = $this->getOrCreateFamily($user);
        if ($member->family_id !== $family->id) {
            return response()->json(['message' => 'Không thuộc gia đình này'], 404);
        }
        if ($member->id === $family->primary_parent_id) {
            return response()->json(['message' => 'Không thể xoá chủ gia đình'], 422);
        }
        $member->family_id = null;
        $member->save();
        return response()->json(['message' => 'Đã xoá thành viên']);
    }
}

