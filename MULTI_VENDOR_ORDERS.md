# Multi-Vendor Order System

## Overview
The multi-vendor order system extends the existing order functionality to support orders from multiple vendors while maintaining a single main order for the customer. Each vendor gets isolated order information without accessing customer data.

## Architecture

### Main Components

1. **Main Order (orders table)**
   - Remains the authoritative source for customer data
   - Contains delivery information, payment details, and overall order status
   - Controls the delivery lifecycle
   - User-facing order management

2. **Vendor Orders (vendor_orders table)**
   - Isolated portion of the main order for each vendor
   - Contains vendor-specific items and subtotals
   - No user_id or customer personal information
   - Each vendor can only see their own orders
   - Independent preparation status flow

3. **Vendor Order Items (vendor_order_items table)**
   - Line items for each vendor order
   - Contains product details, quantities, and prices
   - Links to products and variants for reference

## Database Schema

### vendor_orders Table
```php
- id
- order_id (foreign key to orders)
- order_number (same as main order for reference)
- vendor_id (foreign key to vendors)
- subtotal (vendor's payment - full amount for their items)
- status (pending, processing, ready, collected, cancelled)
- timestamps
```

### vendor_order_items Table
```php
- id
- vendor_order_id (foreign key to vendor_orders)
- product_id (foreign key to products)
- variant_id (nullable, foreign key to product_variations)
- product_name (snapshot)
- variant_name (nullable, snapshot)
- product_image (snapshot)
- quantity
- price (per unit)
- subtotal (quantity * price)
- timestamps
```

### vendors Table (updated)
```php
Added:
- commission_percentage (default: 10.00)
```

### transactions Table (updated)
```php
Added:
- vendor_order_id (foreign key to vendor_orders)
```

## Status Flows

### Main Order Status Flow
```
pending → confirmed → processing → shipping → delivered
           ↓
        cancelled
```

### Vendor Order Status Flow
```
pending → processing → ready → collected
           ↓
        cancelled
```

**Key Points:**
- Main order status controls delivery to customer
- Vendor order status controls preparation by vendor
- These flows are independent
- Vendor orders can be marked as "ready" while main order is still "processing"

## Commission System

### Payment Model
- Vendors receive the **full subtotal** of their items
- No commission percentage - vendors get 100% of item prices
- Payment is transferred when main order status = "delivered"

### Payment Timing
- Payment is ONLY transferred when main order status = "delivered"
- Transfer is automatic via OrderObserver
- Creates Transaction record with type "commission"
- Updates vendor's wallet balance with the full subtotal amount
- Cannot be paid multiple times (checks for existing transaction)

### Implementation
```php
// OrderObserver handles vendor payment
public function updated(Order $order): void
{
    if ($order->wasChanged('status') && $order->status === 'delivered') {
        $this->payVendorCommissions($order);
    }
}

// Pays vendor the full subtotal
$wallet->credit(
    $vendorOrder->subtotal,  // Full amount
    Transaction::TYPE_COMMISSION,
    __('Payment for order #:order_number'),
    null,
    $order->id,
    null
);
```

## Checkout Flow

### Order Creation Process
1. Customer places order (POST /api/v1/orders/checkout)
2. OrderCalculationService validates and calculates order details
3. Main order is created with customer information
4. Order items are created
5. createVendorOrders() is called:
   - Groups items by vendor_id
   - Calculates vendor subtotal
   - Gets vendor commission_percentage
   - Calculates commission_amount
   - Creates VendorOrder record
   - Creates VendorOrderItem records

### Code Implementation
```php
protected function createVendorOrders(Order $order, array $items): void
{
    $itemsByVendor = collect($items)->groupBy('vendor_id');

    foreach ($itemsByVendor as $vendorId => $vendorItems) {
        // Calculate vendor subtotal (what they'll receive)
        $vendorSubtotal = $vendorItems->sum('total');

        // Create vendor order
        $vendorOrder = \App\Models\VendorOrder::create([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'vendor_id' => $vendorId,
            'subtotal' => $vendorSubtotal,
            'status' => 'pending',
        ]);

        foreach ($vendorItems as $item) {
            // Create vendor order items...
        }
    }
}
```

## API Endpoints

### Vendor Order Endpoints (Vendor Auth Required)

#### List Vendor Orders
```
GET /api/v1/vendor/orders
Query Parameters:
- status: Filter by status (optional)
- search: Search by order number (optional)
- per_page: Items per page (default: 15)

Response:
{
  "success": true,
  "message": "Vendor orders retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [...],
    "total": 50
  }
}
```

