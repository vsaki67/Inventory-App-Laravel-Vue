<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementController extends Controller
{
    public function add(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $row) {
                /** @var InventoryItem $item */
                $item = InventoryItem::lockForUpdate()->findOrFail($row['inventory_item_id']);

                $qty = (float) $row['quantity'];

                $item->quantity = (float) $item->quantity + $qty;
                $item->save();

                StockMovement::create([
                    'inventory_item_id' => $item->id,
                    'type' => 'add',
                    'quantity' => $qty,
                    'note' => $row['note'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Stock added successfully.');
    }

    public function deduct(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $row) {
                /** @var InventoryItem $item */
                $item = InventoryItem::lockForUpdate()->findOrFail($row['inventory_item_id']);

                $qty = (float) $row['quantity'];

                if (((float) $item->quantity - $qty) < 0) {
                    throw ValidationException::withMessages([
                        'message' => "Not enough stock for item: {$item->name}",
                    ]);
                }

                $item->quantity = (float) $item->quantity - $qty;
                $item->save();

                StockMovement::create([
                    'inventory_item_id' => $item->id,
                    'type' => 'deduct',
                    'quantity' => $qty,
                    'note' => $row['note'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Stock deducted successfully.');
    }
}
