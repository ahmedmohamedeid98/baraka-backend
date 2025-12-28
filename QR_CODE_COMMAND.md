# QR Code Generator Command

A Laravel Artisan command to generate QR codes for any URL and save them in both PNG and SVG formats.

## Installation

The QR code generation package is already installed:
```bash
composer require simplesoftwareio/simple-qrcode
```

## Usage

### Basic Command

```bash
php artisan qr:generate <url>
```

### Command Syntax

```bash
php artisan qr:generate {url} [options]
```

### Arguments

- **url** (required): The URL to generate QR code for

### Options

- `--name`: Custom name for the QR code file (optional)
- `--size`: Size of the QR code in pixels (default: 300)
- `--margin`: Margin around the QR code (default: 2)

## Examples

### 1. Generate QR Code with Auto-Generated Name

```bash
php artisan qr:generate "https://example.com"
```

**Output:**
```
Generating QR code for: https://example.com
✓ PNG saved: storage/app/public/qrcodes/qr-example-com-1735234567.png
✓ SVG saved: storage/app/public/qrcodes/qr-example-com-1735234567.svg

Full paths:
PNG: /path/to/storage/app/public/qrcodes/qr-example-com-1735234567.png
SVG: /path/to/storage/app/public/qrcodes/qr-example-com-1735234567.svg

Public URLs:
PNG: http://yoursite.test/storage/qrcodes/qr-example-com-1735234567.png
SVG: http://yoursite.test/storage/qrcodes/qr-example-com-1735234567.svg

QR codes generated successfully!
```

### 2. Generate QR Code with Custom Name

```bash
php artisan qr:generate "https://example.com" --name="my-website"
```

This will create:
- `storage/app/public/qrcodes/my-website.png`
- `storage/app/public/qrcodes/my-website.svg`

### 3. Generate QR Code with Custom Size

```bash
php artisan qr:generate "https://example.com" --name="large-qr" --size=500
```

Creates a 500x500 pixel QR code.

### 4. Generate QR Code with Custom Margin

```bash
php artisan qr:generate "https://example.com" --name="qr-with-margin" --margin=5
```

Creates a QR code with more spacing around the edges.

### 5. Complete Example with All Options

```bash
php artisan qr:generate "https://mywebsite.com/products/123" --name="product-123" --size=400 --margin=3
```

## Common Use Cases

### Generate QR Code for Order Tracking

```bash
php artisan qr:generate "https://yoursite.com/orders/track/ORD-12345" --name="order-12345-tracking"
```

### Generate QR Code for Product Page

```bash
php artisan qr:generate "https://yoursite.com/products/456" --name="product-456"
```

### Generate QR Code for Restaurant Menu

```bash
php artisan qr:generate "https://yoursite.com/menu" --name="restaurant-menu" --size=600
```

### Generate QR Code for Payment Link

```bash
php artisan qr:generate "https://pay.yoursite.com/invoice/789" --name="invoice-789-payment"
```

### Generate QR Code for Event Registration

```bash
php artisan qr:generate "https://yoursite.com/events/register/101" --name="event-101-registration"
```

## File Storage

### Storage Location
Files are saved to: `storage/app/public/qrcodes/`

### Public Access
After running `php artisan storage:link`, files are accessible via:
```
http://yoursite.com/storage/qrcodes/filename.png
http://yoursite.com/storage/qrcodes/filename.svg
```

### Creating Storage Link (if not already created)

```bash
php artisan storage:link
```

This creates a symbolic link from `public/storage` to `storage/app/public`.

## Output Formats

### PNG Format
- **Advantages:** 
  - Universal support
  - Good for printing
  - Works in all image viewers
- **Use Case:** Email attachments, printing, general use

### SVG Format
- **Advantages:**
  - Scalable without quality loss
  - Smaller file size
  - Perfect for web and high-resolution displays
- **Use Case:** Websites, large format printing, responsive designs

## Technical Details

