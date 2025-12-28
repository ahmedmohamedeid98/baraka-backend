<?php

namespace App\Filament\Resources\VendorOrderResource\Pages;

use App\Filament\Resources\VendorOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorOrder extends CreateRecord
{
    protected static string $resource = VendorOrderResource::class;
}
