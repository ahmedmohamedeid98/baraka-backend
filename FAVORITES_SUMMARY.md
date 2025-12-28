# Favorites Feature - Quick Reference

## Overview
The Favorites feature allows users to save their favorite products for quick access later. Users can add, remove, toggle, and check the favorite status of products.

## Files Created/Modified

### New Files
1. **Migration:** `database/migrations/2025_12_26_200030_create_favorites_table.php`
2. **Model:** `app/Models/Favorite.php`
3. **Controller:** `app/Http/Controllers/Api/FavoriteController.php`
4. **Documentation:** `FAVORITES_API.md` - Complete API documentation with examples

### Modified Files
1. **User Model:** `app/Models/User.php` - Added `favorites()` and `favoriteProducts()` relationships
2. **Routes:** `routes/api.php` - Added 5 favorite endpoints
3. **Translations:** 
   - `lang/en/messages.php` - Added English messages
   - `lang/ar/messages.php` - Added Arabic messages
4. **API Docs:** `API_DOCUMENTATION.md` - Added reference to favorites docs

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/favorites` | Get all favorite products (paginated) |
| POST | `/api/v1/favorites` | Add product to favorites |
| DELETE | `/api/v1/favorites/{product_id}` | Remove product from favorites |
| POST | `/api/v1/favorites/toggle` | Toggle favorite status |
| GET | `/api/v1/favorites/check/{product_id}` | Check if product is favorited |

## Quick Test

```bash
# 1. Add to favorites
curl -X POST "http://localhost:8000/api/v1/favorites" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "lang: ar" \
  -d '{"product_id": 1}'

# 2. Get all favorites
curl -X GET "http://localhost:8000/api/v1/favorites" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "lang: ar"

# 3. Check favorite status
curl -X GET "http://localhost:8000/api/v1/favorites/check/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "lang: ar"

# 4. Toggle favorite
curl -X POST "http://localhost:8000/api/v1/favorites/toggle" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "lang: ar" \
  -d '{"product_id": 1}'

# 5. Remove from favorites
curl -X DELETE "http://localhost:8000/api/v1/favorites/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "lang: ar"
```

## Database Schema

```sql
CREATE TABLE favorites (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    product_id BIGINT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (user_id, product_id)
);
```

## Messages (Localized)

### Arabic (Default)
- Added: "تم إضافة المنتج إلى المفضلة"
- Removed: "تم إزالة المنتج من المفضلة"
- Already Exists: "المنتج موجود بالفعل في المفضلة"
- Not Found: "المنتج غير موجود في المفضلة"

### English
- Added: "Product added to favorites"
- Removed: "Product removed from favorites"
- Already Exists: "Product is already in favorites"
- Not Found: "Product not found in favorites"

## Usage in Frontend

### React/React Native Example
```javascript
// Add to favorites
const addToFavorites = async (productId) => {
  try {
    const response = await fetch('http://localhost:8000/api/v1/favorites', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'lang': 'ar'
      },
      body: JSON.stringify({ product_id: productId })
    });
    const data = await response.json();
    return data;
  } catch (error) {
    console.error(error);
  }
};

// Toggle favorite
const toggleFavorite = async (productId) => {
  try {
    const response = await fetch('http://localhost:8000/api/v1/favorites/toggle', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'lang': 'ar'
      },
      body: JSON.stringify({ product_id: productId })
    });
    const data = await response.json();
    return data.data.is_favorite;
  } catch (error) {
    console.error(error);
  }
};
```

## Features

✅ **Add to Favorites** - Add any active product to favorites
✅ **Remove from Favorites** - Remove products from favorites
✅ **Toggle Favorite** - One-click add/remove
✅ **Check Status** - Check if product is favorited
✅ **List Favorites** - Get paginated list of favorite products
✅ **Unique Constraint** - Prevent duplicate favorites
✅ **Cascade Delete** - Auto-delete if user or product is deleted
✅ **Localized Messages** - Arabic and English support
✅ **Full Documentation** - Complete API docs with examples

## Notes

- Users can only favorite active products
- Each user can favorite a product only once
- Favorites are automatically deleted if the product or user is deleted
- The feature supports pagination for large favorite lists
- All endpoints require authentication
- Messages are localized based on the `lang` header (default: Arabic)
