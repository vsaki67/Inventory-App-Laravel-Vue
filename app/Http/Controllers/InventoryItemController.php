<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryItemController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();

        $items = InventoryItem::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Inventory/Index', [
            'items' => $items,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function history(InventoryItem $inventoryItem)
    {
        $movements = $inventoryItem->movements()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Inventory/History', [
            'item' => $inventoryItem,
            'movements' => $movements,
        ]);
    }

}
