<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RewardPunishment extends Model {
    protected $fillable = ['child_id', 'points', 'type', 'description'];
    public function child() {
        return $this->belongsTo(Child::class, 'child_id');
    }
}

