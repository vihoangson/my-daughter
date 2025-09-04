<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RewardItem extends Model
{
    protected $fillable = ['parent_id','name','price_acoin','image_path','note','is_active'];
    protected $casts = [ 'is_active'=>'boolean' ];
    protected $appends = ['image_url'];

    public function parent()
    {
        return $this->belongsTo(UserParents::class, 'parent_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if(!$this->image_path) return null;
        try { return Storage::disk('s3_public')->url($this->image_path); } catch(\Throwable $e) { return Storage::url($this->image_path); }
    }
}

