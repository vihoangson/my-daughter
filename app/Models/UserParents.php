<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use App\Models\UserKid; // import child model
class UserParents extends User {
    protected $table = 'users';

    // Add fillable properties to allow mass assignment of these fields
    protected $fillable = [
        'name', 'email', 'password', 'type', 'avatar', 'phone', 'address'
    ];

    protected static function booted() {
        static::addGlobalScope('user_parents', function (Builder $query) {
            $query->where('type', 'parent');
        });
    }
    public function kids() {
        return $this->belongsToMany(UserKid::class, 'child_parent', 'parent_id', 'child_id')->withTimestamps();
    }

    public function handledRequests() {
        return $this->hasMany(KidRequest::class, 'parent_id');
    }
}
