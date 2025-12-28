<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateQrCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qr:generate 
                            {url : The URL to generate QR code for}
                            {--name= : Custom name for the QR code file (optional)}
                            {--size=300 : Size of the QR code in pixels}
                            {--margin=2 : Margin around the QR code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate QR code for a given URL and save as PNG and SVG in local storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        $size = (int) $this->option('size');
        $margin = (int) $this->option('margin');
        $customName = $this->option('name');

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('Invalid URL provided!');
            return Command::FAILURE;
        }

        $this->info('Generating QR code for: ' . $url);

        // Generate file name
        $fileName = $customName 
            ? Str::slug($customName) 
            : 'qr-' . Str::slug(parse_url($url, PHP_URL_HOST) ?? 'code') . '-' . time();

        try {
            // Create qrcodes directory if it doesn't exist
            $directory = 'qrcodes';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            // Generate PNG format
            $pngPath = $directory . '/' . $fileName . '.png';
            $pngContent = QrCode::format('png')
                ->size($size)
                ->margin($margin)
                ->errorCorrection('H')
                ->generate($url);
            
            Storage::disk('public')->put($pngPath, $pngContent);
            $this->info('✓ PNG saved: storage/app/public/' . $pngPath);

            // Generate SVG format
            $svgPath = $directory . '/' . $fileName . '.svg';
            $svgContent = QrCode::format('svg')
                ->size($size)
                ->margin($margin)
                ->errorCorrection('H')
                ->generate($url);
            
            Storage::disk('public')->put($svgPath, $svgContent);
            $this->info('✓ SVG saved: storage/app/public/' . $svgPath);

            // Display full paths
            $this->newLine();
            $this->info('Full paths:');
            $this->line('PNG: ' . Storage::disk('public')->path($pngPath));
            $this->line('SVG: ' . Storage::disk('public')->path($svgPath));
            
            // Display public URLs
            $this->newLine();
            $this->info('Public URLs:');
            $this->line('PNG: ' . asset('storage/' . $pngPath));
            $this->line('SVG: ' . asset('storage/' . $svgPath));

            $this->newLine();
            $this->success('QR codes generated successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to generate QR code: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display a success message.
     */
    protected function success($message)
    {
        $this->getOutput()->writeln("<fg=green>$message</>");
    }
}
