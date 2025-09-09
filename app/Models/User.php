<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Log; // added

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'numeric_password',
        'type', // added
        'avatar',
        'login_attempts',
        'locked_until',
        'acoin_balance', // added Acoin balance
        'family_id', // added family reference
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute()
    {
        if (!$this->avatar) {
            return null;
        }

        try {
            // Use asset() helper to generate correct URL for local files
            return asset('storage/' . $this->avatar);
        } catch (\Throwable $e) {
            Log::warning('Avatar URL generation failed', [
                'user_id' => $this->id,
                'avatar_path' => $this->avatar,
                'error' => $e->getMessage()
            ]);

            // Return null to trigger default avatar in frontend
            return null;
        }
    }

    // Relationships for stocks
    public function stockHoldings()
    {
        return $this->hasMany(StockHolding::class);
    }

    // Added: parent <-> kids pivot helpers so base User can access when type is parent/child
    public function kids()
    {
        // For parent users: children managed
        return $this->belongsToMany(UserKid::class, 'child_parent', 'parent_id', 'child_id')->withTimestamps();
    }

    public function parents()
    {
        // For child users: parent accounts
        return $this->belongsToMany(UserParents::class, 'child_parent', 'child_id', 'parent_id')->withTimestamps();
    }

    // Family relationship
    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function familyBlogPosts()
    {
        return $this->hasMany(BlogFamily::class, 'user_id');
    }
}
