# E-Shop Backend - Installation & Setup Guide

## System Requirements

- PHP >= 8.2
- PostgreSQL >= 14
- Redis >= 6.0
- Composer
- Node.js >= 18 & NPM

## Installation Steps

### 1. Clone and Install Dependencies

```bash
cd /Users/mac/Documents/projects/eshop

# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Configure Environment Variables

Edit `.env` file with your settings:

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=eshop
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Cloudflare R2
R2_ACCESS_KEY_ID=your_r2_access_key
R2_SECRET_ACCESS_KEY=your_r2_secret_key
R2_BUCKET=your_bucket_name
R2_ENDPOINT=https://your_account_id.r2.cloudflarestorage.com
R2_URL=https://your_public_domain.com

# UltraMsg WhatsApp
ULTRAMSG_INSTANCE_ID=your_instance_id
ULTRAMSG_TOKEN=your_token
ULTRAMSG_ENABLED=true

# Firebase
FIREBASE_CREDENTIALS=firebase-credentials.json
```

### 4. Database Setup

```bash
# Create PostgreSQL database
createdb eshop

# Publish Spatie package migrations
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"

# Run migrations
php artisan migrate

# Seed database with initial data
php artisan db:seed
```

This will create:
- Admin user (admins table): `admin@eshop.com` / Password: `password`
- Vendor (vendors table): `vendor@eshop.com` / Password: `password`
- Sample areas (North Sinai regions)
- Sample categories

### 5. Storage Setup

```bash
# Link public storage
php artisan storage:link
```

### 6. Install Filament

```bash
# Filament is already configured via composer.json
# Run Filament install command
php artisan filament:install --panels

# Create admin user (optional, already seeded)
php artisan make:filament-user
```

### 7. Queue Worker Setup

```bash
# Run queue worker (in separate terminal)
php artisan queue:work redis --tries=3
```

For production, use Supervisor:

```ini
[program:eshop-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/eshop/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/eshop/storage/logs/worker.log
stopwaitsecs=3600
```

### 8. Redis Cache Configuration

Redis is already configured in `.env`. Test connection:

```bash
php artisan redis:ping
```

### 9. Start Development Server

```bash
# Start Laravel server
php artisan serve

# In another terminal, compile assets
npm run dev
```

## Access Points

- **Admin Panel**: http://localhost:8000/admin
  - Email: `admin@eshop.com`
  - Password: `password`
  
- **Vendor Panel**: http://localhost:8000/vendor
  - Email: `vendor@eshop.com`
  - Password: `password`
  
- **API**: http://localhost:8000/api/v1/

## Testing OTP Flow

Since UltraMsg requires actual credentials, you can test with:

1. Set `ULTRAMSG_ENABLED=false` in `.env`
2. Check OTP codes in `otp_verifications` table
3. Use the code from database to verify

```sql
SELECT phone, code, expires_at FROM otp_verifications 
WHERE phone = '01234567890' 
ORDER BY created_at DESC LIMIT 1;
```

## Firebase Setup

1. Download Firebase service account JSON from Firebase Console
2. Place it in `storage/firebase-credentials.json`
3. Update `FIREBASE_CREDENTIALS` in `.env` to `firebase-credentials.json`

**Note:** We use `kreait/firebase-php` directly (v7.x) which is compatible with Laravel 12 and PHP 8.3

## Cloudflare R2 Setup

1. Create R2 bucket in Cloudflare dashboard
2. Generate API token with R2 permissions
3. Configure custom domain for public access (optional)
4. Update R2 credentials in `.env`

## Production Deployment

### 1. Optimize Application

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
npm run build
```

### 2. Set Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 3. Configure Web Server (Nginx)

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/eshop/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 4. SSL Certificate (Let's Encrypt)

```bash
certbot --nginx -d your-domain.com
```

### 5. Schedule Cron Jobs

Add to crontab:

```bash
* * * * * cd /path/to/eshop && php artisan schedule:run >> /dev/null 2>&1
```

## Troubleshooting

### Database Connection Issues

```bash
# Test PostgreSQL connection
psql -h 127.0.0.1 -U postgres -d eshop

# Check Laravel can connect
php artisan db:show
```

### Redis Connection Issues

```bash
# Test Redis
redis-cli ping

# Test from Laravel
php artisan tinker
>>> Redis::connection()->ping();
```

### Storage Issues

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recreate storage link
php artisan storage:link
```

### Queue Not Processing

```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

## Monitoring & Logs

- Application logs: `storage/logs/laravel.log`
- Queue worker logs: Check Supervisor logs
- Web server logs: Check Nginx/Apache logs

```bash
# Tail Laravel logs
tail -f storage/logs/laravel.log

# Monitor queue in real-time
php artisan queue:listen --verbose
```

## Useful Commands

```bash
# Clear expired OTPs
php artisan tinker
>>> (new \App\Services\OtpService(app(\App\Services\UltraMsgService::class), app(\App\Services\FirebaseService::class)))->cleanExpiredOTPs();

# Create new admin
php artisan make:filament-user

# Refresh database (WARNING: Deletes all data)
php artisan migrate:fresh --seed
```
