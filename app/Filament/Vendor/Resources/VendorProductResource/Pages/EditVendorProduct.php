<?php

namespace App\Filament\Vendor\Resources\VendorProductResource\Pages;

use App\Filament\Vendor\Resources\VendorProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendorProduct extends EditRecord
{
    protected static string $resource = VendorProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
