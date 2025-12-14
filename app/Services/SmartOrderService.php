<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmartOrderService
{
    /**
     * Parse text order using AI and match with products
     */
    public function parseTextOrder(string $text): array
    {
        // Get AI parsed items
        $aiParsedItems = $this->callOpenAI($text);
        
        if (empty($aiParsedItems)) {
            return [
                'items' => [],
                'totalItems' => 0,
                'totalPrice' => 0,
                'currency' => 'EGP',
            ];
        }

        // Match each item with actual products
        $cartItems = [];
        $totalItems = 0;
        $totalPrice = 0;

        foreach ($aiParsedItems as $aiItem) {
            $matchedProduct = $this->findBestProductMatch(
                $aiItem['product_name'],
                $aiItem['quantity_text'] ?? null
            );

            if ($matchedProduct) {
                $quantity = $aiItem['quantity'] ?? 1;
                $price = $matchedProduct['price'];
                
                $cartItems[] = [
                    'productId' => (string) $matchedProduct['product_id'],
                    'name' => $matchedProduct['name'],
                    'price' => (float) $price,
                    'image' => $matchedProduct['image'],
                    'quantity' => $quantity,
                    'vendorId' => (string) $matchedProduct['vendor_id'],
                    'vendorName' => $matchedProduct['vendor_name'],
                    'variationId' => $matchedProduct['variation_id'],
                    'variationName' => $matchedProduct['variation_name'],
                ];

                $totalItems += $quantity;
                $totalPrice += $price * $quantity;
            }
        }

        return [
            'items' => $cartItems,
            'totalItems' => $totalItems,
            'totalPrice' => round($totalPrice, 2),
            'currency' => 'EGP',
        ];
    }

    /**
     * Call OpenAI to parse the text order
     */
    protected function callOpenAI(string $text): array
    {
        $apiKey = config('services.openai.api_key');
        
        if (!$apiKey) {
            Log::warning('OpenAI API key not configured');
            return $this->fallbackParser($text);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant that extracts grocery/shopping items from Arabic or English text. Return a JSON array of items with fields: product_name (string), quantity (integer, default 1), quantity_text (string, e.g., "1kg", "2L"). Only return valid JSON, no markdown.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $text
                    ]
                ],
                'temperature' => 0.3,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $content = $result['choices'][0]['message']['content'] ?? '';
                
                // Remove markdown code blocks if present
                $content = preg_replace('/```json\s*|\s*```/', '', $content);
                $content = trim($content);
                
                $parsed = json_decode($content, true);
                
                if (is_array($parsed)) {
                    return $parsed;
                }
            }

            Log::error('OpenAI API failed', ['response' => $response->body()]);
            return $this->fallbackParser($text);

        } catch (\Exception $e) {
            Log::error('OpenAI API error: ' . $e->getMessage());
            return $this->fallbackParser($text);
        }
    }

    /**
     * Fallback parser if AI is not available
     */
    protected function fallbackParser(string $text): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        $items = [];

        foreach ($lines as $line) {
            // Try to extract quantity and product name
            // Patterns: "3 tomatoes", "tomatoes 3", "2kg rice", etc.
            if (preg_match('/^(\d+)\s*([a-zA-Z\u0600-\u06FF\s]+)$/u', $line, $matches)) {
                $items[] = [
                    'product_name' => trim($matches[2]),
                    'quantity' => (int) $matches[1],
                    'quantity_text' => null,
                ];
            } elseif (preg_match('/^([a-zA-Z\u0600-\u06FF\s]+)\s+(\d+)$/u', $line, $matches)) {
                $items[] = [
                    'product_name' => trim($matches[1]),
                    'quantity' => (int) $matches[2],
                    'quantity_text' => null,
                ];
            } elseif (preg_match('/^(\d+)\s*(kg|كجم|l|لتر|gram|جرام)\s+([a-zA-Z\u0600-\u06FF\s]+)$/ui', $line, $matches)) {
                $items[] = [
                    'product_name' => trim($matches[3]),
                    'quantity' => 1,
                    'quantity_text' => $matches[1] . $matches[2],
                ];
            } else {
                // Just the product name
                $items[] = [
                    'product_name' => $line,
                    'quantity' => 1,
                    'quantity_text' => null,
                ];
            }
        }

        return $items;
    }

    /**
     * Find best matching product
     * Prioritize featured products
     */
    protected function findBestProductMatch(string $productName, ?string $quantityText = null): ?array
    {
        // Search products with similar names (prioritize featured)
        $products = Product::where('is_active', true)
            ->where(function ($q) use ($productName) {
                $q->where('name_ar', 'ILIKE', "%{$productName}%")
                  ->orWhere('name_ar', 'ILIKE', $productName);
            })
            ->with(['vendor', 'variations'])
            ->orderByRaw('CASE WHEN is_featured = true THEN 0 ELSE 1 END')
            ->orderBy('sort_order')
            ->limit(5)
            ->get();

        if ($products->isEmpty()) {
            return null;
        }

        $bestMatch = $products->first();
        
        // Try to find variation that matches quantity text
        $variation = null;
        if ($quantityText && $bestMatch->variations->isNotEmpty()) {
            $variation = $bestMatch->variations->first(function ($v) use ($quantityText) {
                return stripos($v->name_ar, $quantityText) !== false;
            });
            
            if (!$variation) {
                $variation = $bestMatch->variations->where('is_active', true)->first();
            }
        }

        $images = is_array($bestMatch->images) ? $bestMatch->images : [];
        $imageUrl = !empty($images) ? asset('storage/' . $images[0]) : null;

        return [
            'product_id' => $bestMatch->id,
            'name' => $bestMatch->name_ar,
            'price' => $variation ? $variation->price : $bestMatch->price,
            'image' => $imageUrl,
            'vendor_id' => $bestMatch->vendor_id,
            'vendor_name' => $bestMatch->vendor->name_ar,
            'variation_id' => $variation?->id,
            'variation_name' => $variation?->name_ar,
        ];
    }
}
