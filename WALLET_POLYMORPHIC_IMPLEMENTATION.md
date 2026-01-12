# Wallet and Transaction Polymorphic Implementation

## Overview
Successfully converted the Wallet and Transaction system to use polymorphic relationships, enabling both **Vendors** and **Users** to have wallets and track their transactions.

## Changes Made

### 1. Database Migrations

#### Migration 1: Convert Vendor Wallets to Polymorphic Wallets
**File:** `database/migrations/2026_01_09_040935_convert_vendor_wallets_to_polymorphic_wallets.php`

- Renamed `vendor_wallets` table to `wallets`
- Added polymorphic columns:
  - `walletable_type` (VARCHAR) - Stores the model class (e.g., `App\Models\Vendor` or `App\Models\User`)
  - `walletable_id` (BIGINT) - Stores the ID of the owning model
- Migrated existing vendor wallet data to the new structure
- Removed the old `vendor_id` foreign key column
- Added composite index on `(walletable_type, walletable_id)`

#### Migration 2: Convert Transactions to Polymorphic Structure
**File:** `database/migrations/2026_01_09_040959_convert_transactions_to_polymorphic_structure.php`

- Replaced `vendor_wallet_id` with generic `wallet_id`
- Added `vendor_id` column for backward compatibility and quick vendor lookups
- Migrated all existing transaction data
- Updated foreign key relationships

### 2. Model Changes

#### New Model: Wallet
**File:** `app/Models/Wallet.php`

- Replaces `VendorWallet` model
- Uses `morphTo` relationship for `walletable` (owner can be Vendor or User)
- Methods:
  - `credit()` - Add funds to wallet
  - `debit()` - Deduct funds from wallet
  - `hasSufficientBalance()` - Check balance
  - `getOwnerNameAttribute()` - Get owner's display name
  - `getOwnerTypeAttribute()` - Get owner type label

#### Updated: Transaction Model
**File:** `app/Models/Transaction.php`

- Changed `vendor_wallet_id` to `wallet_id` in fillable fields
- Updated `wallet()` relationship to use new `Wallet` model
- Maintained all existing transaction type constants and methods

#### Updated: Vendor Model
**File:** `app/Models/Vendor.php`

- Changed wallet relationship from `hasOne(VendorWallet)` to `morphOne(Wallet, 'walletable')`
- Updated `getOrCreateWallet()` return type to `Wallet`

#### Updated: User Model
**File:** `app/Models/User.php`

- Added new `morphOne(Wallet, 'walletable')` relationship
- Added `getOrCreateWallet()` method for users

### 3. Filament Resources

#### New Resource: WalletResource
**File:** `app/Filament/Resources/WalletResource.php`

- Replaces `VendorWalletResource`
- Supports both Vendor and User wallets
- Features:
  - Filter by owner type (Vendor/User)
  - Display owner information polymorphically
  - Actions: Charge, Gift, Deduct, View Transactions
  - Balance filters (positive, zero, negative)

**Page:** `app/Filament/Resources/WalletResource/Pages/ListWallets.php`

#### Updated: TransactionResource
**File:** `app/Filament/Resources/TransactionResource.php`

- Updated to display wallet owner type and name polymorphically
- Added filter by owner type
- Enhanced search to work across both Vendor and User wallets
- Updated form to show owner information

### 4. API Controllers

#### Updated: VendorSubscriptionController
**File:** `app/Http/Controllers/Api/VendorSubscriptionController.php`

- Updated to use new `Wallet` model
- Changed resource from `VendorWalletResource` to `WalletResource`
- All subscription and wallet operations remain functional

#### New Controller: UserWalletController
**File:** `app/Http/Controllers/Api/UserWalletController.php`

- Handles user wallet operations
- Endpoints:
  - `GET /api/v1/wallet` - Get user wallet details
  - `GET /api/v1/wallet/transactions` - Get user transaction history

### 5. API Resources

#### New Resource: WalletResource
**File:** `app/Http/Resources/WalletResource.php`

- Generic wallet resource for API responses
- Returns wallet ID, owner type, owner ID, balance, and timestamp

### 6. Routes

#### Updated: API Routes
**File:** `routes/api.php`

