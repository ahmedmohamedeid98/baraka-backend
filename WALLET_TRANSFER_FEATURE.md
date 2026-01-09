# Wallet Transfer Feature Documentation

## Overview
A secure wallet transfer system that allows both users and vendors to transfer balance between their wallets with built-in fraud prevention, daily limits, and configurable transfer fees.

## Features

### 1. **Polymorphic Wallet System**
- Wallets support both Users and Vendors through polymorphic relationships
- Each wallet can send and receive transfers
- Automatic wallet creation for new users/vendors

### 2. **Transfer Fee System**
Configurable fees via `config/api.php`:
```php
'wallet_transfer' => [
    'fee_percentage' => 1,        // 1% fee on amount
    'fee_fixed' => 0,             // Fixed fee in EGP
]
```

### 3. **Security & Fraud Prevention**
- **Rate Limiting**: 5 transfers per minute per user
- **Daily Amount Limit**: Default 50,000 EGP per day
- **Daily Count Limit**: Maximum 20 transfers per day
- **Auto-flagging**: Transfers ≥ 5,000 EGP are flagged for admin review
- **Device Tracking**: IP address, user agent, and device fingerprinting
- **Unique Reference Numbers**: Each transfer gets a unique TRF-XXXXXXXXXXXX reference

### 4. **Transfer Validation**
Before executing transfers, the system validates:
- Transfer feature is enabled
- Sender and receiver are different
- Amount within min/max limits (default: 10 - 10,000 EGP)
- Sufficient balance (including fees)
- Daily limits not exceeded
- User verification (optional, configurable)

## API Endpoints

### User Endpoints
All under `/api/v1/wallet/transfer` (requires authentication):

#### 1. Calculate Transfer Fee
```http
POST /api/v1/wallet/transfer/calculate-fee
Content-Type: application/json

{
    "amount": 1000
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "amount": "1000.00",
        "fee": "10.00",
        "fee_percentage": 1,
        "fee_fixed": 0,
        "total_deducted": "1010.00",
        "amount_received": "1000.00",
        "sufficient_balance": true,
        "current_balance": "5000.00"
    }
}
```

#### 2. Validate Transfer
```http
POST /api/v1/wallet/transfer/validate
Content-Type: application/json

{
    "recipient_type": "user",
    "recipient_id": 123,
    "amount": 1000
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "valid": true,
        "errors": [],
        "warnings": [],
        "fee_info": {
            "amount": 1000,
            "fee": 10,
            "total": 1010
        }
    }
}
```

#### 3. Execute Transfer
```http
POST /api/v1/wallet/transfer
Content-Type: application/json

{
    "recipient_type": "user",
    "recipient_id": 123,
    "amount": 1000,
    "description": "Payment for services",
    "verification_code": "123456"
}
```

**Response:**
```json
{
    "success": true,
    "message": "تم التحويل بنجاح",
    "data": {
        "transfer": {
            "id": 1,
            "reference_number": "TRF-ABC123DEF456",
            "type": "sent",
            "sender": {
                "type": "user",
                "id": 1,
                "name": "John Doe",
                "wallet_id": 1
            },
            "receiver": {
                "type": "user",
                "id": 123,
                "name": "Jane Smith",
                "wallet_id": 123
            },
            "amount": 1000.00,
            "fee": 10.00,
            "total_deducted": 1010.00,
            "amount_received": 1000.00,
            "status": "completed",
            "status_text": "مكتمل",
            "description": "Payment for services",
            "is_flagged": false,
            "created_at": "2026-01-09T12:00:00.000000Z",
            "created_at_human": "1 minute ago"
        }
    }
}
```

#### 4. Get Transfer History
```http
GET /api/v1/wallet/transfers?type=sent&status=completed&per_page=20
```

**Query Parameters:**
- `type`: Filter by 'sent' or 'received'
- `status`: Filter by status (pending, completed, failed, cancelled)
- `per_page`: Results per page (max 50)

#### 5. Get Single Transfer
```http
GET /api/v1/wallet/transfers/{id}
```

#### 6. Get Transfer Statistics
```http
GET /api/v1/wallet/transfer/stats
```

**Response:**
```json
{
    "success": true,
    "data": {
        "today": {
            "sent_amount": 5000,
            "sent_count": 3,
            "received_amount": 2000,
            "received_count": 2
        },
        "this_month": {
            "sent_amount": 25000,
            "sent_count": 15,
            "received_amount": 18000,
            "received_count": 12
        },
        "limits": {
            "daily_limit": 50000,
            "daily_used": 5000,
            "daily_remaining": 45000,
            "daily_count_limit": 20,
            "daily_count_used": 3
        }
    }
}
```

### Vendor Endpoints
Same endpoints available under `/api/v1/vendor/wallet/transfer`

## Admin Panel (Filament)

### Wallet Transfer Resource
Location: **Subscriptions → Wallet Transfers**

#### Features:
1. **View All Transfers**
   - Filterable by status, sender/receiver type, amount
   - Search by reference number or user names
   - Badge showing count of flagged transfers

2. **Review Flagged Transfers**
   - Auto-flagged high-value transfers (≥ 5,000 EGP)
   - Manual flagging capability
   - Approve or review flagged transfers

3. **Transfer Details**
   - Complete sender and receiver information
   - Amount breakdown (amount, fee, total)
   - Security data (IP, device info)
   - Linked transactions

## Configuration

Edit `/config/api.php`:

