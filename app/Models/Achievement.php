<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Achievement extends Model
{
    protected $fillable = ['parent_id','category','name','note','image_path'];
    protected $appends = ['image_url'];

    public function parent()
    {
        return $this->belongsTo(UserParents::class, 'parent_id');
    }

    public function kids()
    {
        return $this->belongsToMany(UserKid::class, 'achievement_kid', 'achievement_id', 'kid_id')->withPivot('achieved_at')->withTimestamps();
    }

    public function getImageUrlAttribute(): ?string
    {
        if(!$this->image_path) return null;
        try { return Storage::disk('s3_public')->url($this->image_path); } catch(\Throwable $e) { return Storage::url($this->image_path); }
    }
}
