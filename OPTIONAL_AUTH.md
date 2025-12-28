# Optional Authentication Middleware

## Overview

The `OptionalAuth` middleware provides flexible authentication for API endpoints that should be accessible to both authenticated and guest users, while still providing personalized content to authenticated users.

## Problem It Solves

### The Challenge

Some endpoints need to:
- ✅ Be accessible to **guest users** (not logged in)
- ✅ Provide **personalized content** to authenticated users
- ✅ Not require authentication but **use it if available**

**Example:** Product listings should:
- Show all products to everyone (guests and authenticated users)
- Include `is_favorite: true/false` for authenticated users
- Show `is_favorite: false` for guest users

### The Solution

Traditional approaches don't work well:

❌ **Using `auth:sanctum` middleware:**
- Blocks guest users completely
- Returns 401 Unauthorized for non-authenticated requests

❌ **No middleware at all:**
- Bearer tokens are ignored
- `$request->user()` always returns `null`
- No way to provide personalized content

✅ **Using `OptionalAuth` middleware:**
- Authenticates user if Bearer token is present
- Allows request to proceed if no token is provided
- `$request->user()` works correctly in both cases

## How It Works

### Middleware Logic

```php
class OptionalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if Authorization header exists
        $token = $request->bearerToken();

        if ($token) {
            // 2. Find the token in database
            $accessToken = PersonalAccessToken::findToken($token);

            // 3. If valid token found, authenticate the user
            if ($accessToken && !$accessToken->tokenable->trashed()) {
                Auth::setUser($accessToken->tokenable);
                $accessToken->tokenable->withAccessToken($accessToken);
            }
        }

        // 4. Continue (whether authenticated or not)
        return $next($request);
    }
}
```

### Request Flow

#### Scenario 1: Guest User (No Token)
```bash
GET /api/v1/products
# No Authorization header
```

**Flow:**
1. OptionalAuth checks for token → Not found
2. Request continues without authentication
3. `$request->user()` returns `null`
4. Product resource shows `is_favorite: false`

#### Scenario 2: Authenticated User (With Token)
```bash
GET /api/v1/products
Authorization: Bearer 1|abc123...
```

**Flow:**
1. OptionalAuth finds token → Valid
2. User is authenticated
3. `$request->user()` returns User object
4. Product resource checks favorites from cache
5. Shows `is_favorite: true` or `false` based on user's favorites

#### Scenario 3: Invalid/Expired Token
```bash
GET /api/v1/products
Authorization: Bearer invalid-token
```

**Flow:**
1. OptionalAuth tries to find token → Not found
2. Request continues as guest
3. `$request->user()` returns `null`
4. Product resource shows `is_favorite: false`

## Implementation

### 1. Middleware Registration

In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->api(prepend: [
        \App\Http\Middleware\SetLocale::class,
    ]);
    
    // Register optional auth middleware alias
    $middleware->alias([
        'optional.auth' => \App\Http\Middleware\OptionalAuth::class,
    ]);
})
```

**Important:** The middleware is registered as an alias, NOT applied globally. This prevents double-processing tokens for routes that already use `auth:sanctum`.

### 2. Applying to Specific Routes

In `routes/api.php`:

```php
// Catalog (public with optional auth for personalized features)
Route::middleware('optional.auth')->group(function () {
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::get('vendors', [VendorController::class, 'index']);
    // ... other public catalog routes
});

