# Wallet Charge Request System

## Overview
Complete wallet charge request system allowing users and vendors to request wallet top-ups by uploading payment screenshots. Admins review and approve/reject requests through the Filament admin panel.

## Features
- ✅ Users/Vendors can submit charge requests with payment screenshots
- ✅ Support for multiple payment methods (Vodafone Cash, Instapay, Bank Transfer)
- ✅ Admin approval/rejection workflow with Arabic default reasons
- ✅ Automatic wallet crediting on approval
- ✅ Transaction record creation
- ✅ Rejection with customizable reasons
- ✅ Ability to resubmit rejected requests
- ✅ Payment screenshot preview in admin panel
- ✅ Rate limiting on submissions (10 per hour)
- ✅ Request tracking and statistics

## Database Schema

### Table: `wallet_charge_requests`
```sql
- id (bigint, primary key)
- wallet_id (bigint, foreign key to wallets)
- user_type (varchar) - Polymorphic: App\Models\User or App\Models\Vendor
- user_id (bigint) - Polymorphic user/vendor ID
- amount (decimal 10,2) - Requested charge amount
- payment_method (enum) - vodafone_cash, instapay, bank_transfer, other
- payment_screenshot (varchar) - Path to uploaded screenshot
- payment_reference (varchar, nullable) - Payment reference number
- notes (text, nullable) - User notes
- status (enum) - pending, approved, rejected
- reviewed_by (bigint, nullable, foreign key to admins)
- reviewed_at (timestamp, nullable)
- rejection_reason (text, nullable)
- is_resubmission (boolean, default false)
- original_request_id (bigint, nullable, self-reference)
- transaction_id (bigint, nullable, foreign key to transactions)
- created_at, updated_at
```

## Model: WalletChargeRequest

### Constants
```php
// Status
STATUS_PENDING = 'pending'
STATUS_APPROVED = 'approved'
STATUS_REJECTED = 'rejected'

// Payment Methods
PAYMENT_VODAFONE_CASH = 'vodafone_cash'
PAYMENT_INSTAPAY = 'instapay'
PAYMENT_BANK_TRANSFER = 'bank_transfer'
PAYMENT_OTHER = 'other'
```

### Relationships
- `wallet()` - BelongsTo Wallet
- `reviewedBy()` - BelongsTo Admin
- `transaction()` - BelongsTo Transaction
- `originalRequest()` - BelongsTo WalletChargeRequest (for resubmissions)
- `resubmissions()` - HasMany WalletChargeRequest

### Scopes
- `pending()` - Filter pending requests
- `approved()` - Filter approved requests
- `rejected()` - Filter rejected requests
- `forUser($userType, $userId)` - Filter by user

### Methods
#### `approve($adminId)`
Approves the charge request:
1. Credits the wallet with the requested amount
2. Creates a transaction record
3. Updates status to 'approved'
4. Records reviewer and timestamp

```php
$request->approve($admin->id);
```

#### `reject($adminId, $reason)`
Rejects the charge request:
1. Updates status to 'rejected'
2. Stores rejection reason
3. Records reviewer and timestamp

```php
$request->reject($admin->id, 'الصورة غير واضحة');
```

#### `canBeResubmitted()`
Checks if a rejected request can be resubmitted.

### Accessors
- `statusText` - Arabic status text
- `paymentMethodText` - Arabic payment method text
- `screenshotUrl` - Full URL to payment screenshot
- `userName` - User/Vendor name

## API Endpoints

### For Users (auth:sanctum)
Base URL: `/api/v1/wallet/charge-requests`

#### 1. List Charge Requests
```http
GET /api/v1/wallet/charge-requests
```

**Query Parameters:**
- `status` (optional): pending, approved, rejected
- `per_page` (optional): Items per page (max 50, default 20)

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "amount": 500.00,
      "payment_method": "vodafone_cash",
      "payment_method_text": "فودافون كاش",
      "payment_reference": "REF123456",
      "payment_screenshot": "http://example.com/storage/wallet-charge-requests/screenshot.jpg",
      "notes": "تحويل من رقم 01234567890",
      "status": "pending",
      "status_text": "قيد الانتظار",
      "rejection_reason": null,
      "is_resubmission": false,
      "original_request_id": null,
      "can_be_resubmitted": false,
      "reviewed_by": null,
      "reviewed_at": null,
      "transaction_id": null,
      "created_at": "2024-01-09T12:00:00Z",
      "updated_at": "2024-01-09T12:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

#### 2. Get Single Request
```http
GET /api/v1/wallet/charge-requests/{id}
```

**Response:** Same structure as list item

#### 3. Submit New Request
```http
POST /api/v1/wallet/charge-requests
```

**Rate Limit:** 10 requests per hour

