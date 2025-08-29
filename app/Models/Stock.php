<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StockHolding;

class Stock extends Model
{
    use HasFactory;
    protected $fillable = ['code','name','base_price','current_price'];

    public function holdings()
    {
        return $this->hasMany(StockHolding::class);
    }
}
