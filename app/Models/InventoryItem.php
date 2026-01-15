<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\StockMovement;

class InventoryItem extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'quantity',
    ];

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

}
