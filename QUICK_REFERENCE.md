# Quick Reference Guide

## 🚀 Quick Start Commands

```bash
# Development
php artisan serve                    # Start server
php artisan queue:work redis         # Start queue worker
npm run dev                          # Start Vite

# Database
php artisan migrate                  # Run migrations
php artisan migrate:fresh --seed     # Fresh install with data
php artisan db:seed                  # Seed only

# First-time setup
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"

# Cache
php artisan cache:clear              # Clear cache
php artisan config:cache             # Cache config
php artisan optimize                 # Optimize for production

# Queue
php artisan queue:listen             # Monitor queue
php artisan queue:failed             # List failed jobs
php artisan queue:retry all          # Retry failed jobs
```

## 📞 Default Credentials

```
Admin Panel (/admin)
Email: admin@eshop.com
Password: password

Vendor Panel (/vendor)  
Email: vendor@eshop.com
Password: password

API Base URL
http://localhost:8000/api/v1
```

## 🔑 Essential .env Variables

```env
# Must Configure
DB_DATABASE=eshop
DB_USERNAME=postgres
DB_PASSWORD=your_password

R2_ACCESS_KEY_ID=your_key
R2_SECRET_ACCESS_KEY=your_secret
R2_BUCKET=your_bucket
R2_ENDPOINT=https://...
R2_URL=https://...

ULTRAMSG_INSTANCE_ID=your_instance
ULTRAMSG_TOKEN=your_token

FIREBASE_CREDENTIALS=storage/firebase-credentials.json
```

## 📡 Key API Endpoints

```bash
# Auth
POST /api/v1/auth/request-otp        # Get OTP code
POST /api/v1/auth/verify-otp         # Login with OTP
GET  /api/v1/me                      # Get profile

# Catalog
GET  /api/v1/products                # List products
GET  /api/v1/categories              # List categories
GET  /api/v1/vendors                 # List vendors

# Cart & Checkout
POST /api/v1/cart/items              # Add to cart
POST /api/v1/checkout                # Create order

# Orders
GET  /api/v1/orders                  # My orders
GET  /api/v1/orders/{id}/tracking    # Track order (public)
```

## 🗂️ Important Directories

```
app/Http/Controllers/Api/    → API controllers
app/Http/Resources/          → API JSON resources
app/Models/                  → Eloquent models
app/Services/                → Business logic
app/Filament/Resources/      → Admin panel
app/Filament/Vendor/         → Vendor panel
database/migrations/         → Database structure
database/seeders/            → Sample data
routes/api.php              → API routes
```

## 🔧 Common Tasks

### Create Admin User
```bash
php artisan make:filament-user
```

### Check Database Connection
```bash
php artisan db:show
```

### Test Redis
```bash
redis-cli ping
# Or in Laravel:
php artisan redis:ping
```

### View Logs
```bash
tail -f storage/logs/laravel.log
```

### Clear All Caches
```bash
php artisan optimize:clear
```

### Check Failed Jobs
```bash
php artisan queue:failed
php artisan queue:retry all
```

### Get OTP Code (Development)
```sql
-- Connect to PostgreSQL
psql -d eshop

-- Get latest OTP for phone
SELECT phone, code, expires_at, created_at 
FROM otp_verifications 
WHERE phone = '01234567890' 
ORDER BY created_at DESC 
LIMIT 1;
```

## 🐛 Troubleshooting

### Issue: Class not found
```bash
composer dump-autoload
php artisan clear-compiled
```

### Issue: Queue not working
```bash
# Check Redis is running
redis-cli ping

# Check queue connection
php artisan queue:work redis --verbose
```

### Issue: Storage images not showing
```bash
php artisan storage:link
# Ensure R2 credentials are correct
```

### Issue: Migration errors
```bash
# Drop and recreate
php artisan migrate:fresh --seed

# Or rollback one step
php artisan migrate:rollback --step=1
```

## 📊 Database Quick Reference

### Main Tables
- `users` - Customer/vendor/admin accounts
- `vendors` - Vendor stores
- `products` - Product catalog
- `categories` - Product categories
- `orders` - Customer orders
- `addresses` - Delivery addresses
- `areas` - Delivery zones (North Sinai)
- `coupons` - Discount codes

### System Tables
- `otp_verifications` - OTP codes
- `whatsapp_logs` - WhatsApp message logs
- `notifications` - In-app notifications
- `banners` - Promotional banners

## 🎯 Testing OTP Flow

### With WhatsApp (Production)
1. Set `ULTRAMSG_ENABLED=true`
2. Configure UltraMsg credentials
3. Request OTP via API
4. Receive code on WhatsApp

### Without WhatsApp (Development)
1. Set `ULTRAMSG_ENABLED=false`
2. Request OTP via API
3. Check database for code:
```sql
SELECT code FROM otp_verifications 
WHERE phone = 'YOUR_PHONE' 
ORDER BY created_at DESC LIMIT 1;
```
4. Use code to verify

## 📦 Postman Testing

1. Import `postman_collection.json`
2. Set base_url: `http://localhost:8000/api/v1`
3. Request OTP
4. Verify OTP → copy token
5. Set token variable
6. Test authenticated endpoints

## 🔒 Roles & Permissions

```
admin    → Full access to admin panel
vendor   → Access to vendor panel (own data only)
customer → API access only
```

Assign roles:
```php
php artisan tinker
>>> $user = User::find(1);
>>> $user->assignRole('admin');
```

## 📈 Production Deployment

```bash
# 1. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build

# 2. Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage

# 3. Setup queue worker (Supervisor)
# See INSTALLATION.md

# 4. Setup cron
* * * * * php /path/to/artisan schedule:run
```

## 🌐 Environment Checklist

**Development**
- [ ] PostgreSQL running
- [ ] Redis running
- [ ] `.env` configured
- [ ] Migrations run
- [ ] Queue worker running

**Production**
- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] Database configured
- [ ] R2 configured
- [ ] UltraMsg configured
- [ ] Firebase configured
- [ ] Queue worker (Supervisor)
- [ ] Cron job setup
- [ ] SSL certificate
- [ ] Backups configured

## 📞 Support Links

- Laravel Docs: https://laravel.com/docs
- Filament Docs: https://filamentphp.com/docs
- PostgreSQL Docs: https://postgresql.org/docs
- Redis Docs: https://redis.io/docs
- R2 Docs: https://developers.cloudflare.com/r2
- UltraMsg: https://ultramsg.com

---

**Last Updated:** December 2025  
**Version:** 1.0.0
