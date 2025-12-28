# Favorites API Documentation

The Favorites API allows users to manage their favorite products, add and remove products from their wishlist.

## Important Feature: `is_favorite` Field

All product resources now include an `is_favorite` boolean field that automatically indicates whether the current authenticated user has favorited that product. This feature:

- ✅ **Returns `true`** if the product is in the user's favorites
- ✅ **Returns `false`** if the product is not favorited or user is not authenticated
- ✅ **Cached for performance** - User's favorites list is cached for 60 minutes
- ✅ **Available in all product endpoints** - Products list, product details, search results, etc.
- ✅ **Real-time updates** - Cache is automatically cleared when favorites change

**Example Product Response with `is_favorite`:**
```json
{
  "id": 1,
  "name": "Fresh Tomatoes",
  "price": 25.50,
  "is_favorite": true,  // ← This field
  "vendor": {...},
  "category": {...}
}
```

## Authentication
All favorite endpoints require authentication using Bearer token.

```bash
Authorization: Bearer {your_token}
```

## Endpoints

### 1. Get All Favorite Products

Retrieve a paginated list of user's favorite products.

**Endpoint:** `GET /api/v1/favorites`

**Headers:**
```
Authorization: Bearer {token}
lang: ar  # or 'en' for English
```

**Query Parameters:**
- `per_page` (optional, integer, max 50): Number of items per page. Default: 20

**Success Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Fresh Tomatoes",
      "name_ar": "طماطم طازجة",
      "description": "Fresh organic tomatoes",
      "description_ar": "طماطم عضوية طازجة",
      "price": 25.50,
      "stock": 100,
      "unit": "kg",
      "images": [
        "https://cdn.example.com/products/tomatoes.jpg"
      ],
      "is_active": true,
      "is_favorite": true,
      "vendor": {
        "id": 1,
        "business_name": "Fresh Market",
        "business_name_ar": "سوق الطازج"
      },
      "category": {
        "id": 1,
        "name": "Vegetables",
        "name_ar": "خضروات"
      }
    }
  ],
  "pagination": {
    "total": 15,
    "per_page": 20,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 15
  }
}
```

**Example Request:**

```bash
curl -X GET "https://api.example.com/api/v1/favorites?per_page=10" \
  -H "Authorization: Bearer your_token_here" \
  -H "lang: ar"
```

---

### 2. Add Product to Favorites

Add a product to the user's favorites list.

**Endpoint:** `POST /api/v1/favorites`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
lang: ar
```

**Request Body:**
```json
{
  "product_id": 1
}
```

**Success Response (201 Created):**

```json
{
  "success": true,
  "message": "تم إضافة المنتج إلى المفضلة",
  "data": {
    "id": 1,
    "name": "Fresh Tomatoes",
    "name_ar": "طماطم طازجة",
    "price": 25.50,
    "stock": 100,
    "vendor": {
      "id": 1,
      "business_name": "Fresh Market"
    }
  }
}
```

**Error Response - Already Exists (400 Bad Request):**

```json
{
  "success": false,
  "message": "المنتج موجود بالفعل في المفضلة"
}
```

**Error Response - Product Not Found (404 Not Found):**

```json
{
  "success": false,
  "message": "Product not found"
}
```

**Example Request:**

```bash
curl -X POST "https://api.example.com/api/v1/favorites" \
  -H "Authorization: Bearer your_token_here" \
  -H "Content-Type: application/json" \
  -H "lang: ar" \
  -d '{
    "product_id": 1
  }'
```

---

### 3. Remove Product from Favorites

Remove a specific product from the user's favorites.

**Endpoint:** `DELETE /api/v1/favorites/{product_id}`

**Headers:**
```
Authorization: Bearer {token}
lang: ar
```

**URL Parameters:**
- `product_id` (required, integer): The ID of the product to remove

**Success Response (200 OK):**

```json
{
  "success": true,
  "message": "تم إزالة المنتج من المفضلة",
  "data": null
}
```

**Error Response - Not Found (404 Not Found):**

```json
{
  "success": false,
  "message": "المنتج غير موجود في المفضلة"
}
```

