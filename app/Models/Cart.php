<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    // Accessors
    public function getSubtotalAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    // Methods
    public function addItem(Product $product, int $quantity = 1)
    {
        $item = $this->items()->where('product_id', $product->id)->first();

        if ($item) {
            $item->quantity += $quantity;
            $item->save();
        } else {
            $item = $this->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
            ]);
        }

        return $item;
    }

    public function updateItemQuantity(int $itemId, int $quantity)
    {
        $item = $this->items()->findOrFail($itemId);
        $item->quantity = $quantity;
        $item->save();

        return $item;
    }

    public function removeItem(int $itemId)
    {
        return $this->items()->where('id', $itemId)->delete();
    }

    public function clear()
    {
        return $this->items()->delete();
    }
}
