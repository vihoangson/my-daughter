<?php
namespace App\Http\Controllers;

use App\Models\RewardItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RewardItemController extends Controller
{
    public function index()
    {
        $parent = Auth::user();
        if(!$parent) return response()->json(['message'=>'Unauthenticated'],401);
        $items = RewardItem::where('parent_id',$parent->id)->orderByDesc('id')->get();
        return $items;
    }

    public function store(Request $request)
    {
        $parent = Auth::user();
        if(!$parent) return response()->json(['message'=>'Unauthenticated'],401);
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'price_acoin' => ['required','integer','min:0','max:100000000'],
            'note' => ['nullable','string'],
            'is_active' => ['nullable','boolean'],
            'image' => ['nullable','image','max:10240'],
        ]);
        $item = new RewardItem();
        $item->parent_id = $parent->id;
        $item->name = $data['name'];
        $item->price_acoin = $data['price_acoin'];
        $item->note = $data['note'] ?? null;
        $item->is_active = array_key_exists('is_active',$data) ? (bool)$data['is_active'] : true;
        if($request->hasFile('image')){
            $disk = array_key_exists('s3_public', config('filesystems.disks')) ? 's3_public' : 'public';
            $item->image_path = $request->file('image')->store('rewards',$disk);
        }
        $item->save();
        return response()->json($item->fresh(),201);
    }

    public function update(Request $request, RewardItem $reward)
    {
        $parent = Auth::user();
        if(!$parent) return response()->json(['message'=>'Unauthenticated'],401);
        if($reward->parent_id !== $parent->id) return response()->json(['message'=>'Forbidden'],403);
        $data = $request->validate([
            'name' => ['sometimes','required','string','max:255'],
            'price_acoin' => ['sometimes','required','integer','min:0','max:100000000'],
            'note' => ['nullable','string'],
            'is_active' => ['nullable','boolean'],
            'image' => ['nullable','image','max:10240'],
            'remove_image' => ['nullable','boolean']
        ]);
        if(isset($data['name'])) $reward->name = $data['name'];
        if(isset($data['price_acoin'])) $reward->price_acoin = $data['price_acoin'];
        if(array_key_exists('note',$data)) $reward->note = $data['note'];
        if(array_key_exists('is_active',$data)) $reward->is_active = (bool)$data['is_active'];
        if(!empty($data['remove_image']) && $reward->image_path){
            foreach(['s3_public','public'] as $d){ if(array_key_exists($d, config('filesystems.disks'))){ try { Storage::disk($d)->delete($reward->image_path);} catch(\Throwable $e){} } }
            $reward->image_path = null;
        }
        if($request->hasFile('image')){
            if($reward->image_path){ foreach(['s3_public','public'] as $d){ if(array_key_exists($d, config('filesystems.disks'))){ try { Storage::disk($d)->delete($reward->image_path);} catch(\Throwable $e){} } } }
            $disk = array_key_exists('s3_public', config('filesystems.disks')) ? 's3_public' : 'public';
            $reward->image_path = $request->file('image')->store('rewards',$disk);
        }
        $reward->save();
        return $reward->fresh();
    }

    public function destroy(RewardItem $reward)
    {
        $parent = Auth::user();
        if(!$parent) return response()->json(['message'=>'Unauthenticated'],401);
        if($reward->parent_id !== $parent->id) return response()->json(['message'=>'Forbidden'],403);
        if($reward->image_path){ foreach(['s3_public','public'] as $d){ if(array_key_exists($d, config('filesystems.disks'))){ try { Storage::disk($d)->delete($reward->image_path);} catch(\Throwable $e){} } } }
        $reward->delete();
        return ['message'=>'Deleted'];
    }

    public function toggleActive(RewardItem $reward)
    {
        $parent = Auth::user();
        if(!$parent) return response()->json(['message'=>'Unauthenticated'],401);
        if($reward->parent_id !== $parent->id) return response()->json(['message'=>'Forbidden'],403);
        $reward->is_active = !$reward->is_active;
        $reward->save();
        return ['id'=>$reward->id,'is_active'=>$reward->is_active];
    }
}

