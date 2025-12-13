<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $this->syncVendorCategory($product);
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        $this->syncVendorCategory($product);
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        // Check if vendor still has products in this category
        $vendorHasOtherProductsInCategory = Product::where('vendor_id', $product->vendor_id)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->exists();

        // If no other products in this category, remove the vendor-category relationship
        if (!$vendorHasOtherProductsInCategory) {
            $product->vendor->categories()->detach($product->category_id);
        }
    }

    /**
     * Sync vendor category when product is created or updated
     */
    protected function syncVendorCategory(Product $product): void
    {
        if ($product->vendor_id && $product->category_id) {
            // Add category to vendor if not already associated
            $product->vendor->categories()->syncWithoutDetaching([$product->category_id]);
        }
    }
}