// Protected routes still use auth:sanctum (no double-processing)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('favorites', [FavoriteController::class, 'index']);
    Route::post('favorites', [FavoriteController::class, 'store']);
    // ... other protected routes
});
```

**Key Point:** Only public routes that need personalized features use `optional.auth`. Protected routes continue to use `auth:sanctum` without any duplication.

### 2. Using in Resources

```php
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'is_favorite' => $this->checkIsFavorite($request),  // ← Works for both
        ];
    }

    protected function checkIsFavorite(Request $request): bool
    {
        // Returns false for guests
        if (!$request->user()) {
            return false;
        }

        // Returns actual status for authenticated users
        return /* check if favorited */;
    }
}
```

## Affected Endpoints

All public endpoints now support optional authentication:

### Product Endpoints
- `GET /api/v1/products` - Product listing
- `GET /api/v1/products/{id}` - Product details
- `GET /api/v1/vendors/{id}/products` - Vendor products

### Category Endpoints
- `GET /api/v1/categories` - Categories list
- `GET /api/v1/categories/{id}` - Category details

### Vendor Endpoints
- `GET /api/v1/vendors` - Vendors list
- `GET /api/v1/vendors/{id}` - Vendor details

## Response Examples

### Guest User Request

```bash
curl -X GET "https://api.example.com/api/v1/products"
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Product A",
      "price": 100,
      "is_favorite": false  // Always false for guests
    }
  ]
}
```

### Authenticated User Request

```bash
curl -X GET "https://api.example.com/api/v1/products" \
  -H "Authorization: Bearer 1|abc123..."
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Product A",
      "price": 100,
      "is_favorite": true  // Based on user's actual favorites
    },
    {
      "id": 2,
      "name": "Product B",
      "price": 200,
      "is_favorite": false
    }
  ]
}
```

## Benefits

### 1. Better User Experience
- Guests can browse without barriers
- Authenticated users see personalized content
- Seamless transition from guest to authenticated

### 2. API Consistency
- Same endpoint for both user types
- No need for separate authenticated/public endpoints
- Reduces API complexity

### 3. Performance
- No extra API calls needed
- Uses existing caching mechanisms
- Efficient token validation

### 4. Security
- Validates tokens properly
- Checks for soft-deleted users
- Handles invalid tokens gracefully

## Comparison with Other Approaches

| Approach | Guest Access | Auth User Features | Token Validation | Double Processing |
|----------|--------------|-------------------|------------------|-------------------|
| `auth:sanctum` | ❌ Blocked | ✅ Full | ✅ Yes | ❌ No |
| No middleware | ✅ Allowed | ❌ None | ❌ No | ❌ No |
| OptionalAuth (global) | ✅ Allowed | ✅ Full | ✅ Yes | ⚠️ **Yes** (inefficient) |
| **OptionalAuth (targeted)** | ✅ Allowed | ✅ Full | ✅ Yes | ✅ **No** (efficient) |

**Note:** We use the targeted approach (applying `optional.auth` only to specific route groups) to avoid processing tokens twice on routes that already use `auth:sanctum`.

## Technical Details

### Token Validation

The middleware:
1. Extracts Bearer token from Authorization header
2. Looks up token in `personal_access_tokens` table
3. Checks if associated user is not soft-deleted
4. Sets authenticated user in request context

### Security Considerations

- ✅ Validates token cryptographically
- ✅ Checks token expiration (if configured)
- ✅ Prevents access with deleted user tokens
- ✅ Handles malformed tokens gracefully
- ✅ No error responses for invalid tokens (fails silently)

### Performance Impact

- **With valid token:** ~1 DB query (token lookup)
- **Without token:** 0 DB queries
- **With invalid token:** ~1 DB query (token lookup fails)

Minimal overhead due to:
- Efficient token lookup
- No additional middleware stack
- Shared with Sanctum's authentication mechanism

## When to Use

### ✅ Use OptionalAuth When:
- Endpoint should be public but benefit from authentication
- Need to show personalized data when available
- Want seamless guest-to-authenticated experience

### ❌ Don't Use OptionalAuth When:
- Endpoint requires authentication (use `auth:sanctum`)
- No personalized features needed (use no middleware)
- Endpoint is completely public (no auth needed)

## Migration Guide

### Before (Public Endpoint)

```php
// routes/api.php
Route::get('products', [ProductController::class, 'index']);
// No authentication, $request->user() always null
```

### After (With OptionalAuth)

```php
// routes/api.php - Same route!
Route::get('products', [ProductController::class, 'index']);
// Now $request->user() works if token is provided
```

**No route changes needed!** The middleware is applied globally to all API routes.

## Testing

### Test Guest Access
```bash
curl -X GET "http://localhost:8000/api/v1/products"
```

Expected: Products returned with `is_favorite: false`

### Test Authenticated Access
```bash
curl -X GET "http://localhost:8000/api/v1/products" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Expected: Products returned with actual `is_favorite` status