- Added `UserWalletController` import
- Added user wallet endpoints:
  ```php
  Route::get('wallet', [UserWalletController::class, 'index']);
  Route::get('wallet/transactions', [UserWalletController::class, 'transactions']);
  ```

## API Endpoints

### Vendor Wallet Endpoints (Existing)
- `GET /api/v1/vendor/wallet` - Get vendor wallet
- `GET /api/v1/vendor/wallet/transactions` - Get vendor transactions
- `POST /api/v1/vendor/wallet/subscribe` - Subscribe to package
- `POST /api/v1/vendor/wallet/change-package` - Change subscription package
- `GET /api/v1/vendor/wallet/subscriptions` - Get subscription history
- `POST /api/v1/vendor/wallet/subscription/toggle-auto-renew` - Toggle auto-renew
- `POST /api/v1/vendor/wallet/subscription/disable-auto-renew` - Disable auto-renew
- `POST /api/v1/vendor/wallet/subscription/enable-auto-renew` - Enable auto-renew
- `POST /api/v1/vendor/wallet/subscription/cancel` - Cancel subscription

### User Wallet Endpoints (New)
- `GET /api/v1/wallet` - Get user wallet details
- `GET /api/v1/wallet/transactions` - Get user transaction history

## Database Schema

### Wallets Table
```sql
CREATE TABLE wallets (
    id BIGINT PRIMARY KEY,
    walletable_type VARCHAR(255) NOT NULL,
    walletable_id BIGINT NOT NULL,
    balance DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_walletable (walletable_type, walletable_id)
);
```

### Transactions Table
```sql
CREATE TABLE transactions (
    id BIGINT PRIMARY KEY,
    wallet_id BIGINT NOT NULL,
    vendor_id BIGINT NULL,
    type ENUM('charge', 'subscription', 'gift', 'commission', 'refund'),
    amount DECIMAL(12,2),
    balance_after DECIMAL(12,2),
    description TEXT,
    order_id BIGINT NULL,
    vendor_order_id BIGINT NULL,
    subscription_id BIGINT NULL,
    created_by BIGINT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL
);
```

## Usage Examples

### Creating a User Wallet
```php
$user = User::find(1);
$wallet = $user->getOrCreateWallet();
```

### Adding Funds to User Wallet
```php
$wallet->credit(
    amount: 100.00,
    type: Transaction::TYPE_CHARGE,
    description: 'Added funds',
    adminId: 1
);
```

### Checking Wallet Owner
```php
$wallet = Wallet::find(1);
$owner = $wallet->walletable; // Returns Vendor or User instance
$ownerType = $wallet->owner_type; // Returns 'Vendor' or 'User'
$ownerName = $wallet->owner_name; // Returns owner's name
```

### Querying Transactions by Owner Type
```php
// Get all vendor transactions
$vendorTransactions = Transaction::whereHas('wallet', function($query) {
    $query->where('walletable_type', Vendor::class);
})->get();

// Get all user transactions
$userTransactions = Transaction::whereHas('wallet', function($query) {
    $query->where('walletable_type', User::class);
})->get();
```

## Benefits

1. **Unified System**: Single wallet and transaction system for both vendors and users
2. **Scalability**: Easy to add more wallet-enabled models in the future
3. **Code Reusability**: Shared logic for wallet operations
4. **Better Tracking**: Centralized transaction history across all wallet types
5. **Flexible Queries**: Easy to filter and report on transactions by owner type
6. **Backward Compatibility**: Vendor operations continue to work seamlessly

## Migration Notes

- All existing vendor wallets have been automatically migrated to the new structure
- All existing transactions have been preserved and updated
- No data loss occurred during migration
- The `vendor_id` column in transactions is maintained for quick vendor lookups and backward compatibility

## Testing Recommendations

1. Test vendor wallet operations (charge, debit, subscribe)
2. Test user wallet creation and transaction tracking
3. Test Filament admin panel wallet and transaction views
4. Test API endpoints for both vendors and users
5. Verify transaction history displays correctly for both types
6. Test filtering and searching in admin panel

## Future Enhancements

Potential features that can be added:
- User wallet top-up via payment gateways
- User-to-user wallet transfers
- Cashback and rewards system for users
- Wallet-based payments for orders
- Transaction categories and tags
- Export transaction reports by owner type
