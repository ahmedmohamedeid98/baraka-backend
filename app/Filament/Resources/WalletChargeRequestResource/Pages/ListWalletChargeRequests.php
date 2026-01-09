<?php

namespace App\Filament\Resources\WalletChargeRequestResource\Pages;

use App\Filament\Resources\WalletChargeRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWalletChargeRequests extends ListRecords
{
    protected static string $resource = WalletChargeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - users create from API
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل'),
            'pending' => Tab::make('قيد الانتظار')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => \App\Models\WalletChargeRequest::pending()->count()),
            'approved' => Tab::make('موافق عليه')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),
            'rejected' => Tab::make('مرفوض')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
        ];
    }
}
