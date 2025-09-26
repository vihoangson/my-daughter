<?php
namespace App\Http\Controllers;

use App\Models\{Loan, UserKid, UserParents, User, AcoinTransaction};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    // Parent: list all loans for their k   ids
    public function parentIndex(Request $request)
    {
        $parent = $request->user();
        if(!$parent || $parent->type !== 'parent') return response()->json(['message'=>'Forbidden'],403);
        $kidIds = $parent->kids()->pluck('users.id');
        $loans = Loan::whereIn('kid_id',$kidIds)->orderByDesc('id')->get();
        // accrue snapshot (lazy accrual)
        foreach($loans as $loan){ $loan->accrueIfNeeded(); }
        return $loans->fresh();
    }

    // Parent: create loan (disburse principal to kid balance)
    public function parentStore(Request $request)
    {
        $parent = $request->user();
        if(!$parent || $parent->type !== 'parent') return response()->json(['message'=>'Forbidden'],403);
        $data = $request->validate([
            'kid_id' => ['required','integer', Rule::exists('users','id')->where('type','child')],
            'principal' => 'required|integer|min:1|max:100000000',
            'rate_per_day_bp' => 'required|integer|min:1|max:5000', // up to 50% daily unrealistic but capped
            'term_days' => 'required|integer|min:1|max:365'
        ]);
        // ensure relation
        if(!$parent->kids()->where('users.id',$data['kid_id'])->exists()){
            return response()->json(['message'=>'Kid not related'],403);
        }
        $kid = UserKid::findOrFail($data['kid_id']);
        $loan = null;
        DB::transaction(function() use ($data,$parent,$kid,&$loan){
            $kidLocked = User::where('id',$kid->id)->lockForUpdate()->first();
            // disburse principal to kid balance
            $kidLocked->acoin_balance = (int)$kidLocked->acoin_balance + $data['principal'];
            $kidLocked->save();
            $start = now();
            $loan = Loan::create([
                'kid_id'=>$kidLocked->id,
                'parent_id'=>$parent->id,
                'principal'=>$data['principal'],
                'remaining_principal'=>$data['principal'],
                'rate_per_day_bp'=>$data['rate_per_day_bp'],
                'term_days'=>$data['term_days'],
                'start_date'=>$start->toDateString(),
                'due_date'=>$start->copy()->addDays($data['term_days'])->toDateString(),
                'status'=>'active',
                'accrued_interest'=>0,
                'last_accrual_at'=>$start,
            ]);
            AcoinTransaction::create([
                'kid_id'=>$kidLocked->id,
                'parent_id'=>$parent->id,
                'amount'=>$data['principal'],
                'type'=>'loan_disburse',
                'description'=>'Giải ngân khoản vay #'.$loan->id,
                'balance_after'=>$kidLocked->acoin_balance,
            ]);
        });
        return response()->json(['message'=>'Created','loan'=>$loan->fresh()],201);
    }

    // Parent: detail
    public function parentShow(Request $request, Loan $loan)
    {
        $parent = $request->user();
        if(!$parent || $parent->type !== 'parent') return response()->json(['message'=>'Forbidden'],403);
        if($loan->parent_id !== $parent->id && !$parent->kids()->where('users.id',$loan->kid_id)->exists()) return response()->json(['message'=>'Not related'],403);
        $loan->accrueIfNeeded();
        return $loan->fresh();
    }

    // Parent: force accrue now
    public function parentAccrue(Request $request, Loan $loan)
    {
        $parent = $request->user();
        if(!$parent || $parent->type !== 'parent') return response()->json(['message'=>'Forbidden'],403);
        if(!$parent->kids()->where('users.id',$loan->kid_id)->exists()) return response()->json(['message'=>'Not related'],403);
        $changed = $loan->accrueIfNeeded();
        return ['accrued'=> (bool)$changed, 'loan'=>$loan->fresh()];
    }

    // Kid: list loans
    public function kidIndex(Request $request)
    {
        $kid = $request->user();
        if(!$kid || $kid->type !== 'child') return response()->json(['message'=>'Forbidden'],403);
        $loans = Loan::where('kid_id',$kid->id)->orderByDesc('id')->get();
        foreach($loans as $loan){ $loan->accrueIfNeeded(); }
        return $loans->fresh();
    }

    // Kid: repay
    public function kidRepay(Request $request, Loan $loan)
    {
        $kid = $request->user();
        if(!$kid || $kid->type !== 'child') return response()->json(['message'=>'Forbidden'],403);
        if($loan->kid_id !== $kid->id) return response()->json(['message'=>'Not related'],403);
        if(!in_array($loan->status,['active','overdue'])) return response()->json(['message'=>'Loan not active'],422);
        $data = $request->validate([
            'amount' => 'required|integer|min:1'
        ]);
        $loan->accrueIfNeeded();
        $amount = $data['amount'];
        if($kid->acoin_balance < $amount) return response()->json(['message'=>'Không đủ Acoin','balance'=>(int)$kid->acoin_balance],422);
        DB::transaction(function() use ($kid,$loan,$amount){
            $kidLocked = User::where('id',$kid->id)->lockForUpdate()->first();
            if($kidLocked->acoin_balance < $amount) abort(response()->json(['message'=>'Số dư thay đổi, không đủ Acoin'],422));
            $remaining = $amount;
            $interestPaid = 0; $principalPaid = 0;
            // pay accrued interest first
            if($loan->accrued_interest > 0){
                $payInterest = min($loan->accrued_interest, $remaining);
                $loan->accrued_interest -= $payInterest;
                $remaining -= $payInterest;
                $interestPaid = $payInterest;
            }
            if($remaining > 0 && $loan->remaining_principal > 0){
                $payPrincipal = min($loan->remaining_principal, $remaining);
                $loan->remaining_principal -= $payPrincipal;
                $remaining -= $payPrincipal;
                $principalPaid = $payPrincipal;
            }
            $kidLocked->acoin_balance -= $amount - $remaining; // amount actually used (should be amount; remaining should be 0 always)
            $kidLocked->save();
            if($loan->remaining_principal == 0 && $loan->accrued_interest == 0){
                $loan->status = 'repaid';
            } elseif(now()->gt($loan->due_date)) {
                $loan->status = 'overdue';
            }
            $loan->save();
            AcoinTransaction::create([
                'kid_id'=>$kidLocked->id,
                'parent_id'=> $loan->parent_id,
                'amount'=> -($amount - $remaining),
                'type'=>'loan_repay',
                'description'=>'Trả khoản vay #'.$loan->id.' (gốc:'.$principalPaid.' lãi:'.$interestPaid.')',
                'balance_after'=>$kidLocked->acoin_balance,
            ]);
        });
        return ['message'=>'Repaid','loan'=>$loan->fresh()];
    }

    // Kid: history (loan + repay)
    public function kidHistory(Request $request)
    {
        $kid = $request->user();
        if(!$kid || $kid->type !== 'child') return response()->json(['message'=>'Forbidden'],403);
        $txs = AcoinTransaction::where('kid_id',$kid->id)
            ->whereIn('type',[ 'loan_disburse','loan_repay'])
            ->orderByDesc('id')->limit(300)->get();
        $history = $txs->map(function($t){
            $interest = 0;
            if($t->type === 'loan_repay' && preg_match('/lãi:(\d+)/u',$t->description,$m)) $interest=(int)$m[1];
            return [
                'id'=>$t->id,
                'type'=>$t->type==='loan_disburse'?'loan':'repay',
                'amount'=>abs((int)$t->amount),
                'interest'=>$interest,
                'created_at'=>$t->created_at,
            ];
        });
        return ['history'=>$history];
    }

    // Kid: create loan (maps interest_rate + rate_period to daily basis points)
    public function kidStore(Request $request)
    {
        $kid = $request->user();
        if(!$kid || $kid->type !== 'child') return response()->json(['message'=>'Forbidden'],403);
        $parent = $kid->parents()->first();
        if(!$parent) return response()->json(['message'=>'Không có phụ huynh liên kết'],422);
        $data = $request->validate([
            'amount'=>'required|integer|min:1|max:100000000',
            'term_days'=>'required|integer|min:1|max:365',
            'interest_rate'=>'nullable|numeric|min:0|max:100',
            'rate_period'=>'nullable|string|in:term,day,week'
        ]);
        $principal = (int)$data['amount'];
        $termDays = (int)$data['term_days'];
        $interestRate = (float)($data['interest_rate'] ?? 0); // percent
        $period = $data['rate_period'] ?? 'term';
        $dailyRate = 0.0;
        if($interestRate>0){
            switch($period){
                case 'day': $dailyRate = $interestRate/100.0; break; // already per day
                case 'week': $dailyRate = ($interestRate/100.0)/7.0; break;
                default: $dailyRate = ($interestRate/100.0)/max(1,$termDays); break; // spread over term
            }
        }
        $ratePerDayBp = max(0,(int)round($dailyRate*10000));
        if($ratePerDayBp>5000) return response()->json(['message'=>'Lãi suất quá cao'],422);
        $loan = null;
        DB::transaction(function() use ($kid,$parent,$principal,$termDays,$ratePerDayBp,&$loan){
            $kidLocked = User::where('id',$kid->id)->lockForUpdate()->first();
            $kidLocked->acoin_balance = (int)$kidLocked->acoin_balance + $principal;
            $kidLocked->save();
            $start = now();
            $loan = Loan::create([
                'kid_id'=>$kidLocked->id,
                'parent_id'=>$parent->id,
                'principal'=>$principal,
                'remaining_principal'=>$principal,
                'rate_per_day_bp'=>$ratePerDayBp,
                'term_days'=>$termDays,
                'start_date'=>$start->toDateString(),
                'due_date'=>$start->copy()->addDays($termDays)->toDateString(),
                'status'=>'active',
                'accrued_interest'=>0,
                'last_accrual_at'=>$start,
            ]);
            AcoinTransaction::create([
                'kid_id'=>$kidLocked->id,
                'parent_id'=>$parent->id,
                'amount'=>$principal,
                'type'=>'loan_disburse',
                'description'=>'Giải ngân khoản vay #'.$loan->id,
                'balance_after'=>$kidLocked->acoin_balance,
            ]);
        });
        return response()->json(['message'=>'Created','loan'=>$loan->fresh()],201);
    }
}