**Example Request:**

```bash
curl -X DELETE "https://api.example.com/api/v1/favorites/1" \
  -H "Authorization: Bearer your_token_here" \
  -H "lang: ar"
```

---

### 4. Toggle Favorite Status

Toggle the favorite status of a product (add if not favorited, remove if already favorited).

**Endpoint:** `POST /api/v1/favorites/toggle`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
lang: ar
```

**Request Body:**
```json
{
  "product_id": 1
}
```

**Success Response - Added (200 OK):**

```json
{
  "success": true,
  "message": "تم إضافة المنتج إلى المفضلة",
  "data": {
    "is_favorite": true
  }
}
```

**Success Response - Removed (200 OK):**

```json
{
  "success": true,
  "message": "تم إزالة المنتج من المفضلة",
  "data": {
    "is_favorite": false
  }
}
```

**Example Request:**

```bash
curl -X POST "https://api.example.com/api/v1/favorites/toggle" \
  -H "Authorization: Bearer your_token_here" \
  -H "Content-Type: application/json" \
  -H "lang: ar" \
  -d '{
    "product_id": 1
  }'
```

---

### 5. Check Favorite Status

Check if a specific product is in the user's favorites.

**Endpoint:** `GET /api/v1/favorites/check/{product_id}`

**Headers:**
```
Authorization: Bearer {token}
lang: ar
```

**URL Parameters:**
- `product_id` (required, integer): The ID of the product to check

**Success Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "is_favorite": true
  }
}
```

**Example Request:**

```bash
curl -X GET "https://api.example.com/api/v1/favorites/check/1" \
  -H "Authorization: Bearer your_token_here" \
  -H "lang: ar"
```

---

## Response Messages

### Arabic Messages (lang: ar)
- **Added:** "تم إضافة المنتج إلى المفضلة"
- **Removed:** "تم إزالة المنتج من المفضلة"
- **Already Exists:** "المنتج موجود بالفعل في المفضلة"
- **Not Found:** "المنتج غير موجود في المفضلة"

### English Messages (lang: en)
- **Added:** "Product added to favorites"
- **Removed:** "Product removed from favorites"
- **Already Exists:** "Product is already in favorites"
- **Not Found:** "Product not found in favorites"

---

## Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "المنتج موجود بالفعل في المفضلة"
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "المنتج غير موجود في المفضلة"
}
```

### 422 Unprocessable Entity (Validation Error)
```json
{
  "success": false,
  "message": "The product id field is required.",
  "errors": {
    "product_id": [
      "The product id field is required."
    ]
  }
}
```

---

## Common Use Cases

### 1. Display Favorites List in App

```javascript
// Fetch user favorites
const response = await fetch('https://api.example.com/api/v1/favorites', {
  headers: {
    'Authorization': 'Bearer ' + token,
    'lang': 'ar'
  }
});
const data = await response.json();
// Display data.data array in UI
```

### 2. Add to Favorites Button

```javascript
async function addToFavorites(productId) {
  const response = await fetch('https://api.example.com/api/v1/favorites', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json',
      'lang': 'ar'
    },
    body: JSON.stringify({ product_id: productId })
  });
  
  const data = await response.json();
  if (data.success) {
    // Show success message
    alert(data.message);
  }
}
```

### 3. Toggle Favorite Icon

```javascript
async function toggleFavorite(productId) {
  const response = await fetch('https://api.example.com/api/v1/favorites/toggle', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json',
      'lang': 'ar'
    },
    body: JSON.stringify({ product_id: productId })
  });
  
  const data = await response.json();
  // Update UI icon based on data.data.is_favorite
  updateFavoriteIcon(data.data.is_favorite);
}
```

### 4. Check if Product is Favorited on Page Load

```javascript
async function checkFavoriteStatus(productId) {
  const response = await fetch(`https://api.example.com/api/v1/favorites/check/${productId}`, {
    headers: {
      'Authorization': 'Bearer ' + token,
      'lang': 'ar'
    }
  });
  
  const data = await response.json();
  return data.data.is_favorite;
}
```

---

## Database Schema

The favorites are stored in the `favorites` table:

```sql
CREATE TABLE favorites (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    product_id BIGINT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

---

## Testing with Postman

### 1. Import Collection
You can import the following Postman collection to test the Favorites API:

**Collection Name:** Favorites API

**Variables:**
- `base_url`: https://api.example.com
- `token`: your_authentication_token

### 2. Test Sequence
1. Add product to favorites (POST /favorites)
2. Check favorite status (GET /favorites/check/{id})
3. Get all favorites (GET /favorites)
4. Toggle favorite (POST /favorites/toggle)
5. Remove from favorites (DELETE /favorites/{id})

---

## Best Practices

1. **Use Toggle for UI**: Use the `/toggle` endpoint for heart/favorite icon clicks
2. **Check Status**: Use `/check/{id}` when displaying product details
3. **Pagination**: Use pagination for favorites list to improve performance
4. **Error Handling**: Always handle 404 and 400 errors gracefully
5. **Cache Results**: Cache favorite status on client side when appropriate
6. **Optimistic UI**: Update UI immediately and revert on error

---

## Rate Limiting

The API has standard rate limiting applied:
- **Authenticated requests:** 60 requests per minute
- **Unauthenticated requests:** 10 requests per minute

If you exceed the rate limit, you'll receive a 429 (Too Many Requests) response.

---

## Mobile App Integration Examples

### Flutter/Dart Example

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class FavoritesService {
  final String baseUrl = 'https://api.example.com/api/v1';
  final String token;
  
  FavoritesService(this.token);
  
  // Get all favorites
  Future<List<Product>> getFavorites() async {
    final response = await http.get(
      Uri.parse('$baseUrl/favorites'),
      headers: {
        'Authorization': 'Bearer $token',
        'lang': 'ar',
      },
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return (data['data'] as List)
          .map((json) => Product.fromJson(json))
          .toList();
    }
    throw Exception('Failed to load favorites');
  }
  
  // Toggle favorite
  Future<bool> toggleFavorite(int productId) async {
    final response = await http.post(
      Uri.parse('$baseUrl/favorites/toggle'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
        'lang': 'ar',
      },
      body: json.encode({'product_id': productId}),
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return data['data']['is_favorite'];
    }
    throw Exception('Failed to toggle favorite');
  }
  
  // Check favorite status
  Future<bool> checkFavorite(int productId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/favorites/check/$productId'),
      headers: {
        'Authorization': 'Bearer $token',
        'lang': 'ar',
      },
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return data['data']['is_favorite'];
    }
    return false;
  }
}

// Usage in Widget
class ProductCard extends StatefulWidget {
  final Product product;
  
  @override
  _ProductCardState createState() => _ProductCardState();
}

class _ProductCardState extends State<ProductCard> {
  bool isFavorite = false;
  
  @override
  void initState() {
    super.initState();
    checkFavoriteStatus();
  }
  
  Future<void> checkFavoriteStatus() async {
    final service = FavoritesService(userToken);
    final status = await service.checkFavorite(widget.product.id);
    setState(() {
      isFavorite = status;
    });
  }
  
  Future<void> toggleFavorite() async {
    final service = FavoritesService(userToken);
    final newStatus = await service.toggleFavorite(widget.product.id);
    setState(() {
      isFavorite = newStatus;
    });
  }
  
  @override
  Widget build(BuildContext context) {
    return Card(
      child: Column(
        children: [
          Text(widget.product.name),
          IconButton(
            icon: Icon(
              isFavorite ? Icons.favorite : Icons.favorite_border,
              color: isFavorite ? Colors.red : Colors.grey,
            ),
            onPressed: toggleFavorite,
          ),
        ],
      ),
    );
  }
}
```

### React Native Example

```javascript
import axios from 'axios';

const API_URL = 'https://api.example.com/api/v1';

// Favorites Service
class FavoritesService {
  constructor(token) {
    this.token = token;
    this.api = axios.create({
      baseURL: API_URL,
      headers: {
        'Authorization': `Bearer ${token}`,
        'lang': 'ar'
      }
    });
  }
  
