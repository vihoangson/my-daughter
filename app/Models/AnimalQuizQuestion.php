<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnimalQuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'difficulty','topic','text','options','correct','explanation'
    ];

    protected $casts = [
        'options' => 'array',
        'correct' => 'integer'
    ];
}

