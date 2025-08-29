<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTrade extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id','stock_id','type','quantity','price','profit','total','balance_after'
    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function stock(){ return $this->belongsTo(Stock::class); }
}

