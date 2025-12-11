# E-Shop API Documentation

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication

All authenticated endpoints require a Bearer token in the Authorization header:

```
Authorization: Bearer {token}
```

---

## Authentication Endpoints

### Request OTP
Request an OTP code via WhatsApp (or SMS fallback)

**POST** `/auth/request-otp`

**Body:**
```json
{
  "phone": "01234567890"
}
```

**Response (WhatsApp):**
```json
{
  "success": true,
  "message": "OTP request processed",
  "data": {
    "method": "whatsapp",
    "use_sms": false,
    "message": "OTP sent via WhatsApp"
  }
}
```

**Response (SMS Fallback):**
```json
{
  "success": true,
  "message": "OTP request processed",
  "data": {
    "method": "sms",
    "use_sms": true,
    "message": "Please use SMS verification"
  }
}
```

### Verify OTP
Verify OTP code and login

**POST** `/auth/verify-otp`

**Body:**
```json
{
  "phone": "01234567890",
  "code": "123456",
  "method": "whatsapp"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": null,
      "phone": "01234567890",
      "email": null,
      "is_active": true,
      "phone_verified_at": "2024-01-15T10:30:00.000000Z",
      "created_at": "2024-01-15T10:30:00.000000Z"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890"
  }
}
```

### Get User Profile
Get authenticated user data

**GET** `/me`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "phone": "01234567890",
    "email": "john@example.com",
    "is_active": true,
    "phone_verified_at": "2024-01-15T10:30:00.000000Z",
    "created_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

### Update Profile
Update user profile

**PUT** `/me`

**Headers:** `Authorization: Bearer {token}`

**Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com"
}
```

### Update FCM Token
Update Firebase Cloud Messaging token for push notifications

**POST** `/me/fcm-token`

**Headers:** `Authorization: Bearer {token}`

**Body:**
```json
{
  "token": "firebase_fcm_token_here"
}
```

### Logout
Logout and revoke current access token

**POST** `/auth/logout`

**Headers:** `Authorization: Bearer {token}`

---

## Catalog Endpoints

### Get Categories
Get all active categories

**GET** `/categories`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "إلكترونيات",
      "name_ar": "إلكترونيات",
      "name_en": "Electronics",
      "slug": "electronics",
      "image": null,
      "description": "أفضل الإلكترونيات",
      "parent_id": null,
      "children": []
    }
  ]
}
```

### Get Products
Get all products with optional filters

**GET** `/products`

**Query Parameters:**
- `category_id` (optional): Filter by category
- `vendor_id` (optional): Filter by vendor
- `featured` (optional): Show only featured products
- `search` (optional): Search by name
- `sort_by` (optional): `price`, `created_at`, `views_count`, `orders_count`
- `sort_order` (optional): `asc`, `desc`
- `per_page` (optional): Results per page (max 100)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "iPhone 15",
      "name_ar": "آيفون 15",
      "name_en": "iPhone 15",
      "slug": "iphone-15",
      "description": "أحدث موبايل من آبل",
      "price": 45000.00,
      "compare_price": 50000.00,
      "has_discount": true,
      "discount_percentage": 10,
      "stock": 50,
      "images": ["https://r2.example.com/products/iphone.jpg"],
      "first_image": "https://r2.example.com/products/iphone.jpg",
      "is_featured": true,
      "views_count": 1250,
      "vendor": {
        "id": 1,
        "name": "متجر الإلكترونيات"
      },
      "category": {
        "id": 1,
        "name": "إلكترونيات"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 95,
    "from": 1,
    "to": 20
  }
}
```

### Get Single Product
Get product details

**GET** `/products/{id}`

### Get Vendors
Get all active and approved vendors

**GET** `/vendors`

### Get Vendor Products
Get products from specific vendor

**GET** `/vendors/{id}/products`

---

## Cart Endpoints

### Get Cart
Get current user's cart

**GET** `/cart`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "items": [
      {
        "id": 1,
        "product": {
          "id": 1,
          "name": "iPhone 15",
          "price": 45000.00,
          "first_image": "https://..."
        },
        "quantity": 2,
        "price": 45000.00,
        "subtotal": 90000.00
      }
    ],
    "subtotal": 90000.00,
    "total_items": 2
  }
}
```

### Add to Cart
Add product to cart

**POST** `/cart/items`

**Headers:** `Authorization: Bearer {token}`

**Body:**
```json
{
  "product_id": 1,
  "quantity": 2
}
```

