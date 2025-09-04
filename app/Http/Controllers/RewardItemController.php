<?php
namespace App\Http\Controllers;

use App\Models\RewardItem;
use App\Models\RewardRedemption;
use App\Models\AcoinTransaction;
use App\Models\UserKid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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

    public function kidList(Request $request)
    {
        $kid = Auth::user();
        if(!$kid || $kid->type !== 'child') return response()->json(['message'=>'Forbidden'],403);
        $parentIds = $kid->parents()->pluck('users.id');
        $items = \App\Models\RewardItem::whereIn('parent_id',$parentIds)->where('is_active',true)->orderBy('name')->get();
        // optionally include times redeemed count
        $counts = RewardRedemption::select('reward_item_id', DB::raw('COUNT(*) as c'))
            ->where('kid_id',$kid->id)->groupBy('reward_item_id')->pluck('c','reward_item_id');
        $items->transform(function($it) use ($counts){ $it->redeemed_count = (int)($counts[$it->id] ?? 0); return $it; });
        return $items;
    }

    public function kidRedemptions(Request $request)
    {
        $kid = Auth::user();
        if(!$kid || $kid->type !== 'child') return response()->json(['message'=>'Forbidden'],403);
        $list = RewardRedemption::with(['rewardItem:id,name,price_acoin'])->where('kid_id',$kid->id)->orderByDesc('id')->limit(200)->get();
        return $list;
    }

    public function kidRedeem(Request $request, \App\Models\RewardItem $reward)
    {
        $kid = Auth::user();
        if(!$kid || $kid->type !== 'child') return response()->json(['message'=>'Forbidden'],403);
        if(!$reward->is_active) return response()->json(['message'=>'Phần thưởng đã tắt'],422);
        $parentIds = $kid->parents()->pluck('users.id');
        if(!$parentIds->contains($reward->parent_id)) return response()->json(['message'=>'Không hợp lệ'],403);
        $price = (int)$reward->price_acoin;
        if($price < 0) return response()->json(['message'=>'Giá không hợp lệ'],422);
        $result = DB::transaction(function() use ($kid,$reward,$price){
            $lockedKid = UserKid::where('id',$kid->id)->lockForUpdate()->first();
            if($lockedKid->acoin_balance < $price){
                return ['error'=>true,'response'=>response()->json(['message'=>'Không đủ Acoin','balance'=>$lockedKid->acoin_balance,'required'=>$price],422)];
            }
            $lockedKid->acoin_balance -= $price;
            $lockedKid->save();
            $redemption = RewardRedemption::create([
                'reward_item_id' => $reward->id,
                'kid_id' => $lockedKid->id,
                'parent_id' => $reward->parent_id,
                'price_acoin' => $price,
            ]);
            AcoinTransaction::create([
                'kid_id' => $lockedKid->id,
                'parent_id' => $reward->parent_id,
                'amount' => -$price,
                'type' => 'spend',
                'description' => 'Đổi phần thưởng: '.$reward->name,
                'balance_after' => $lockedKid->acoin_balance,
            ]);
            return ['error'=>false,'redemption'=>$redemption,'balance'=>$lockedKid->acoin_balance];
        });
        if($result['error']) return $result['response'];
        return [
            'message' => 'Đổi phần thưởng thành công',
            'balance_after' => $result['balance'],
            'redemption' => $result['redemption']
        ];
    }
}
