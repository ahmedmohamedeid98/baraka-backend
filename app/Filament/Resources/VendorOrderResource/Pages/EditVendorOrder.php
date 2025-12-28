<?php

namespace App\Filament\Resources\VendorOrderResource\Pages;

use App\Filament\Resources\VendorOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendorOrder extends EditRecord
{
    protected static string $resource = VendorOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