#### Get Vendor Order Details
```
GET /api/v1/vendor/orders/{id}

Response:
{
  "success": true,
  "message": "Vendor order details retrieved successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-ABC123",
    "subtotal": "150.00",
    "commission_amount": "15.00",
    "status": "processing",
    "items": [...]
  }
}
```

#### Update Vendor Order Status
```
PUT /api/v1/vendor/orders/{id}/status
Body:
{
  "status": "processing" // pending, processing, ready, collected, cancelled
}

Response:
{
  "success": true,
  "message": "Vendor order status updated successfully",
  "data": {...}
}

Validation:
- Only valid status transitions are allowed
- Returns 422 if transition is invalid
```

#### Get Vendor Order Statistics
```
GET /api/v1/vendor/orders/statistics

Response:
{
  "success": true,
  "message": "Vendor order statistics retrieved successfully",
  "data": {
    "pending": 5,
    "processing": 3,
    "ready": 2,
    "collected": 45,
    "cancelled": 1,
    "total_commission": "450.00"
  }
}
```

## Privacy & Security

### Data Isolation
- Vendor orders do NOT contain user_id
- Vendors cannot access customer personal information
- Vendors only see order_number for reference
- Delivery address is hidden from vendors
- Customer contact details are not exposed

### Vendor Scope
- All vendor order queries are scoped by vendor_id
- VendorOrderController uses Auth::guard('vendor')->user()
- Vendors can ONLY access their own orders

### Status Transition Validation
- Invalid status transitions are prevented
- Each status has defined valid next states
- Prevents vendors from skipping preparation steps

## Testing Scenarios

### Scenario 1: Multi-Vendor Order
```
Cart:
- Product A from Vendor 1 (price: 50)
- Product B from Vendor 1 (price: 30)
- Product C from Vendor 2 (price: 70)

Result:
- 1 Main Order (total: 150)
- 2 Vendor Orders:
  - Vendor 1: subtotal=80 (will receive 80 when delivered)
  - Vendor 2: subtotal=70 (will receive 70 when delivered)
```

### Scenario 2: Vendor Payment
```
1. Order created with status "pending"
2. Admin marks as "confirmed" → no payment made
3. Admin marks as "processing" → no payment made
4. Admin marks as "shipping" → no payment made
5. Admin marks as "delivered" → full subtotal paid to all vendors
```

### Scenario 3: Status Flow
```
Vendor 1:
- pending → processing (vendor marks)
- processing → ready (vendor marks)
- ready → collected (admin/delivery marks)

Vendor 2:
- pending → cancelled (vendor cancels)
- No commission paid for cancelled order
```

## Models & Relationships

### Order Model
```php
public function vendorOrders()
{
    return $this->hasMany(VendorOrder::class);
}
```

### VendorOrder Model
```php
public function order()
{
    return $this->belongsTo(Order::class);
}

public function vendor()
{
    return $this->belongsTo(Vendor::class);
}

public function items()
{
    return $this->hasMany(VendorOrderItem::class);
}

public function updateStatus(string $status)
{
    // Handles status updates with validation
}

public function isCommissionPaid(): bool
{
    // Checks if main order is delivered
}
```

### VendorOrderItem Model
```php
public function vendorOrder()
{
    return $this->belongsTo(VendorOrder::class);
}

public function product()
{
    return $this->belongsTo(Product::class);
}

public function variant()
{
    return $this->belongsTo(ProductVariation::class, 'variant_id');
}
```

### Transaction Model
```php
public function vendorOrder()
{
    return $this->belongsTo(VendorOrder::class);
}

public function vendor()
{
    return $this->belongsTo(Vendor::class);
}
```

## Future Enhancements

1. **Vendor Notifications**
   - Push notifications when new order arrives
   - Status update notifications
   - Commission payment notifications

2. **Vendor Analytics**
   - Daily/weekly/monthly sales reports
   - Top selling products
   - Commission history

3. **Multi-Delivery Support**
   - Allow vendors to mark items as partially fulfilled
   - Split deliveries per vendor
   - Independent tracking per vendor

4. **Commission Tiers**
   - Different commission rates based on vendor performance
   - Promotional commission rates
   - Category-specific commissions

5. **Vendor Order Management**
   - Bulk status updates
   - Order filters (date range, amount)
   - Export orders to CSV/PDF

## Migration Steps

To implement this system:

```bash
# Run migrations
php artisan migrate

# Verify tables created
- vendor_orders
- vendor_order_items
- commission_percentage added to vendors
- vendor_order_id added to transactions
```

## Notes

- All existing orders continue to work without changes
- Multi-vendor splitting only happens for new orders
- Commission calculation is automatic based on vendor settings
- Vendor orders are created in a transaction with main order
- If vendor order creation fails, entire checkout is rolled back
- Commission payment is idempotent (won't pay twice)
