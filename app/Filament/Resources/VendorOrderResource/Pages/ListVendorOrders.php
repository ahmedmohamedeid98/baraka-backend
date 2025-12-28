<?php

namespace App\Filament\Resources\VendorOrderResource\Pages;

use App\Filament\Resources\VendorOrderResource;
use App\Models\VendorOrder;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListVendorOrders extends ListRecords
{
    protected static string $resource = VendorOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - vendor orders are created automatically
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(VendorOrder::count()),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(VendorOrder::where('status', 'pending')->count())
                ->badgeColor('warning'),
            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'processing'))
                ->badge(VendorOrder::where('status', 'processing')->count())
                ->badgeColor('info'),
            'ready' => Tab::make('Ready')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'ready'))
                ->badge(VendorOrder::where('status', 'ready')->count())
                ->badgeColor('success'),
            'collected' => Tab::make('Collected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'collected'))
                ->badge(VendorOrder::where('status', 'collected')->count())
                ->badgeColor('success'),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled'))
                ->badge(VendorOrder::where('status', 'cancelled')->count())
                ->badgeColor('danger'),
        ];
    }
}
