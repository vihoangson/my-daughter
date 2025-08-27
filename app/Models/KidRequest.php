<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KidRequest extends Model
{
    protected $fillable = [
        'child_id',
        'title',
        'description',
        'type',
        'status',
        'scheduled_time',
        'parent_id',
        'parent_note'
    ];

    protected $casts = [
        'scheduled_time' => 'datetime',
    ];

    // Possible request types
    const TYPE_TOY = 'toy';
    const TYPE_FOOD = 'food';
    const TYPE_PLAYGROUND = 'playground';
    const TYPE_ACTIVITY = 'activity';

    // Possible request statuses
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';

    /**
     * Get the child that owns the request
     */
    public function child()
    {
        return $this->belongsTo(UserKid::class, 'child_id');
    }

    /**
     * Get the parent that handled the request
     */
    public function parent()
    {
        return $this->belongsTo(UserParents::class, 'parent_id');
    }
}
