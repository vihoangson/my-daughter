<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    protected $fillable = [
        'kid_id','parent_id','principal','remaining_principal','rate_per_day_bp','term_days','start_date','due_date','status','accrued_interest','last_accrual_at'
    ];

    protected $casts = [
        'start_date'=>'date',
        'due_date'=>'date',
        'last_accrual_at'=>'datetime'
    ];

    protected $appends = ['paid_amount','paid_interest'];

    public function kid(): BelongsTo { return $this->belongsTo(UserKid::class,'kid_id'); }
    public function parent(): BelongsTo { return $this->belongsTo(UserParents::class,'parent_id'); }

    public function accrueIfNeeded(?\DateTimeInterface $now = null): bool
    {
        $now = $now ?: now();
        if($this->status !== 'active') return false;
        $last = $this->last_accrual_at ?: $this->start_date->startOfDay();
        $days = $last->diffInDays($now->copy()->startOfDay());
        if($days <= 0) return false;
        // simple interest (no compounding until repayment)
        $dailyRate = $this->rate_per_day_bp / 10000; // e.g. 150 -> 0.015
        $interest = (int) round($this->remaining_principal * $dailyRate * $days);
        if($interest > 0){
            $this->accrued_interest += $interest;
            $this->last_accrual_at = $now;
            if($now->gt($this->due_date) && ($this->remaining_principal >0 || $this->accrued_interest>0)) {
                $this->status = 'overdue';
            }
            $this->save();
            return true;
        }
        $this->last_accrual_at = $now; // move forward anyway
        $this->save();
        return false;
    }

    public function getPaidAmountAttribute(){
        return max(0, (int)$this->principal - (int)$this->remaining_principal);
    }
    public function getPaidInterestAttribute(){
        // Not tracked separately yet; returns 0 so outstanding = remaining_principal + accrued_interest
        return 0;
    }
}