  // Get all favorites
  async getFavorites(page = 1) {
    try {
      const response = await this.api.get(`/favorites?page=${page}`);
      return response.data;
    } catch (error) {
      throw error.response.data;
    }
  }
  
  // Add to favorites
  async addToFavorites(productId) {
    try {
      const response = await this.api.post('/favorites', {
        product_id: productId
      });
      return response.data;
    } catch (error) {
      throw error.response.data;
    }
  }
  
  // Remove from favorites
  async removeFromFavorites(productId) {
    try {
      const response = await this.api.delete(`/favorites/${productId}`);
      return response.data;
    } catch (error) {
      throw error.response.data;
    }
  }
  
  // Toggle favorite
  async toggleFavorite(productId) {
    try {
      const response = await this.api.post('/favorites/toggle', {
        product_id: productId
      });
      return response.data.data.is_favorite;
    } catch (error) {
      throw error.response.data;
    }
  }
  
  // Check if favorited
  async checkFavorite(productId) {
    try {
      const response = await this.api.get(`/favorites/check/${productId}`);
      return response.data.data.is_favorite;
    } catch (error) {
      return false;
    }
  }
}

// Usage in Component
import React, { useState, useEffect } from 'react';
import { View, TouchableOpacity } from 'react-native';
import Icon from 'react-native-vector-icons/MaterialIcons';

const FavoriteButton = ({ productId, token }) => {
  const [isFavorite, setIsFavorite] = useState(false);
  const [loading, setLoading] = useState(false);
  
  const favoritesService = new FavoritesService(token);
  
  useEffect(() => {
    checkFavoriteStatus();
  }, [productId]);
  
  const checkFavoriteStatus = async () => {
    try {
      const status = await favoritesService.checkFavorite(productId);
      setIsFavorite(status);
    } catch (error) {
      console.error('Error checking favorite:', error);
    }
  };
  
  const handleToggle = async () => {
    setLoading(true);
    try {
      const newStatus = await favoritesService.toggleFavorite(productId);
      setIsFavorite(newStatus);
    } catch (error) {
      console.error('Error toggling favorite:', error);
    } finally {
      setLoading(false);
    }
  };
  
  return (
    <TouchableOpacity onPress={handleToggle} disabled={loading}>
      <Icon
        name={isFavorite ? 'favorite' : 'favorite-border'}
        size={24}
        color={isFavorite ? '#ff0000' : '#999999'}
      />
    </TouchableOpacity>
  );
};

export default FavoriteButton;

// Favorites Screen Component
const FavoritesScreen = ({ token }) => {
  const [favorites, setFavorites] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  
  const favoritesService = new FavoritesService(token);
  
  useEffect(() => {
    loadFavorites();
  }, [page]);
  
  const loadFavorites = async () => {
    try {
      const response = await favoritesService.getFavorites(page);
      setFavorites(response.data);
    } catch (error) {
      console.error('Error loading favorites:', error);
    } finally {
      setLoading(false);
    }
  };
  
  const handleRemove = async (productId) => {
    try {
      await favoritesService.removeFromFavorites(productId);
      // Refresh the list
      loadFavorites();
    } catch (error) {
      console.error('Error removing favorite:', error);
    }
  };
  
  return (
    <View>
      {/* Render favorites list */}
    </View>
  );
};
```

### Swift (iOS) Example

```swift
import Foundation

class FavoritesService {
    private let baseURL = "https://api.example.com/api/v1"
    private let token: String
    
    init(token: String) {
        self.token = token
    }
    
    // Get all favorites
    func getFavorites(completion: @escaping (Result<[Product], Error>) -> Void) {
        guard let url = URL(string: "\(baseURL)/favorites") else { return }
        
        var request = URLRequest(url: url)
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("ar", forHTTPHeaderField: "lang")
        
        URLSession.shared.dataTask(with: request) { data, response, error in
            if let error = error {
                completion(.failure(error))
                return
            }
            
            guard let data = data else { return }
            
            do {
                let response = try JSONDecoder().decode(FavoritesResponse.self, from: data)
                completion(.success(response.data))
            } catch {
                completion(.failure(error))
            }
        }.resume()
    }
    
