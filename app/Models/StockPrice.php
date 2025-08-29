<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockPrice extends Model
{
    use HasFactory;
    protected $fillable = ['stock_id','price','captured_at'];
    public function stock(){ return $this->belongsTo(Stock::class); }
}

