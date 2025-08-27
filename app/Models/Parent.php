<?php
namespace App\Models;
class Parent extends User {
    protected $table = 'users';
    protected static function booted() {
        static::addGlobalScope('parent', function ($query) {
            $query->where('type', 'parent');
        });
    }
    public function children() {
        return $this->belongsToMany(Child::class, 'child_parent', 'parent_id', 'child_id');
    }
}

