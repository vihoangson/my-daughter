<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class RewardPunishment extends Model {
    protected $fillable = ['child_id', 'points', 'type', 'description', 'evidence_path'];
    protected $appends = ['evidence_url'];
    public function child() {
        return $this->belongsTo(Child::class, 'child_id');
    }
    public function getEvidenceUrlAttribute(){
        if(!$this->evidence_path) return null;
        try { return Storage::disk('s3_public')->url($this->evidence_path); } catch(\Throwable $e) { return Storage::url($this->evidence_path); }
    }
}
