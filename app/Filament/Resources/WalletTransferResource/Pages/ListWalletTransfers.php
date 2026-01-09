<?php

namespace App\Filament\Resources\WalletTransferResource\Pages;

use App\Filament\Resources\WalletTransferResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListWalletTransfers extends ListRecords
{
    protected static string $resource = WalletTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - transfers are created via API only
        ];
    }
}
