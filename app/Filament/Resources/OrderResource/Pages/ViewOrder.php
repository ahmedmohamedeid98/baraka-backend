<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\VendorOrder;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public $selectedStatus = null;
    public $selectedVendorOrderId = null;

    protected function getListeners(): array
    {
        return [
            'openStatusModal' => 'handleOrderStatusUpdate',
            'openVendorStatusModal' => 'handleVendorOrderStatusUpdate',
        ];
    }

    public function handleOrderStatusUpdate($status, $orderId)
    {
        $this->selectedStatus = $status;
        $this->mountAction('updateOrderStatus');
    }

    public function handleVendorOrderStatusUpdate($status, $vendorOrderId)
    {
        $this->selectedStatus = $status;
        $this->selectedVendorOrderId = $vendorOrderId;
        $this->mountAction('updateVendorOrderStatus');
    }

    protected function getOrderStatusFlow(): array
    {
        return [
            'pending' => ['label' => 'Pending', 'icon' => 'heroicon-o-clock', 'color' => 'warning', 'next' => ['confirmed', 'cancelled']],
            'confirmed' => ['label' => 'Confirmed', 'icon' => 'heroicon-o-check-circle', 'color' => 'info', 'next' => ['preparing', 'cancelled']],
            'preparing' => ['label' => 'Preparing', 'icon' => 'heroicon-o-cog-6-tooth', 'color' => 'primary', 'next' => ['on_the_way', 'cancelled']],
            'on_the_way' => ['label' => 'On The Way', 'icon' => 'heroicon-o-truck', 'color' => 'info', 'next' => ['delivered', 'cancelled']],
            'delivered' => ['label' => 'Delivered', 'icon' => 'heroicon-o-check-badge', 'color' => 'success', 'next' => []],
            'cancelled' => ['label' => 'Cancelled', 'icon' => 'heroicon-o-x-circle', 'color' => 'danger', 'next' => []],
        ];
    }

    protected function getVendorOrderStatusFlow(): array
    {
        return [
            'pending' => ['label' => 'Pending', 'icon' => 'heroicon-o-clock', 'color' => 'warning', 'next' => ['processing', 'cancelled']],
            'processing' => ['label' => 'Processing', 'icon' => 'heroicon-o-cog-6-tooth', 'color' => 'info', 'next' => ['ready', 'cancelled']],
            'ready' => ['label' => 'Ready', 'icon' => 'heroicon-o-check-circle', 'color' => 'success', 'next' => ['collected', 'cancelled']],
            'collected' => ['label' => 'Collected', 'icon' => 'heroicon-o-check-badge', 'color' => 'success', 'next' => []],
            'cancelled' => ['label' => 'Cancelled', 'icon' => 'heroicon-o-x-circle', 'color' => 'danger', 'next' => []],
        ];
    }

    protected function canTransitionTo(string $currentStatus, string $targetStatus, array $statusFlow): bool
    {
        if ($currentStatus === $targetStatus) {
            return false;
        }
        
        return in_array($targetStatus, $statusFlow[$currentStatus]['next'] ?? []);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('updateOrderStatus')
                ->label('Update Order Status')
                ->form(function () {
                    $statusFlow = $this->getOrderStatusFlow();
                    $targetStatus = $this->selectedStatus;
                    
                    return [
                        Forms\Components\Placeholder::make('status_info')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="flex items-center gap-3 p-4 bg-primary-50 dark:bg-primary-950 rounded-lg border border-primary-200 dark:border-primary-800">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-primary-900 dark:text-primary-100">
                                            Updating to: ' . ($statusFlow[$targetStatus]['label'] ?? 'Unknown') . '
                                        </h3>
                                        <p class="text-sm text-primary-700 dark:text-primary-300">
                                            Please provide a reason for this status change to maintain accountability.
                                        </p>
                                    </div>
                                </div>
                            ')),
                        
                        Forms\Components\Hidden::make('status')
                            ->default($targetStatus),
                        
                        Forms\Components\Textarea::make('note')
                            ->label('Reason for Status Change')
                            ->required()
                            ->rows(3)
                            ->placeholder('e.g., Customer confirmed delivery, Payment verified, etc.')
                            ->helperText('This note will be recorded in the order history for audit purposes.'),
                    ];
                })
                ->modalHeading('Confirm Status Change')
                ->modalSubmitActionLabel('Update Status')
                ->modalWidth('lg')
                ->action(function (array $data): void {
                    $status = $data['status'];
                    $note = $data['note'];
                    
                    // Validate the transition
                    if (!$this->canTransitionTo($this->record->status, $status, $this->getOrderStatusFlow())) {
                        Notification::make()
                            ->title('Invalid Status Transition')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $this->record->updateStatus($status, $note);
                    
                    Notification::make()
                        ->title('Order Status Updated')
                        ->body("Order status changed to {$this->getOrderStatusFlow()[$status]['label']}")
                        ->success()
                        ->send();
                    
                    // Refresh the page to show updated timeline
                    $this->redirect(static::getUrl(['record' => $this->record->id]));
                })
                ->visible(fn () => $this->selectedStatus !== null),
            
            Actions\Action::make('updateVendorOrderStatus')
                ->label('Update Vendor Order Status')
                ->form(function () {
                    $statusFlow = $this->getVendorOrderStatusFlow();
                    $targetStatus = $this->selectedStatus;
                    $vendorOrderId = $this->selectedVendorOrderId;
                    
                    return [
                        Forms\Components\Placeholder::make('status_info')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="flex items-center gap-3 p-4 bg-primary-50 dark:bg-primary-950 rounded-lg border border-primary-200 dark:border-primary-800">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-primary-900 dark:text-primary-100">
                                            Updating Vendor Order to: ' . ($statusFlow[$targetStatus]['label'] ?? 'Unknown') . '
                                        </h3>
                                        <p class="text-sm text-primary-700 dark:text-primary-300">
                                            Please provide a reason for this vendor order status change.
                                        </p>
                                    </div>
                                </div>
                            ')),
                        
                        Forms\Components\Hidden::make('status')
                            ->default($targetStatus),
                        
                        Forms\Components\Hidden::make('vendorOrderId')
                            ->default($vendorOrderId),
                        
                        Forms\Components\Textarea::make('note')
                            ->label('Reason for Status Change')
                            ->required()
                            ->rows(3)
                            ->placeholder('e.g., Vendor confirmed items are ready, Items collected by delivery, etc.')
                            ->helperText('This note will be recorded in the vendor order history.'),
                    ];
                })
                ->modalHeading('Confirm Vendor Order Status Change')
                ->modalSubmitActionLabel('Update Status')
                ->modalWidth('lg')
                ->action(function (array $data): void {
                    $status = $data['status'];
                    $note = $data['note'];
                    $vendorOrderId = $data['vendorOrderId'];
                    
                    $vendorOrder = VendorOrder::findOrFail($vendorOrderId);
                    
                    // Validate the transition
                    if (!$this->canTransitionTo($vendorOrder->status, $status, $this->getVendorOrderStatusFlow())) {
                        Notification::make()
                            ->title('Invalid Status Transition')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $vendorOrder->updateStatus($status, $note);
                    
                    Notification::make()
                        ->title('Vendor Order Status Updated')
                        ->body("Vendor order #{$vendorOrder->order_number} status changed to {$this->getVendorOrderStatusFlow()[$status]['label']}")
                        ->success()
                        ->send();
                    
                    // Refresh the page to show updated timeline
                    $this->redirect(static::getUrl(['record' => $this->record->id]));
                })
                ->visible(fn () => $this->selectedVendorOrderId !== null),
            
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Order Information
                Infolists\Components\Section::make('Order Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('order_number')
                            ->label('Order Number')
                            ->badge()
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'preparing' => 'primary',
                                'on_the_way' => 'info',
                                'delivered' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Order Date')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Customer Notes')
                            ->default('No notes'),
                    ])->columns(2),

                // Order Status Timeline
                Infolists\Components\Section::make('Order Status Management')
                    ->description('Click on any available status to update the order. Only valid next steps are enabled.')
                    ->schema([
                        Infolists\Components\ViewEntry::make('status_timeline')
                            ->label('')
                            ->view('filament.components.order-status-timeline', [
                                'currentStatus' => fn ($record) => $record->status,
                                'statusFlow' => $this->getOrderStatusFlow(),
                                'orderId' => fn ($record) => $record->id,
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // Customer Information
                Infolists\Components\Section::make('Customer Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('user.phone')
                            ->label('Phone'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email')
                            ->default('N/A'),
                    ])->columns(3),

                // Delivery Address
                Infolists\Components\Section::make('Delivery Address')
                    ->schema([
                        Infolists\Components\TextEntry::make('delivery_address')
                            ->label('Full Address')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('address.area.name_en')
                            ->label('Area'),
                        Infolists\Components\TextEntry::make('address.building')
                            ->label('Building'),
                        Infolists\Components\TextEntry::make('address.floor')
                            ->label('Floor'),
                        Infolists\Components\TextEntry::make('address.apartment')
                            ->label('Apartment'),
                    ])->columns(4),

                // Payment Information
                Infolists\Components\Section::make('Payment Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Payment Method')
                            ->badge(),
                        Infolists\Components\TextEntry::make('payment_status')
                            ->label('Payment Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'pending_verification' => 'info',
                                'completed' => 'success',
                                'failed' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\ImageEntry::make('payment_screenshot')
                            ->label('Payment Screenshot')
                            ->disk('r2')
                            ->visible(fn ($state) => $state !== null)
                            ->columnSpanFull(),
                        
                        Infolists\Components\Actions::make([
                            Infolists\Components\Actions\Action::make('confirmPayment')
                                ->label('Confirm Payment')
                                ->icon('heroicon-o-check-circle')
                                ->color('success')
                                ->requiresConfirmation()
                                ->modalHeading('Confirm Payment')
                                ->modalDescription('Are you sure you want to confirm this payment? This will mark the payment as completed.')
                                ->modalSubmitActionLabel('Confirm Payment')
                                ->action(function () {
                                    $this->record->update([
                                        'payment_status' => 'completed',
                                    ]);
                                    
                                    Notification::make()
                                        ->title('Payment Confirmed')
                                        ->body('Payment has been marked as completed.')
                                        ->success()
                                        ->send();
                                    
                                    $this->redirect(static::getUrl(['record' => $this->record->id]));
                                })
                                ->visible(fn () => $this->record->payment_screenshot && $this->record->payment_status === 'pending_verification'),
                            
                            Infolists\Components\Actions\Action::make('rejectPayment')
                                ->label('Reject Payment')
                                ->icon('heroicon-o-x-circle')
                                ->color('danger')
                                ->form([
                                    Forms\Components\Textarea::make('rejection_reason')
                                        ->label('Rejection Reason')
                                        ->required()
                                        ->rows(3)
                                        ->placeholder('e.g., Payment screenshot is unclear, Amount does not match, etc.')
                                        ->helperText('This reason will be sent to the customer.'),
                                ])
                                ->modalHeading('Reject Payment')
                                ->modalSubmitActionLabel('Reject Payment')
                                ->modalWidth('lg')
                                ->action(function (array $data) {
                                    $this->record->update([
                                        'payment_status' => 'failed',
                                        'payment_rejection_reason' => $data['rejection_reason'],
                                    ]);
                                    
                                    Notification::make()
                                        ->title('Payment Rejected')
                                        ->body('Payment has been rejected. The customer will be notified.')
                                        ->warning()
                                        ->send();
                                    
                                    $this->redirect(static::getUrl(['record' => $this->record->id]));
                                })
                                ->visible(fn () => $this->record->payment_screenshot && $this->record->payment_status === 'pending_verification'),
                        ])
                        ->visible(fn () => $this->record->payment_screenshot && $this->record->payment_status === 'pending_verification')
                        ->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('payment_rejection_reason')
                            ->label('Rejection Reason')
                            ->color('danger')
                            ->visible(fn ($state) => $state !== null)
                            ->columnSpanFull(),
                    ])->columns(2),

                // Order Summary
                Infolists\Components\Section::make('Order Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->money('EGP'),
                        Infolists\Components\TextEntry::make('delivery_fee')
                            ->label('Delivery Fee')
                            ->money('EGP'),
                        Infolists\Components\TextEntry::make('discount')
                            ->label('Discount')
                            ->money('EGP'),
                        Infolists\Components\TextEntry::make('total')
                            ->label('Total')
                            ->money('EGP')
                            ->weight('bold')
                            ->size('lg'),
                        Infolists\Components\TextEntry::make('coupon_code')
                            ->label('Coupon Code')
                            ->badge()
                            ->visible(fn ($state) => $state !== null),
                    ])->columns(5),

                // Order Items
                Infolists\Components\Section::make('Order Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('product_name')
                                    ->label('Product'),
                                Infolists\Components\TextEntry::make('variant_name')
                                    ->label('Variant')
                                    ->default('N/A'),
                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Qty'),
                                Infolists\Components\TextEntry::make('price')
                                    ->label('Price')
                                    ->money('EGP'),
                                Infolists\Components\TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('EGP'),
                            ])
                            ->columns(5),
                    ]),

                // Vendor Orders Management
                Infolists\Components\Section::make('Vendor Orders Management')
                    ->description('Track and manage vendor orders. Update status for each vendor order as needed.')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('vendorOrders')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('order_number')
                                            ->label('Vendor Order #')
                                            ->badge()
                                            ->color('primary'),
                                        Infolists\Components\TextEntry::make('vendor.name')
                                            ->label('Vendor Name')
                                            ->weight('bold')
                                            ->icon('heroicon-o-building-storefront'),
                                        Infolists\Components\TextEntry::make('vendor.phone')
                                            ->label('Vendor Phone')
                                            ->icon('heroicon-o-phone'),
                                        Infolists\Components\TextEntry::make('status')
                                            ->label('Current Status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'ready' => 'success',
                                                'collected' => 'success',
                                                'cancelled' => 'danger',
                                                default => 'gray',
                                            })
                                            ->size('lg'),
                                        Infolists\Components\TextEntry::make('subtotal')
                                            ->label('Vendor Payment')
                                            ->money('EGP')
                                            ->weight('bold')
                                            ->color('success'),
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Created At')
                                            ->dateTime()
                                            ->icon('heroicon-o-clock'),
                                    ]),
                                
                                // Vendor Order Status Timeline
                                Infolists\Components\Section::make('Vendor Order Status')
                                    ->description('Click on any available status to update. Only valid transitions are enabled.')
                                    ->schema([
                                        Infolists\Components\ViewEntry::make('vendor_status_timeline')
                                            ->label('')
                                            ->view('filament.components.vendor-order-status-timeline', [
                                                'currentStatus' => fn ($record) => $record->status,
                                                'statusFlow' => $this->getVendorOrderStatusFlow(),
                                                'vendorOrderId' => fn ($record) => $record->id,
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false),
                                
                                Infolists\Components\Section::make('Vendor Order Items')
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('items')
                                            ->label('')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('product_name')
                                                    ->label('Product'),
                                                Infolists\Components\TextEntry::make('variant_name')
                                                    ->label('Variant')
                                                    ->default('Standard'),
                                                Infolists\Components\TextEntry::make('quantity')
                                                    ->label('Qty')
                                                    ->badge(),
                                                Infolists\Components\TextEntry::make('price')
                                                    ->label('Unit Price')
                                                    ->money('EGP'),
                                                Infolists\Components\TextEntry::make('subtotal')
                                                    ->label('Subtotal')
                                                    ->money('EGP')
                                                    ->weight('bold'),
                                            ])
                                            ->columns(5),
                                    ])
                                    ->collapsible(),
                                
                                Infolists\Components\Actions::make([
                                    Infolists\Components\Actions\Action::make('viewVendor')
                                        ->label('View Vendor Details')
                                        ->icon('heroicon-o-eye')
                                        ->color('gray')
                                        ->url(fn ($record) => route('filament.admin.resources.vendors.edit', ['record' => $record->vendor_id]))
                                        ->openUrlInNewTab(),
                                ])
                                ->alignEnd(),
                            ])
                            ->contained(true)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => $this->record->vendorOrders()->count() > 0)
                    ->collapsible(),

                // Status History
                Infolists\Components\Section::make('Status History')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('statusHistories')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('note')
                                    ->label('Note')
                                    ->default('N/A'),
                                Infolists\Components\TextEntry::make('created_by_name')
                                    ->label('Changed By')
                                    ->default('System'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Date')
                                    ->dateTime(),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}
