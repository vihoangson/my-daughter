<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'invite_code',
        'motto',
        'timezone',
        'country',
        'primary_parent_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    // Members (users) belonging to this family
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Primary / owner parent
    public function primaryParent()
    {
        return $this->belongsTo(User::class, 'primary_parent_id');
    }

    // Family blog posts
    public function blogPosts()
    {
        return $this->hasMany(BlogFamily::class);
    }
}
