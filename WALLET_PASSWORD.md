# Wallet Password Feature

## Overview
Added a 4-digit password protection feature for wallets. Users and vendors can set a password to secure their wallet transfers.

## Database Changes
- Added `password` column to `wallets` table (nullable, hashed using bcrypt)

## Model Changes (Wallet.php)
New methods added:
- `hasPassword()`: Check if wallet has a password set
- `setPassword(string $password)`: Set a new 4-digit password (validates format)
- `verifyPassword(string $password)`: Verify provided password against stored hash

## Password Requirements
- Must be exactly 4 digits (0-9)
- Stored as bcrypt hash for security
- Required for all transfer operations once set

## API Endpoints

### 1. Check Password Status
**GET** `/api/v1/{guard}/wallet/password/status`

**Response:**
```json
{
    "has_password": true,
    "password_required_for_transfer": true
}
```

### 2. Set Password (First Time)
**POST** `/api/v1/{guard}/wallet/password/set`

**Request:**
```json
{
    "password": "1234",
    "password_confirmation": "1234",
    "verification_code": 1234
}
```

**Response:**
```json
{
    "message": "تم تعيين كلمة المرور بنجاح"
}
```

**Validation:**
- Password must be exactly 4 digits
- Password confirmation must match
- Verification code required
- Cannot set if password already exists

### 3. Change Password
**POST** `/api/v1/{guard}/wallet/password/change`

**Request:**
```json
{
    "old_password": "1234",
    "new_password": "5678",
    "new_password_confirmation": "5678",
    "verification_code": 1234
}
```

**Response:**
```json
{
    "message": "تم تغيير كلمة المرور بنجاح"
}
```

**Validation:**
- Old password must be correct
- New password must be different from old password
- New password must be 4 digits
- Verification code required

### 4. Reset Password (Forgot Password)
**POST** `/api/v1/{guard}/wallet/password/reset`

**Request:**
```json
{
    "new_password": "5678",
    "new_password_confirmation": "5678",
    "verification_code": 1234
}
```

**Response:**
```json
{
    "message": "تم إعادة تعيين كلمة المرور بنجاح"
}
```

**Validation:**
- Verification code required
- New password must be 4 digits

### 5. Transfer with Password
**POST** `/api/v1/{guard}/wallet/transfer`

**Updated Request:**
```json
{
    "phone": "01234567890",
    "amount": 100,
    "description": "Payment for order",
    "verification_code": 1234,
    "wallet_password": "1234"
}
```

**Notes:**
- `wallet_password` is optional if no password is set
- `wallet_password` is required if wallet has password
- Transfer will fail with error if password is incorrect

## Error Messages

| Error | Arabic Message |
|-------|---------------|
| Password required | كلمة مرور المحفظة مطلوبة |
| Incorrect password | كلمة مرور المحفظة غير صحيحة |
| Password already exists | تم تعيين كلمة مرور بالفعل. استخدم تحديث كلمة المرور لتغييرها |
| No password set | لم يتم تعيين كلمة مرور. استخدم تعيين كلمة المرور أولاً |
| Wrong old password | كلمة المرور القديمة غير صحيحة |
| Invalid format | كلمة المرور يجب أن تكون 4 أرقام فقط |
| Invalid verification code | رمز التحقق غير صحيح |

## Security Features

1. **Password Hashing**: All passwords are stored using bcrypt hashing
2. **Verification Required**: All password operations require verification code
3. **Hidden in API**: Password field is hidden from API responses
4. **4-Digit Format**: Simple but secure PIN format
5. **Transfer Protection**: Transfers blocked without correct password once set

## Usage Flow

### First Time Setup
1. User calls `/wallet/password/status` to check if password exists
2. If no password, user calls `/wallet/password/set` with verification code
3. Password is now required for all transfers

### Changing Password
1. User calls `/wallet/password/change`
2. Must provide old password, new password, and verification code
3. Password is updated

### Forgot Password
1. User calls `/wallet/password/reset`
2. Must provide verification code (sent to phone)
3. New password is set

### Making Transfer
1. User initiates transfer with `/wallet/transfer`
2. If wallet has password, must include `wallet_password` field
3. Transfer proceeds if password is correct

## Available Guards
- `user` - Regular users
- `vendor` - Vendors

All endpoints support both guards:
- `/api/v1/user/wallet/password/*`
- `/api/v1/vendor/wallet/password/*`
