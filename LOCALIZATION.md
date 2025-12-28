# API Localization

The API now supports English and Arabic localization for all error and success messages.

## Usage

### Set Language via Header
Send the `lang` header with your API requests:

```bash
# Arabic (default)
curl -H "lang: ar" https://api.example.com/api/v1/...

# English
curl -H "lang: en" https://api.example.com/api/v1/...
```

**Note:** If no `lang` header is provided, the API defaults to Arabic.

## Supported Languages

- **Arabic (ar)** - Default
- **English (en)**

## Example Responses

### Arabic Response (Default)
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": { ... }
}
```

### English Response
```json
{
  "success": true,
  "message": "Login successful",
  "data": { ... }
}
```

## Available Message Keys

All localized messages are stored in:
- `lang/en/messages.php` - English translations
- `lang/ar/messages.php` - Arabic translations

### Message Categories

1. **OTP Messages** (`messages.otp.*`)
   - `invalid_or_expired` - Invalid or expired OTP code
   - `request_processed` - OTP request processed
   - `sent_to_new_phone` - OTP sent to new phone number

2. **Authentication Messages** (`messages.auth.*`)
   - `login_successful` - Login successful
   - `logout_successful` - Logged out successfully

3. **Profile Messages** (`messages.profile.*`)
   - `updated_successfully` - Profile updated
   - `avatar_updated` - Avatar updated
   - `avatar_deleted` - Avatar deleted
   - `phone_updated` - Phone number updated
   - `fcm_token_updated` - FCM token updated

4. **Address Messages** (`messages.address.*`)
   - `created_successfully` - Address created
   - `updated_successfully` - Address updated
   - `deleted_successfully` - Address deleted

5. **Cart Messages** (`messages.cart.*`)
   - `item_added` - Item added to cart
   - `item_updated` - Cart item updated
   - `item_removed` - Cart item removed
   - `cleared` - Cart cleared
   - `insufficient_stock` - Insufficient stock
   - `empty` - Cart is empty

6. **Order Messages** (`messages.order.*`)
   - `created_successfully` - Order placed successfully
   - `cancelled_successfully` - Order cancelled
   - `cannot_cancel` - Cannot cancel order
   - `single_vendor_only` - Single vendor restriction
   - `creation_failed` - Order creation failed

7. **Coupon Messages** (`messages.coupon.*`)
   - `invalid` - Invalid coupon code
   - `cannot_be_used` - Coupon cannot be used
   - `expired` - Coupon expired
   - `usage_limit_reached` - Usage limit reached
   - `minimum_not_met` - Minimum amount not met

8. **Smart Order Messages** (`messages.smart_order.*`)
   - `analyzed_successfully` - Order analyzed
   - `updated_successfully` - Smart order updated
   - `deleted_successfully` - Smart order deleted
   - `retrieved_successfully` - Order retrieved

## Adding New Translations

To add new translations:

1. Add the English text in `lang/en/messages.php`
2. Add the Arabic translation in `lang/ar/messages.php`
3. Use it in your controller: `__('messages.category.key')`

Example:
```php
return $this->successResponse($data, __('messages.order.created_successfully'));
```