### Update Cart Item
Update item quantity

**PUT** `/cart/items/{id}`

**Headers:** `Authorization: Bearer {token}`

**Body:**
```json
{
  "quantity": 3
}
```

### Remove Cart Item
Remove item from cart

**DELETE** `/cart/items/{id}`

**Headers:** `Authorization: Bearer {token}`

### Clear Cart
Remove all items from cart

**DELETE** `/cart`

**Headers:** `Authorization: Bearer {token}`

---

## Address Endpoints

### Get Addresses
Get all user addresses

**GET** `/addresses`

**Headers:** `Authorization: Bearer {token}`

### Create Address
Create new address

**POST** `/addresses`

**Headers:** `Authorization: Bearer {token}`

**Body (Map-based):**
```json
{
  "type": "map",
  "latitude": 31.1313,
  "longitude": 33.7975,
  "formatted_address": "العريش، شمال سيناء، مصر",
  "note": "بجوار المسجد الكبير",
  "is_default": true
}
```

**Body (Manual):**
```json
{
  "type": "manual",
  "area_id": 1,
  "street": "شارع 23 يوليو",
  "note": "عمارة رقم 5، الدور الثالث",
  "is_default": false
}
```

### Update Address
Update existing address

**PUT** `/addresses/{id}`

**Headers:** `Authorization: Bearer {token}`

### Delete Address
Delete address

**DELETE** `/addresses/{id}`

**Headers:** `Authorization: Bearer {token}`

---

## Order Endpoints

### Get Orders
Get user's orders

**GET** `/orders`

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `per_page` (optional): Results per page

### Get Order Details
Get single order with items

**GET** `/orders/{id}`

**Headers:** `Authorization: Bearer {token}`

### Checkout
Create new order

**POST** `/checkout`

**Headers:** `Authorization: Bearer {token}`

**Body:**
```json
{
  "address_id": 1,
  "coupon_code": "SAVE20",
  "notes": "يرجى الاتصال قبل التوصيل"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Order placed successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-ABC12345",
    "status": "pending",
    "payment_status": "pending",
    "payment_method": "cod",
    "vendor": {...},
    "delivery_address": "العريش، شارع 23 يوليو",
    "subtotal": 90000.00,
    "delivery_fee": 20.00,
    "discount": 18000.00,
    "total": 72020.00,
    "coupon_code": "SAVE20",
    "items": [...],
    "created_at": "2024-01-15T14:30:00.000000Z"
  }
}
```

### Cancel Order
Cancel an order

**POST** `/orders/{id}/cancel`

**Headers:** `Authorization: Bearer {token}`

**Body:**
```json
{
  "reason": "غيرت رأيي"
}
```

### Track Order
Track order status (public endpoint)

**GET** `/orders/{id}/tracking`

**Response:**
```json
{
  "success": true,
  "data": {
    "order_number": "ORD-ABC12345",
    "status": "on_the_way",
    "created_at": "2024-01-15T14:30:00.000000Z",
    "timeline": [
      {
        "status": "pending",
        "note": "Order placed",
        "created_at": "2024-01-15T14:30:00.000000Z"
      },
      {
        "status": "confirmed",
        "note": "Order confirmed by vendor",
        "created_at": "2024-01-15T14:35:00.000000Z"
      },
      {
        "status": "preparing",
        "note": "Preparing your order",
        "created_at": "2024-01-15T15:00:00.000000Z"
      },
      {
        "status": "on_the_way",
        "note": "Out for delivery",
        "created_at": "2024-01-15T15:30:00.000000Z"
      }
    ]
  }
}
```

---

## Coupon Endpoints

### Apply Coupon
Validate and apply coupon code

**POST** `/coupons/apply`

**Headers:** `Authorization: Bearer {token}`

**Body:**
```json
{
  "code": "SAVE20",
  "subtotal": 90000.00
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "code": "SAVE20",
    "type": "percentage",
    "discount": 18000.00,
    "message": "Coupon applied successfully. You save 18000 EGP"
  }
}
```

---

## Error Responses

All endpoints return errors in this format:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

**Common HTTP Status Codes:**
- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized
- `404`: Not Found
- `422`: Validation Error
- `429`: Too Many Requests
- `500`: Server Error

---

## Rate Limiting

- OTP requests: 3 per hour per phone number
- API requests: 60 per minute per user

---

## Postman Collection

Import the Postman collection file: `postman_collection.json`

It includes all endpoints with example requests and responses.
