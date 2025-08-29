<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcoinTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'kid_id',
        'parent_id',
        'amount',
        'type',
        'description',
        'balance_after',
    ];

    public function kid()
    {
        return $this->belongsTo(UserKid::class, 'kid_id');
    }

    public function parent()
    {
        return $this->belongsTo(UserParents::class, 'parent_id');
    }
}

