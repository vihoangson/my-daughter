<?php
namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\UserKid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AchievementController extends Controller
{
    // Parent: list achievements with kids status
    public function parentIndex(Request $request)
    {
        /** @var \App\Models\UserParents $parent */
        $parent = Auth::user();
        if(!$parent) return response()->json(['message'=>'Unauthenticated'], 401);
        $achievements = Achievement::with(['kids:id,name'])
            ->where('parent_id', $parent->id)
            ->orderByDesc('id')
            ->get()
            ->map(function($a){
                $a->kids->transform(function($k){
                    $k->achieved = (bool)$k->pivot->achieved_at;
                    return $k;
                });
                return $a;
            });
        return $achievements;
    }

    // Parent: create achievement
    public function store(Request $request)
    {
        /** @var \App\Models\UserParents $parent */
        $parent = Auth::user();
        if(!$parent) return response()->json(['message'=>'Unauthenticated'], 401);
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'category' => ['nullable','string','max:100'],
            'note' => ['nullable','string'],
            'image' => ['nullable','image','max:2048'],
        ]);
        $achievement = new Achievement();
        $achievement->parent_id = $parent->id;
        $achievement->name = $data['name'];
        $achievement->category = $data['category'] ?? null;
        $achievement->note = $data['note'] ?? null;
        if($request->hasFile('image')){
            $disk = array_key_exists('s3_public', config('filesystems.disks')) ? 's3_public' : 'public';
            $achievement->image_path = $request->file('image')->store('achievements', $disk);
        }
        $achievement->save();
        return response()->json($achievement->fresh(), 201);
    }

    // Parent: update achievement
    public function update(Request $request, Achievement $achievement)
    {
        /** @var \App\Models\UserParents $parent */
        $parent = Auth::user();
        if(!$parent) return response()->json(['message'=>'Unauthenticated'], 401);
        if($achievement->parent_id !== $parent->id){
            return response()->json(['message'=>'Forbidden'], 403);
        }
        $data = $request->validate([
            'name' => ['sometimes','required','string','max:255'],
            'category' => ['sometimes','nullable','string','max:100'],
            'note' => ['nullable','string'],
            'image' => ['nullable','image','max:2048'],
            'remove_image' => ['nullable','boolean']
        ]);
        if(isset($data['name'])) $achievement->name = $data['name'];
        if(array_key_exists('category',$data)) $achievement->category = $data['category'];
        if(array_key_exists('note',$data)) $achievement->note = $data['note'];
        if(!empty($data['remove_image']) && $achievement->image_path){
            // try delete on both disks
            foreach(['s3_public','public'] as $diskDel){
                if(array_key_exists($diskDel, config('filesystems.disks'))){
                    try { Storage::disk($diskDel)->delete($achievement->image_path); } catch(\Throwable $e) {}
                }
            }
            $achievement->image_path = null;
        }
        if($request->hasFile('image')){
            if($achievement->image_path){
                foreach(['s3_public','public'] as $diskDel){
                    if(array_key_exists($diskDel, config('filesystems.disks'))){
                        try { Storage::disk($diskDel)->delete($achievement->image_path); } catch(\Throwable $e) {}
                    }
                }
            }
            $disk = array_key_exists('s3_public', config('filesystems.disks')) ? 's3_public' : 'public';
            $achievement->image_path = $request->file('image')->store('achievements', $disk);
        }
        $achievement->save();
        return $achievement->fresh();
    }

    // Parent: delete achievement
    public function destroy(Achievement $achievement)
    {
        /** @var \App\Models\UserParents $parent */
        $parent = Auth::user();
        if(!$parent) return response()->json(['message'=>'Unauthenticated'], 401);
        if($achievement->parent_id !== $parent->id){
            return response()->json(['message'=>'Forbidden'], 403);
        }
        if($achievement->image_path){
            foreach(['s3_public','public'] as $diskDel){
                if(array_key_exists($diskDel, config('filesystems.disks'))){
                    try { Storage::disk($diskDel)->delete($achievement->image_path); } catch(\Throwable $e) {}
                }
            }
        }
        $achievement->delete();
        return response()->json(['message'=>'Deleted']);
    }

    // Parent: toggle kid achievement status
    public function toggleKid(Achievement $achievement, UserKid $kid)
    {
        /** @var \App\Models\UserParents $parent */
        $parent = Auth::user();
        if(!$parent) return response()->json(['message'=>'Unauthenticated'], 401);
        if($achievement->parent_id !== $parent->id){
            return response()->json(['message'=>'Forbidden'], 403);
        }
        // Ensure this kid belongs to parent
        if(!$parent->kids()->where('users.id',$kid->id)->exists()){
            return response()->json(['message'=>'Kid not managed by parent'], 403);
        }
        $existing = $achievement->kids()->where('users.id',$kid->id)->first();
        if($existing && $existing->pivot->achieved_at){
            // set to null (unachieve)
            $achievement->kids()->updateExistingPivot($kid->id, ['achieved_at'=>null]);
        } else {
            $achievement->kids()->syncWithoutDetaching([$kid->id => ['achieved_at' => now()]]);
        }
        $kidFresh = $achievement->kids()->where('users.id',$kid->id)->first();
        return ['kid_id'=>$kid->id,'achieved'=>(bool)($kidFresh && $kidFresh->pivot->achieved_at),'achieved_at'=>$kidFresh? $kidFresh->pivot->achieved_at: null];
    }

    // Kid: list achievements visible to kid
    public function kidIndex(Request $request)
    {
        /** @var \App\Models\UserKid $kid */
        $kid = Auth::user();
        if(!$kid) return response()->json(['message'=>'Unauthenticated'], 401);
        // Achievements created by any parent that manages this kid
        $parentIds = $kid->parents()->pluck('users.id');
        $achievements = Achievement::whereIn('parent_id',$parentIds)
            ->with(['kids'=>function($q) use ($kid){ $q->where('users.id',$kid->id); }])
            ->orderBy('name')
            ->get()
            ->map(function($a) use ($kid) {
                $pivotKid = $a->kids->first();
                $a->achieved = $pivotKid && $pivotKid->pivot->achieved_at ? true : false;
                unset($a->kids); // remove to simplify payload
                return $a;
            });
        return $achievements;
    }
}