### QR Code Specifications
- **Error Correction:** High (H level) - can recover from up to 30% damage
- **Default Size:** 300x300 pixels
- **Default Margin:** 2 units
- **Encoding:** UTF-8

### Error Correction Levels
The command uses 'H' (High) error correction, which means:
- Can recover from up to 30% of data damage
- More reliable for printing and outdoor use
- Slightly larger QR codes

## Using in Code

### Generate QR Code Programmatically

```php
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

// Generate PNG
$pngContent = QrCode::format('png')
    ->size(300)
    ->margin(2)
    ->errorCorrection('H')
    ->generate('https://example.com');

Storage::disk('public')->put('qrcodes/my-qr.png', $pngContent);

// Generate SVG
$svgContent = QrCode::format('svg')
    ->size(300)
    ->margin(2)
    ->errorCorrection('H')
    ->generate('https://example.com');

Storage::disk('public')->put('qrcodes/my-qr.svg', $svgContent);
```

### Display QR Code Directly in View

```php
// In Controller
use SimpleSoftwareIO\QrCode\Facades\QrCode;

public function showQrCode()
{
    $qrCode = QrCode::size(300)->generate('https://example.com');
    return view('qrcode', compact('qrCode'));
}
```

```html
<!-- In Blade View -->
<div>
    {!! $qrCode !!}
</div>
```

## Automation Examples

### Generate QR Codes in Bulk

Create a bash script to generate multiple QR codes:

```bash
#!/bin/bash

# Generate QR codes for multiple URLs
urls=(
    "https://example.com/page1"
    "https://example.com/page2"
    "https://example.com/page3"
)

for url in "${urls[@]}"
do
    php artisan qr:generate "$url"
done
```

### Schedule QR Code Generation

In `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Generate daily QR code for today's menu
    $schedule->call(function () {
        Artisan::call('qr:generate', [
            'url' => 'https://example.com/menu/today',
            '--name' => 'daily-menu-' . date('Y-m-d')
        ]);
    })->daily();
}
```

## Troubleshooting

### Error: "Invalid URL provided!"
Make sure the URL includes the protocol (http:// or https://):
```bash
# Wrong
php artisan qr:generate "example.com"

# Correct
php artisan qr:generate "https://example.com"
```

### Error: "Failed to generate QR code"
1. Check storage permissions:
```bash
chmod -R 775 storage
```

2. Ensure storage link exists:
```bash
php artisan storage:link
```

### Files Not Accessible Publicly
Run the storage link command:
```bash
php artisan storage:link
```

### Large File Sizes
- Reduce the `--size` option
- SVG files are typically smaller and scale better
- PNG files can be optimized with image compression tools

## Best Practices

1. **Naming Convention:** Use descriptive names that identify the purpose
   ```bash
   php artisan qr:generate "https://site.com" --name="home-page-qr"
   ```

2. **Size Recommendations:**
   - Small prints (business cards): 200-300px
   - Medium prints (flyers): 400-600px
   - Large prints (posters): 800-1200px

3. **Testing:** Always test the generated QR code with multiple QR code readers

4. **URL Shortening:** For better QR codes, use shorter URLs when possible

5. **High Contrast:** QR codes work best with high contrast backgrounds

## Security Considerations

- Never generate QR codes for sensitive URLs without proper authentication
- Consider using signed URLs for temporary access
- Regularly clean up old QR code files
- Validate and sanitize URLs before generation

## Clean Up Old QR Codes

```bash
# Delete QR codes older than 30 days
find storage/app/public/qrcodes -name "*.png" -mtime +30 -delete
find storage/app/public/qrcodes -name "*.svg" -mtime +30 -delete
```

## Help Command

To see all available options:
```bash
php artisan qr:generate --help
```

## Additional Resources

- [SimpleSoftwareIO QrCode Documentation](https://www.simplesoftwareio.com/docs/simple-qrcode)
- [QR Code Specifications](https://www.qrcode.com/en/about/standards.html)
