# Wallet Payment Integration

## Overview
The system now automatically uses the user's wallet balance to pay for orders. If the wallet balance is insufficient, the user pays the remaining amount through their selected payment method.

## How It Works

### During Checkout (OrderController)
1. The system calculates the final order total (after discounts and fees)
2. Checks the user's wallet balance
3. Calculates:
   - `wallet_amount`: min(wallet_balance, total) - amount deducted from wallet
   - `paid_amount`: total - wallet_amount - amount user pays via payment method
4. If wallet_amount > 0:
   - Debits the wallet
   - Creates a transaction of type `order_payment`
   - Links the transaction to the order
5. Stores both amounts in the order record

### During Cart Sync (CartController)
The `/api/v1/cart/sync` endpoint now returns wallet information:
```json
{
  "items": [...],
  "order_details": {
    "subtotal": 100.00,
    "delivery_fee": 10.00,
    "discount": 5.00,
    "total": 105.00
  },
  "wallet_details": {
    "balance": 50.00,
    "wallet_amount": 50.00,
    "paid_amount": 55.00
  }
}
```

This allows the mobile app to display:
- User's current wallet balance
- How much will be deducted from wallet
- How much they need to pay via payment method

### During Order Cancellation
When an order is cancelled:
1. Stock is restored
2. If `wallet_amount > 0`:
   - Credits the wallet with the refunded amount
   - Creates a refund transaction
   - Links the transaction to the cancelled order

## Database Schema

### Orders Table - New Fields
- `wallet_amount` (decimal 10,2, default 0.00): Amount paid from wallet
- `paid_amount` (decimal 10,2, default 0.00): Amount paid via payment method
- `wallet_transaction_id` (bigint, nullable): Link to wallet transaction

### Transaction Types
Added new constant:
- `Transaction::TYPE_ORDER_PAYMENT` = 'order_payment'

## API Response Changes

### POST /api/v1/checkout
Response now includes wallet information:
```json
{
  "order_id": 123,
  "status": "pending",
  "order_details": {
    "subtotal": 100.00,
    "delivery_fee": 10.00,
    "discount": 5.00,
    "total": 105.00,
    "wallet_amount": 50.00,
    "paid_amount": 55.00
  }
}
```

### GET /api/v1/orders/{id}
OrderResource now includes:
- `wallet_amount`: Amount paid from wallet
- `paid_amount`: Amount paid via payment method

## Example Scenarios

### Scenario 1: Full Wallet Payment
- Order total: 100 EGP
- Wallet balance: 150 EGP
- Result:
  - wallet_amount: 100 EGP
  - paid_amount: 0 EGP
  - New wallet balance: 50 EGP

### Scenario 2: Partial Wallet Payment
- Order total: 100 EGP
- Wallet balance: 40 EGP
- Result:
  - wallet_amount: 40 EGP
  - paid_amount: 60 EGP (user pays via payment method)
  - New wallet balance: 0 EGP

### Scenario 3: No Wallet Balance
- Order total: 100 EGP
- Wallet balance: 0 EGP
- Result:
  - wallet_amount: 0 EGP
  - paid_amount: 100 EGP (user pays full amount via payment method)
  - New wallet balance: 0 EGP

## Benefits
1. **Automatic**: No need for users to manually select "pay from wallet"
2. **Transparent**: Users see exactly how much comes from wallet vs payment method
3. **Refund Support**: Wallet amounts are automatically refunded on cancellation
4. **Transaction History**: All wallet deductions are tracked with proper transaction records
