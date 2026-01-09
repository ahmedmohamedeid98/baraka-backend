# Wallet Charge Request - Quick Reference

## API Endpoints

### User Endpoints
```
GET    /api/v1/wallet/charge-requests           - List requests
POST   /api/v1/wallet/charge-requests           - Submit new request (Rate: 10/hour)
GET    /api/v1/wallet/charge-requests/stats     - Get statistics
GET    /api/v1/wallet/charge-requests/{id}      - View single request
PUT    /api/v1/wallet/charge-requests/{id}      - Resubmit rejected (Rate: 10/hour)
DELETE /api/v1/wallet/charge-requests/{id}      - Delete pending request
```

### Vendor Endpoints
Same as above but with `/api/v1/vendor/` prefix

## Submit Request (POST)

### Form Data
```javascript
{
  amount: 500,                    // Required: 10-50000
  payment_method: "vodafone_cash", // Required: vodafone_cash|instapay|bank_transfer|other
  payment_screenshot: File,        // Required: image, max 5MB
  payment_reference: "REF123",     // Optional: string, max 255
  notes: "تحويل من رقم..."        // Optional: string, max 1000
}
```

### Response
```json
{
  "status": "success",
  "message": "تم إرسال الطلب بنجاح",
  "data": {
    "request": {
      "id": 1,
      "amount": 500.00,
      "status": "pending",
      "payment_screenshot": "http://...",
      ...
    }
  }
}
```

## Admin Panel

### Location
المحافظ والمعاملات > طلبات شحن المحفظة

### Actions
1. **الموافقة** - Approve request (credits wallet + creates transaction)
2. **الرفض** - Reject with reason (user can resubmit)

### Rejection Reasons (Arabic)
1. الصورة غير واضحة أو غير مقروءة
2. المبلغ المدفوع لا يطابق المبلغ المطلوب
3. معلومات الدفع غير صحيحة أو مفقودة
4. لم يتم استلام الدفعة بعد
5. الصورة مكررة أو مستخدمة من قبل
6. طريقة الدفع غير مدعومة
7. البيانات المدخلة غير كاملة
8. يرجى التواصل مع الدعم الفني
9. سبب مخصص (custom text)

## Model Usage

### Approve Request
```php
use App\Models\WalletChargeRequest;

$request = WalletChargeRequest::find(1);
$request->approve($adminId);
// Wallet credited, transaction created, status updated
```

### Reject Request
```php
$request->reject($adminId, 'الصورة غير واضحة');
// Status updated, reason saved, user can resubmit
```

### Query Scopes
```php
WalletChargeRequest::pending()->get();
WalletChargeRequest::approved()->get();
WalletChargeRequest::rejected()->get();
WalletChargeRequest::forUser(User::class, 1)->get();
```

## Status Flow

```
[User Submits] → pending
                   ↓
           [Admin Reviews]
          ↙              ↘
    approved          rejected
    (Wallet           (Can
    Credited)         Resubmit)
        ↓                ↓
   [Transaction]  [New Request]
                       ↓
                   pending
```

## Files Location

- Model: `app/Models/WalletChargeRequest.php`
- Controller: `app/Http/Controllers/Api/WalletChargeRequestController.php`
- Resource: `app/Http/Resources/WalletChargeRequestResource.php`
- Filament: `app/Filament/Resources/WalletChargeRequestResource.php`
- Screenshots: `storage/app/public/wallet-charge-requests/`
- Public URL: `public/storage/wallet-charge-requests/`

## Testing

### cURL Example
```bash
curl -X POST http://localhost/api/v1/wallet/charge-requests \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "amount=500" \
  -F "payment_method=vodafone_cash" \
  -F "payment_screenshot=@screenshot.jpg"
```

### Postman
1. Set method to POST
2. URL: `http://localhost/api/v1/wallet/charge-requests`
3. Headers: `Authorization: Bearer TOKEN`
4. Body: form-data with fields above
5. Send request

## Common Issues

### 413 Payload Too Large
- Screenshot exceeds 5MB
- Solution: Resize image before upload

### 422 Validation Error
- Check all required fields
- Verify payment_method value
- Ensure amount is between 10-50000

### 429 Too Many Requests
- Rate limit exceeded (10 per hour)
- Solution: Wait or increase limit in routes

### Screenshot not showing
- Check storage link: `php artisan storage:link`
- Verify file uploaded to `storage/app/public/wallet-charge-requests/`

## Rate Limits

- Submit Request: 10 per hour
- Resubmit Request: 10 per hour
- View Requests: No limit
- Get Stats: No limit
