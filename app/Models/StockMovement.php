<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\InventoryItem;


class StockMovement extends Model
{
    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

}