```php
'wallet_transfer' => [
    // Fee settings
    'fee_percentage' => env('WALLET_TRANSFER_FEE_PERCENTAGE', 1),
    'fee_fixed' => env('WALLET_TRANSFER_FEE_FIXED', 0),
    
    // Limits
    'min_amount' => env('WALLET_TRANSFER_MIN_AMOUNT', 10),
    'max_amount' => env('WALLET_TRANSFER_MAX_AMOUNT', 10000),
    'daily_limit' => env('WALLET_TRANSFER_DAILY_LIMIT', 50000),
    'daily_count_limit' => env('WALLET_TRANSFER_DAILY_COUNT_LIMIT', 20),
    
    // Feature toggles
    'enabled' => env('WALLET_TRANSFER_ENABLED', true),
    'require_verification' => env('WALLET_TRANSFER_REQUIRE_VERIFICATION', true),
    
    // Fraud detection
    'auto_flag_suspicious' => env('WALLET_TRANSFER_AUTO_FLAG', true),
    'suspicious_amount_threshold' => env('WALLET_TRANSFER_SUSPICIOUS_THRESHOLD', 5000),
],
```

## Environment Variables

Add to `.env`:

```env
# Wallet Transfer Settings
WALLET_TRANSFER_FEE_PERCENTAGE=1
WALLET_TRANSFER_FEE_FIXED=0
WALLET_TRANSFER_MIN_AMOUNT=10
WALLET_TRANSFER_MAX_AMOUNT=10000
WALLET_TRANSFER_DAILY_LIMIT=50000
WALLET_TRANSFER_DAILY_COUNT_LIMIT=20
WALLET_TRANSFER_ENABLED=true
WALLET_TRANSFER_REQUIRE_VERIFICATION=true
WALLET_TRANSFER_AUTO_FLAG=true
WALLET_TRANSFER_SUSPICIOUS_THRESHOLD=5000
```

## Database Schema

### `wallet_transfers` Table
```sql
- id
- from_wallet_id (foreign key to wallets)
- from_user_type (Vendor or User class name)
- from_user_id
- to_wallet_id (foreign key to wallets)
- to_user_type
- to_user_id
- amount (transferred amount)
- fee (transfer fee charged)
- total_deducted (amount + fee)
- amount_received (amount receiver gets)
- reference_number (unique TRF-XXXXXXXXXXXX)
- description
- status (pending, completed, failed, cancelled)
- ip_address
- user_agent
- device_info (JSON)
- is_flagged
- flagged_reason
- flagged_at
- reviewed_by (foreign key to admins)
- reviewed_at
- sender_transaction_id (foreign key to transactions)
- receiver_transaction_id (foreign key to transactions)
- timestamps
```

## Security Best Practices

1. **Always validate on server-side** - Never trust client input
2. **Monitor flagged transfers** - Review high-value transfers regularly
3. **Adjust limits** based on your business needs
4. **Enable verification** for production environments
5. **Log suspicious patterns** - Monitor for unusual transfer patterns
6. **Rate limiting** prevents abuse - Don't increase without good reason
7. **Keep fee reasonable** - High fees discourage legitimate use

## Transaction Flow

1. User initiates transfer via API
2. System validates:
   - Feature enabled
   - Amount within limits
   - Sufficient balance
   - Daily limits not exceeded
3. Calculate fee
4. Begin database transaction:
   - Deduct from sender (amount + fee)
   - Add to receiver (amount only)
   - Create transfer record
   - Create both transaction records
   - Auto-flag if needed
5. Commit transaction
6. Return success response

## Error Handling

Common error messages:
- `تحويل الرصيد غير متاح حالياً` - Feature disabled
- `لا يمكن التحويل إلى نفس المحفظة` - Same wallet transfer
- `المبلغ أقل من الحد الأدنى للتحويل` - Below minimum
- `المبلغ أكبر من الحد الأقصى للتحويل` - Above maximum
- `الرصيد غير كافي لإتمام التحويل` - Insufficient balance
- `تجاوز الحد اليومي للتحويلات` - Daily limit exceeded
- `تجاوز عدد التحويلات اليومية المسموحة` - Daily count exceeded
- `تم تجاوز الحد المسموح. حاول مرة أخرى بعد X ثانية` - Rate limited

## Testing

### Test Transfer Between Users
```bash
# Get user tokens first
# Then make transfer request
curl -X POST http://your-domain.com/api/v1/wallet/transfer \
  -H "Authorization: Bearer USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "recipient_type": "user",
    "recipient_id": 2,
    "amount": 100,
    "description": "Test transfer"
  }'
```

### Test Fee Calculation
```bash
curl -X POST http://your-domain.com/api/v1/wallet/transfer/calculate-fee \
  -H "Authorization: Bearer USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount": 1000}'
```

## Migration

Run migrations:
```bash
php artisan migrate
```

This will create the `wallet_transfers` table.

## Models

- `App\Models\Wallet` - Polymorphic wallet model
- `App\Models\WalletTransfer` - Transfer records
- `App\Models\Transaction` - Individual wallet transactions

## Future Enhancements

Potential features to add:
1. Transfer scheduling (delayed transfers)
2. Recurring transfers
3. Transfer templates (save frequent recipients)
4. Multi-signature transfers (require approval)
5. Transfer reversal (within timeframe)
6. SMS/Email notifications
7. Transfer receipts (PDF generation)
8. Bulk transfers
9. Transfer analytics dashboard
10. Integration with external payment gateways

---

**Last Updated:** January 9, 2026
**Version:** 1.0.0