**Form Data:**
- `amount` (required): numeric, min:10, max:50000
- `payment_method` (required): vodafone_cash, instapay, bank_transfer, other
- `payment_screenshot` (required): image file (jpeg, jpg, png, gif), max 5MB
- `payment_reference` (optional): string, max 255 chars
- `notes` (optional): string, max 1000 chars

**Example:**
```javascript
const formData = new FormData();
formData.append('amount', '500');
formData.append('payment_method', 'vodafone_cash');
formData.append('payment_screenshot', screenshotFile);
formData.append('payment_reference', 'REF123456');
formData.append('notes', 'تحويل من رقم 01234567890');

fetch('/api/v1/wallet/charge-requests', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  },
  body: formData
});
```

**Response:**
```json
{
  "status": "success",
  "message": "تم إرسال الطلب بنجاح",
  "data": {
    "request": { /* charge request object */ }
  }
}
```

#### 4. Resubmit Rejected Request
```http
PUT /api/v1/wallet/charge-requests/{id}
```

**Rate Limit:** 10 requests per hour

**Form Data (all optional):**
- `amount` (optional): Update amount
- `payment_screenshot` (optional): New screenshot
- `payment_reference` (optional): Update reference
- `notes` (optional): Update notes

**Response:**
```json
{
  "status": "success",
  "message": "تم إعادة إرسال الطلب بنجاح",
  "data": {
    "request": { /* new charge request object */ }
  }
}
```

#### 5. Delete Pending Request
```http
DELETE /api/v1/wallet/charge-requests/{id}
```

**Response:**
```json
{
  "status": "success",
  "message": "تم حذف الطلب بنجاح"
}
```

#### 6. Get Statistics
```http
GET /api/v1/wallet/charge-requests/stats
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "pending_count": 3,
    "pending_amount": 1500.00,
    "approved_count": 10,
    "approved_amount": 5000.00,
    "rejected_count": 2,
    "rejected_amount": 800.00,
    "total_charged": 5000.00
  }
}
```

### For Vendors (auth:sanctum)
Base URL: `/api/v1/vendor/wallet/charge-requests`

All endpoints are identical to user endpoints, just with `/vendor` prefix.

## Admin Panel (Filament)

### Navigation
- Location: المحافظ والمعاملات > طلبات شحن المحفظة
- Badge: Shows count of pending requests
- Icon: Banknotes

### List View Features
- **Tabs:**
  - الكل (All)
  - قيد الانتظار (Pending) - with badge count
  - موافق عليه (Approved)
  - مرفوض (Rejected)

- **Columns:**
  - Request ID
  - User name (searchable)
  - Amount
  - Payment method (colored badge)
  - Payment screenshot (thumbnail)
  - Status (colored badge)
  - Resubmission indicator
  - Reviewer name
  - Created date

- **Filters:**
  - Status
  - Payment method
  - Resubmissions only
  - Date range

- **Actions:**
  - View details
  - Approve (pending only)
  - Reject with reason (pending only)

### Approve Action
1. Click "الموافقة" button
2. Confirm the action
3. System will:
   - Credit wallet with amount
   - Create transaction record
   - Update status to approved
   - Record admin and timestamp
4. Success notification shown

### Reject Action
1. Click "الرفض" button
2. Select rejection reason from dropdown (or custom)
3. Confirm rejection
4. System will:
   - Update status to rejected
   - Save rejection reason
   - Record admin and timestamp
   - Allow user to resubmit
5. Success notification shown

### Default Rejection Reasons (Arabic)
1. الصورة غير واضحة أو غير مقروءة
2. المبلغ المدفوع لا يطابق المبلغ المطلوب
3. معلومات الدفع غير صحيحة أو مفقودة
4. لم يتم استلام الدفعة بعد
5. الصورة مكررة أو مستخدمة من قبل
6. طريقة الدفع غير مدعومة
7. البيانات المدخلة غير كاملة
8. يرجى التواصل مع الدعم الفني
9. سبب مخصص (اكتب السبب) - Custom reason

### View Details Page
Displays:
- Request information (ID, user, amount, payment method, reference, status, date)
- Payment screenshot (large preview, clickable to full size)
- Wallet information (current balance)
- Review information (reviewer, date, reason if rejected)
- Transaction ID (if approved)
- Action buttons in header (approve/reject for pending)

## User Workflow

### Submit Request
1. User navigates to wallet charge section
2. Selects payment method (Vodafone Cash/Instapay/etc)
3. Makes payment via selected method
4. Takes screenshot of payment receipt
5. Fills form:
   - Amount
   - Payment method
   - Upload screenshot
   - Payment reference (optional)
   - Notes (optional)
6. Submits request
7. Receives confirmation with pending status

