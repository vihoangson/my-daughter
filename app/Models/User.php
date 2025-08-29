<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
            // Log the error for debugging
            \Log::warning('Avatar URL generation failed', [
                'user_id' => $this->id,
                'avatar_path' => $this->avatar,
                'error' => $e->getMessage()
            ]);

            // Return null to trigger default avatar in frontend
            return null;
        }
    }
}