    // Toggle favorite
    func toggleFavorite(productId: Int, completion: @escaping (Result<Bool, Error>) -> Void) {
        guard let url = URL(string: "\(baseURL)/favorites/toggle") else { return }
        
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("ar", forHTTPHeaderField: "lang")
        
        let body = ["product_id": productId]
        request.httpBody = try? JSONSerialization.data(withJSONObject: body)
        
        URLSession.shared.dataTask(with: request) { data, response, error in
            if let error = error {
                completion(.failure(error))
                return
            }
            
            guard let data = data else { return }
            
            do {
                let response = try JSONDecoder().decode(ToggleResponse.self, from: data)
                completion(.success(response.data.isFavorite))
            } catch {
                completion(.failure(error))
            }
        }.resume()
    }
}

// Usage in SwiftUI View
struct ProductCard: View {
    let product: Product
    @State private var isFavorite = false
    
    var body: some View {
        VStack {
            Text(product.name)
            Button(action: toggleFavorite) {
                Image(systemName: isFavorite ? "heart.fill" : "heart")
                    .foregroundColor(isFavorite ? .red : .gray)
            }
        }
        .onAppear {
            checkFavoriteStatus()
        }
    }
    
    func toggleFavorite() {
        let service = FavoritesService(token: userToken)
        service.toggleFavorite(productId: product.id) { result in
            switch result {
            case .success(let status):
                isFavorite = status
            case .failure(let error):
                print("Error: \(error)")
            }
        }
    }
    
    func checkFavoriteStatus() {
        // Check favorite status
    }
}
```

---

## Cache Optimization

### How Caching Works

The favorites feature uses intelligent caching to optimize performance:

1. **Cache Key:** `user:{user_id}:favorites`
2. **Cache Duration:** 60 minutes (3600 seconds)
3. **Cached Data:** Array of product IDs that the user has favorited
4. **Cache Strategy:** Read-through cache with automatic invalidation

### When Cache is Used

- ✅ **Product Listings:** When fetching products, `is_favorite` field is determined from cache
- ✅ **Product Details:** Single product details include `is_favorite` from cache
- ✅ **Check Endpoint:** `/favorites/check/{id}` uses cached data
- ✅ **Search Results:** All product search results include cached favorite status

### When Cache is Cleared

Cache is automatically cleared when:

- ✅ User adds a product to favorites (`POST /favorites`)
- ✅ User removes a product from favorites (`DELETE /favorites/{id}`)
- ✅ User toggles favorite status (`POST /favorites/toggle`)
- ✅ Cache expires after 60 minutes (auto-refresh)

### Performance Benefits

**Without Cache:**
- Each product in a list of 20 products = 20 database queries
- Product details page = 1 query per page view
- Total: High database load

**With Cache:**
- User's favorites loaded once and cached
- All 20 products check against cached array (in-memory)
- Product details use cached data
- Total: 1 database query per hour (when cache expires)

### Cache Invalidation Example

```php
// When user adds a favorite
POST /api/v1/favorites
{
  "product_id": 123
}

// Internally:
// 1. Add favorite to database
// 2. Clear cache: Cache::forget("user:1:favorites")
// 3. Next request will rebuild cache automatically
```

### Manual Cache Management

If you need to manually clear cache (e.g., during development):

```bash
# Clear all cache
php artisan cache:clear

# Clear specific user's favorites cache in tinker
php artisan tinker
>>> Cache::forget('user:1:favorites');
```

### Monitoring Cache Performance

```php
// Check if cache exists
Cache::has("user:1:favorites"); // true/false

// Get cache TTL (time to live)
// Check remaining cache time in Redis/Memcached

// Cache hit/miss logging (add to resources)
Log::info('Favorites cache hit', ['user_id' => $userId]);
```

---

## Complete Documentation

For complete API documentation with all endpoints, examples, and use cases, see **[FAVORITES_API.md](FAVORITES_API.md)**