### Track Request
1. User can view all their requests
2. Filter by status
3. View details of each request
4. Check approval/rejection status
5. View rejection reason if rejected

### Resubmit Rejected Request
1. User views rejected request
2. Sees rejection reason
3. Can update:
   - Amount (if wrong)
   - Upload new screenshot (if unclear)
   - Update reference
   - Add notes
4. Submits as new request (marked as resubmission)
5. Original request remains in history

## Admin Workflow

### Review Pending Requests
1. Admin opens "طلبات شحن المحفظة"
2. Sees pending requests tab with badge count
3. Reviews each request:
   - Checks payment screenshot
   - Verifies amount
   - Validates payment method
   - Reads user notes

### Approve Request
1. Click "الموافقة" on pending request
2. Confirm approval
3. Wallet is automatically credited
4. Transaction is created
5. User is notified (if notifications enabled)

### Reject Request
1. Click "الرفض" on pending request
2. Select appropriate rejection reason:
   - Use predefined Arabic reason
   - Or write custom reason
3. Confirm rejection
4. User can see reason and resubmit

## Security Features

1. **Authentication Required:** All endpoints require Sanctum authentication
2. **Authorization:** Users can only view their own requests
3. **Rate Limiting:** 
   - Submit: 10 requests per hour
   - Resubmit: 10 requests per hour
4. **File Validation:**
   - Only images allowed (jpeg, jpg, png, gif)
   - Max file size: 5MB
   - Stored in public/storage/wallet-charge-requests
5. **Amount Limits:**
   - Minimum: 10 SAR
   - Maximum: 50,000 SAR
6. **Database Transactions:** Approval process uses DB transactions for atomicity

## Testing

### Test Charge Request Submission
```bash
# Using cURL
curl -X POST http://localhost/api/v1/wallet/charge-requests \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -F "amount=500" \
  -F "payment_method=vodafone_cash" \
  -F "payment_screenshot=@/path/to/screenshot.jpg" \
  -F "payment_reference=REF123456" \
  -F "notes=Test payment"
```

### Test in Admin Panel
1. Access admin panel: /admin
2. Navigate to "طلبات شحن المحفظة"
3. Create test request via API
4. Verify it appears in pending tab
5. Test approve action
6. Verify wallet balance increased
7. Test reject action with different reasons
8. Test view details page

## Integration with Existing Systems

### Wallet Integration
```php
// When request is approved
$wallet = $request->wallet;
$wallet->credit($request->amount, 'charge_request_approved', [
    'charge_request_id' => $request->id,
    'payment_method' => $request->payment_method,
]);
```

### Transaction Integration
```php
// Transaction is automatically created
$transaction = Transaction::create([
    'wallet_id' => $wallet->id,
    'type' => 'credit',
    'amount' => $request->amount,
    'balance_after' => $wallet->balance,
    'description' => 'شحن محفظة - طلب #' . $request->id,
    // ... other fields
]);
```

### Notification Integration (Optional)
```php
// Add in approve() method
$user = $request->wallet->walletable;
$user->notify(new WalletChargedNotification($request));

// Add in reject() method
$user->notify(new ChargeRequestRejectedNotification($request));
```

## Files Created

### Models
- `app/Models/WalletChargeRequest.php`

### Migrations
- `database/migrations/2026_01_09_044822_create_wallet_charge_requests_table.php`

### Controllers
- `app/Http/Controllers/Api/WalletChargeRequestController.php`

### Resources
- `app/Http/Resources/WalletChargeRequestResource.php`

### Filament
- `app/Filament/Resources/WalletChargeRequestResource.php`
- `app/Filament/Resources/WalletChargeRequestResource/Pages/ListWalletChargeRequests.php`
- `app/Filament/Resources/WalletChargeRequestResource/Pages/ViewWalletChargeRequest.php`

### Views
- `resources/views/filament/resources/wallet-charge-request-resource/pages/view-wallet-charge-request.blade.php`

### Routes
- Updated `routes/api.php` with charge request endpoints

## Configuration

No additional configuration required. Uses existing:
- Storage: `public` disk for screenshots
- Authentication: Sanctum
- Database: PostgreSQL

## Future Enhancements

Potential improvements:
1. Email notifications on approval/rejection
2. SMS notifications
3. Push notifications
4. Multiple screenshot uploads
5. Payment verification via payment gateway APIs
6. Auto-approval for trusted users
7. Fraud detection patterns
8. Admin bulk actions
9. Export requests to Excel
10. Payment method-specific forms

## Support

For issues or questions:
1. Check model methods and scopes
2. Review API endpoint documentation
3. Test with provided examples
4. Check logs in `storage/logs/laravel.log`
5. Verify database constraints and foreign keys
