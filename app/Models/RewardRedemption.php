<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RewardRedemption extends Model
{
    protected $fillable = [
        'reward_item_id','kid_id','parent_id','price_acoin'
    ];

    public function rewardItem() { return $this->belongsTo(RewardItem::class,'reward_item_id'); }
    public function kid() { return $this->belongsTo(UserKid::class,'kid_id'); }
    public function parent() { return $this->belongsTo(UserParents::class,'parent_id'); }
}

