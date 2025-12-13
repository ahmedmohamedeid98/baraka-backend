<?php

namespace App\Filament\Resources\VendorWalletResource\Pages;

use App\Filament\Resources\VendorWalletResource;
use Filament\Resources\Pages\ListRecords;

class ListVendorWallets extends ListRecords
{
    protected static string $resource = VendorWalletResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
