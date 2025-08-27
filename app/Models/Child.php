<?php
namespace App\Models;
class Child extends User {
    protected $table = 'users';
    protected static function booted() {
        static::addGlobalScope('child', function ($query) {
            $query->where('type', 'child');
        });
    }
    public function parents() {
        return $this->belongsToMany(Parent::class, 'child_parent', 'child_id', 'parent_id');
    }
    public function rewardPunishments() {
        return $this->hasMany(RewardPunishment::class, 'child_id');
    }
}

