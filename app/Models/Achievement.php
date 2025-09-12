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
        return $this->belongsToMany(UserKid::class, 'achievement_kid', 'achievement_id', 'kid_id')
            ->withPivot('achieved_at','kid_note')
            ->withTimestamps();
    }

    public function getImageUrlAttribute(): ?string
    {
        if(!$this->image_path) return null;
        $disk = $this->resolvedPublicDisk();
        try { return Storage::disk($disk)->url($this->image_path); } catch(\Throwable $e) { return null; }
    }

    protected function resolvedPublicDisk(): string
    {
        $s3 = config('filesystems.disks.s3_public');
        $hasS3 = is_array($s3)
            && !empty($s3['bucket'])
            && (!empty($s3['key']) || env('AWS_ACCESS_KEY_ID'))
            && (!empty($s3['secret']) || env('AWS_SECRET_ACCESS_KEY'));
        return $hasS3 ? 's3_public' : 'public';
    }
}
