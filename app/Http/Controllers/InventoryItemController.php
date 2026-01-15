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

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'numeric', 'min:0'],
        ]);

        InventoryItem::create($data);

        return back()->with('success', 'Item created successfully.');
    }


}
