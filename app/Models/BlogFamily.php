<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogFamily extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'user_id',
        'title',
        'slug',
        'content',
        'visibility', // public | members | private
        'status',     // draft | published | archived
        'tags',
        'published_at',
        'pinned',
        'cover_image',
        'meta',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'tags' => 'array',
        'meta' => 'array',
        'pinned' => 'boolean',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