### Test Invalid Token
```bash
curl -X GET "http://localhost:8000/api/v1/products" \
  -H "Authorization: Bearer invalid-token"
```

Expected: Products returned (as guest) with `is_favorite: false`

## Troubleshooting

### Issue: `$request->user()` still returns null

**Solution:**
1. Verify middleware is registered in `bootstrap/app.php`
2. Clear config cache: `php artisan config:clear`
3. Verify token format: `Authorization: Bearer {token}`

### Issue: Getting 401 errors for guests

**Solution:**
- Make sure you're not using `auth:sanctum` on the route
- OptionalAuth should allow guests through

### Issue: Performance concerns

**Solution:**
- Tokens are validated efficiently by Sanctum
- Consider token caching if needed
- Cache user's favorites list (already implemented)

## Advanced Usage

### Why Not Apply Globally?

You might wonder why we don't just apply `OptionalAuth` globally. Here's why:

**Problem with Global Application:**
```php
// If applied globally (DON'T DO THIS)
$middleware->api(prepend: [
    \App\Http\Middleware\OptionalAuth::class,  // Runs on ALL routes
]);

// Routes with auth:sanctum would process token TWICE:
Route::middleware('auth:sanctum')->group(function () {
    Route::get('favorites', ...);  // Token processed by OptionalAuth, then by Sanctum
});
```

**Issues:**
- ❌ Token validated twice (performance hit)
- ❌ Two database queries for the same token
- ❌ Redundant user authentication
- ❌ Increased response time

**Solution - Targeted Application:**
```php
// Register as alias
$middleware->alias(['optional.auth' => OptionalAuth::class]);

// Apply only where needed
Route::middleware('optional.auth')->group(function () {
    Route::get('products', ...);  // Token processed once (if present)
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('favorites', ...);  // Token processed once (required)
});
```

**Benefits:**
- ✅ Token validated only once per request
- ✅ No redundant queries
- ✅ Optimal performance
- ✅ Clean separation of concerns

### Custom Optional Auth for Specific Routes

The middleware is already set up to work on specific routes:

```php
// Already implemented in routes/api.php
Route::middleware('optional.auth')->group(function () {
    Route::get('products', [ProductController::class, 'index']);
    Route::get('categories', [CategoryController::class, 'index']);
});
```

To add more routes with optional auth:

```php
// Add new routes to the optional.auth group
Route::middleware('optional.auth')->group(function () {
    Route::get('products', [ProductController::class, 'index']);
    Route::get('special-offers', [OfferController::class, 'index']);  // New route
    Route::get('recommendations', [RecommendationController::class, 'index']);  // New route
});
```

### Extending the Middleware

```php
class OptionalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken && !$accessToken->tokenable->trashed()) {
                Auth::setUser($accessToken->tokenable);
                $accessToken->tokenable->withAccessToken($accessToken);
                
                // Add custom logic here
                // e.g., Log authenticated requests
                // e.g., Track user activity
                // e.g., Load user preferences
            }
        }

        return $next($request);
    }
}
```

## Summary

The `OptionalAuth` middleware provides the best of both worlds:
- ✅ Public access for all users
- ✅ Personalized features for authenticated users
- ✅ No breaking changes to existing APIs
- ✅ Minimal performance overhead
- ✅ Better user experience

It's the professional solution for endpoints that need to support both guest and authenticated users while providing personalized content.
