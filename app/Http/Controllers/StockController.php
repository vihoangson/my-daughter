<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Stock, StockHolding, StockTrade, StockPrice, User};
use App\Models\AcoinTransaction; // added
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    // List stocks (no price change here)
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'child') return response()->json(['message'=>'Forbidden'], 403);
        $stocks = Stock::orderBy('code')->get();
        return response()->json([
            'stocks' => $stocks,
            'acoin_balance' => (int)$user->acoin_balance,
        ]);
    }

    // Random refresh of prices
    public function refresh(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'child') return response()->json(['message'=>'Forbidden'], 403);
        $stocks = Stock::all();
        DB::transaction(function() use ($stocks){
            foreach ($stocks as $stock) {
                $percent = random_int(-10, 10);
                $new = (int) round(max(1, $stock->current_price * (1 + $percent / 100)));
                $stock->current_price = $new;
                $stock->save();
                StockPrice::create([
                    'stock_id' => $stock->id,
                    'price' => $stock->current_price,
                    'captured_at' => now(),
                ]);
                // keep only latest 200 points
                $ids = StockPrice::where('stock_id',$stock->id)->orderByDesc('captured_at')->skip(200)->pluck('id');
                if ($ids->count()) StockPrice::whereIn('id',$ids)->delete();
            }
        });
        return response()->json(['stocks'=>Stock::orderBy('code')->get()]);
    }

    public function prices(Request $request, Stock $stock)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'child') return response()->json(['message'=>'Forbidden'],403);
        $history = StockPrice::where('stock_id',$stock->id)->orderBy('captured_at','asc')->limit(200)->get(['price','captured_at']);
        // if empty (first time), seed one point
        if ($history->isEmpty()) {
            StockPrice::create(['stock_id'=>$stock->id,'price'=>$stock->current_price,'captured_at'=>now()]);
            $history = StockPrice::where('stock_id',$stock->id)->orderBy('captured_at','asc')->limit(200)->get(['price','captured_at']);
        }
        return response()->json([
            'stock' => $stock->only(['id','code','name','current_price']),
            'history' => $history,
        ]);
    }

    // Holdings
    public function holdings(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'child') return response()->json(['message'=>'Forbidden'], 403);
        $holdings = StockHolding::with('stock')->where('user_id',$user->id)->get();
        return response()->json([
            'holdings' => $holdings,
            'acoin_balance' => (int)$user->acoin_balance,
        ]);
    }

    public function buy(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'child') return response()->json(['message'=>'Forbidden'], 403);
        $data = $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'quantity' => 'required|integer|min:1'
        ]);
        $stock = Stock::findOrFail($data['stock_id']);
        $qty = $data['quantity'];
        $cost = $stock->current_price * $qty;
        if ($user->acoin_balance < $cost) {
            return response()->json(['message' => 'Không đủ Acoin', 'required' => $cost, 'balance' => (int)$user->acoin_balance], 422);
        }
        DB::transaction(function() use ($user,$stock,$qty,$cost) {
            $lockedUser = User::where('id',$user->id)->lockForUpdate()->first();
            if ($lockedUser->acoin_balance < $cost) {
                abort(response()->json(['message' => 'Số dư thay đổi, không đủ Acoin'], 422));
            }
            $lockedUser->acoin_balance -= $cost;
            $lockedUser->save();
            $holding = StockHolding::where('user_id',$lockedUser->id)->where('stock_id',$stock->id)->lockForUpdate()->first();
            if ($holding) {
                $newQty = $holding->quantity + $qty;
                $newAvg = (int) round((($holding->quantity * $holding->avg_price) + ($qty * $stock->current_price)) / $newQty);
                $holding->quantity = $newQty;
                $holding->avg_price = $newAvg;
                $holding->save();
            } else {
                $holding = StockHolding::create([
                    'user_id' => $lockedUser->id,
                    'stock_id' => $stock->id,
                    'quantity' => $qty,
                    'avg_price' => $stock->current_price,
                ]);
            }
            StockTrade::create([
                'user_id' => $lockedUser->id,
                'stock_id' => $stock->id,
                'type' => 'buy',
                'quantity' => $qty,
                'price' => $stock->current_price,
                'profit' => 0,
                'total' => $cost,
                'balance_after' => $lockedUser->acoin_balance,
            ]);
            // log acoin transaction (spend)
            AcoinTransaction::create([
                'kid_id' => $lockedUser->id,
                'parent_id' => null,
                'amount' => -$cost,
                'type' => 'trade_buy',
                'description' => 'Mua cổ phiếu ' . $stock->code . ' x' . $qty,
                'balance_after' => $lockedUser->acoin_balance,
            ]);
        });
        return $this->returnState($user);
    }

    public function sell(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'child') return response()->json(['message'=>'Forbidden'], 403);
        $data = $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'quantity' => 'required|integer|min:1'
        ]);
        $stock = Stock::findOrFail($data['stock_id']);
        $qty = $data['quantity'];
        DB::transaction(function() use ($user,$stock,$qty) {
            $lockedUser = User::where('id',$user->id)->lockForUpdate()->first();
            $holding = StockHolding::where('user_id',$lockedUser->id)->where('stock_id',$stock->id)->lockForUpdate()->first();
            if (!$holding || $holding->quantity < $qty) {
                abort(response()->json(['message' => 'Không đủ cổ phiếu để bán'], 422));
            }
            $sellPrice = $stock->current_price;
            $proceeds = $sellPrice * $qty;
            // Realized profit = (sell - avg) * qty (can be negative)
            $realized = ($sellPrice - $holding->avg_price) * $qty;
            $holding->quantity -= $qty;
            if ($holding->quantity == 0) {
                $holding->delete();
            } else {
                $holding->save();
            }
            $lockedUser->acoin_balance += $proceeds;
            $lockedUser->save();
            StockTrade::create([
                'user_id' => $lockedUser->id,
                'stock_id' => $stock->id,
                'type' => 'sell',
                'quantity' => $qty,
                'price' => $sellPrice,
                'profit' => $realized,
                'total' => $proceeds,
                'balance_after' => $lockedUser->acoin_balance,
            ]);
            // log acoin transaction (gain)
            AcoinTransaction::create([
                'kid_id' => $lockedUser->id,
                'parent_id' => null,
                'amount' => $proceeds,
                'type' => 'trade_sell',
                'description' => 'Bán cổ phiếu ' . $stock->code . ' x' . $qty,
                'balance_after' => $lockedUser->acoin_balance,
            ]);
        });
        return $this->returnState($user);
    }

    public function trades(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'child') return response()->json(['message'=>'Forbidden'],403);
        $trades = StockTrade::with('stock')->where('user_id',$user->id)->orderByDesc('id')->limit(100)->get();
        $realized = StockTrade::where('user_id',$user->id)->where('type','sell')->sum('profit');
        return response()->json([
            'trades' => $trades,
            'realized_profit' => (int)$realized,
        ]);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'child') return response()->json(['message'=>'Forbidden'],403);
        $holdings = StockHolding::with('stock')->where('user_id',$user->id)->get();
        $invested = 0; $current = 0; $unrealized = 0;
        foreach ($holdings as $h) {
            $invested += $h->quantity * $h->avg_price;
            $current += $h->quantity * $h->stock->current_price;
            $unrealized += ($h->stock->current_price - $h->avg_price) * $h->quantity;
        }
        $realized = StockTrade::where('user_id',$user->id)->where('type','sell')->sum('profit');
        return response()->json([
            'acoin_balance' => (int)$user->acoin_balance,
            'invested' => (int)$invested,
            'current_value' => (int)$current,
            'unrealized_profit' => (int)$unrealized,
            'realized_profit' => (int)$realized,
            'equity' => (int)($user->acoin_balance + $current),
        ]);
    }

    protected function returnState(User $user)
    {
        $user->refresh();
        $holdings = StockHolding::with('stock')->where('user_id',$user->id)->get();
        return response()->json([
            'acoin_balance' => (int)$user->acoin_balance,
            'holdings' => $holdings,
            'stocks' => Stock::orderBy('code')->get(),
        ]);
    }

    // ==== Parent management methods ====
    public function parentIndex(Request $request)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'],403);
        $stocks = Stock::orderBy('code')->get();
        // attach last change percent (previous price vs current)
        $stockIds = $stocks->pluck('id');
        $prevPrices = StockPrice::select('stock_id','price')
            ->whereIn('id', function($q) use ($stockIds){
                $q->select(DB::raw('MAX(id)'))
                  ->from('stock_prices')
                  ->whereIn('stock_id',$stockIds)
                  ->groupBy('stock_id')
                  ->get();
            });
        // For simplicity compute using last two entries per stock
        $changes = [];
        foreach($stocks as $s){
            $lastTwo = StockPrice::where('stock_id',$s->id)->orderByDesc('captured_at')->limit(2)->pluck('price');
            $change = null;
            if($lastTwo->count()===2 && $lastTwo[1]>0){
                $change = round((($lastTwo[0]-$lastTwo[1])/$lastTwo[1])*100,2);
            }
            $changes[$s->id] = $change;
        }
        $stocks->transform(function($s) use ($changes){ $s->change_percent = $changes[$s->id]; return $s; });
        return response()->json(['stocks'=>$stocks]);
    }

    public function parentRefresh(Request $request)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'],403);
        // reuse child refresh logic but without type constraint
        $stocks = Stock::all();
        DB::transaction(function() use ($stocks){
            foreach ($stocks as $stock) {
                $percent = random_int(-10, 10);
                $new = (int) round(max(1, $stock->current_price * (1 + $percent / 100)));
                $stock->current_price = $new;
                $stock->save();
                StockPrice::create([
                    'stock_id' => $stock->id,
                    'price' => $stock->current_price,
                    'captured_at' => now(),
                ]);
                $ids = StockPrice::where('stock_id',$stock->id)->orderByDesc('captured_at')->skip(200)->pluck('id');
                if ($ids->count()) StockPrice::whereIn('id',$ids)->delete();
            }
        });
        return $this->parentIndex($request);
    }

    public function parentPrices(Request $request, Stock $stock)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'],403);
        $history = StockPrice::where('stock_id',$stock->id)->orderBy('captured_at','asc')->limit(200)->get(['price','captured_at']);
        if ($history->isEmpty()) {
            StockPrice::create(['stock_id'=>$stock->id,'price'=>$stock->current_price,'captured_at'=>now()]);
            $history = StockPrice::where('stock_id',$stock->id)->orderBy('captured_at','asc')->limit(200)->get(['price','captured_at']);
        }
        return response()->json([
            'stock'=>$stock->only(['id','code','name','current_price']),
            'history'=>$history
        ]);
    }

    protected function assertParentOwnsKid($parent, $kidId){
        return $parent->kids()->where('users.id',$kidId)->exists();
    }

    public function parentKidSummary(Request $request, $kidId)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'],403);
        if(!$this->assertParentOwnsKid($user,$kidId)) return response()->json(['message'=>'Kid not related'],403);
        $holdings = StockHolding::with('stock')->where('user_id',$kidId)->get();
        $invested=$current=$unrealized=0;
        foreach($holdings as $h){
            $invested += $h->quantity * $h->avg_price;
            $current += $h->quantity * $h->stock->current_price;
            $unrealized += ($h->stock->current_price - $h->avg_price) * $h->quantity;
        }
        $realized = StockTrade::where('user_id',$kidId)->where('type','sell')->sum('profit');
        $kid = User::find($kidId);
        return response()->json([
            'kid_id'=>$kidId,
            'kid_name'=>$kid? $kid->name: null,
            'acoin_balance' => (int)($kid? $kid->acoin_balance:0),
            'invested' => (int)$invested,
            'current_value' => (int)$current,
            'unrealized_profit' => (int)$unrealized,
            'realized_profit' => (int)$realized,
            'equity' => (int)($kid? $kid->acoin_balance + $current:0),
        ]);
    }

    public function parentKidHoldings(Request $request, $kidId)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'],403);
        if(!$this->assertParentOwnsKid($user,$kidId)) return response()->json(['message'=>'Kid not related'],403);
        $holdings = StockHolding::with('stock')->where('user_id',$kidId)->get();
        return response()->json(['kid_id'=>$kidId,'holdings'=>$holdings]);
    }

    public function parentKidTrades(Request $request, $kidId)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'],403);
        if(!$this->assertParentOwnsKid($user,$kidId)) return response()->json(['message'=>'Kid not related'],403);
        $trades = StockTrade::with('stock')->where('user_id',$kidId)->orderByDesc('id')->limit(200)->get();
        $realized = StockTrade::where('user_id',$kidId)->where('type','sell')->sum('profit');
        return response()->json(['kid_id'=>$kidId,'trades'=>$trades,'realized_profit'=>(int)$realized]);
    }

    public function parentAdjust(Request $request, Stock $stock)
    {
        $user = $request->user();
        if(!$user || $user->type !== 'parent') return response()->json(['message'=>'Forbidden'],403);
        $data = $request->validate(['price'=>'required|integer|min:1|max:100000000']);
        $old = $stock->current_price;
        $stock->current_price = $data['price'];
        $stock->save();
        StockPrice::create(['stock_id'=>$stock->id,'price'=>$stock->current_price,'captured_at'=>now()]);
        $ids = StockPrice::where('stock_id',$stock->id)->orderByDesc('captured_at')->skip(200)->pluck('id');
        if($ids->count()) StockPrice::whereIn('id',$ids)->delete();
        $changePercent = $old>0? round((($stock->current_price-$old)/$old)*100,2): null;
        return response()->json(['message'=>'Updated','stock'=>[
            'id'=>$stock->id,
            'code'=>$stock->code,
            'name'=>$stock->name,
            'current_price'=>$stock->current_price,
            'change_percent'=>$changePercent
        ]]);
    }
}

