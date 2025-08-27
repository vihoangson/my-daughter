<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
class UserKid extends User {
    protected $table = 'users';
    protected static function booted() {
        static::addGlobalScope('user_kid', function (Builder $query) {
            $query->where('type', 'child');
        });
    }
    public function parents() {
        return $this->belongsToMany(UserParents::class, 'child_parent', 'child_id', 'parent_id')->withTimestamps();
    }
    public function rewardPunishments() {
        return $this->hasMany(RewardPunishment::class, 'child_id');
    }

    public function requests() {
        return $this->hasMany(KidRequest::class, 'child_id');
    }
}
