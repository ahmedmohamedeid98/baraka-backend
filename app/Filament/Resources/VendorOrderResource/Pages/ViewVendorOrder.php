<?php

namespace App\Filament\Resources\VendorOrderResource\Pages;

use App\Filament\Resources\VendorOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewVendorOrder extends ViewRecord
{
    protected static string $resource = VendorOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Order Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('order_number')
                            ->label('Order Number')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('order.order_number')
                            ->label('Main Order')
                            ->url(fn ($record) => route('filament.admin.resources.orders.view', $record->order_id))
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'processing' => 'info',
                                'ready' => 'success',
                                'collected' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                    ])->columns(2),

                Infolists\Components\Section::make('Vendor Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('vendor.name_ar')
                            ->label('Vendor Name'),
                        Infolists\Components\TextEntry::make('vendor.phone')
                            ->label('Phone'),
                        Infolists\Components\TextEntry::make('vendor.email')
                            ->label('Email'),
                    ])->columns(3),

                Infolists\Components\Section::make('Financial Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->label('Vendor Payment Amount')
                            ->money('EGP')
                            ->helperText('This amount will be paid to the vendor when main order is delivered'),
                    ]),

                Infolists\Components\Section::make('Payment Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('order.status')
                            ->label('Main Order Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'processing' => 'info',
                                'shipping' => 'primary',
                                'delivered' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('payment_status')
                            ->label('Vendor Payment Status')
                            ->default(function ($record) {
                                return $record->order->status === 'delivered' ? 'Paid' : 'Pending';
                            })
                            ->badge()
                            ->color(function ($record) {
                                return $record->order->status === 'delivered' ? 'success' : 'warning';
                            }),
                    ])->columns(2),
            ]);
    }
}
