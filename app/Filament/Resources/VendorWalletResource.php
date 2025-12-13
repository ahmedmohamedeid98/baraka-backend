<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorWalletResource\Pages;
use App\Models\Transaction;
use App\Models\Vendor;
use App\Models\VendorWallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorWalletResource extends Resource
{
    protected static ?string $model = VendorWallet::class;
    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'Subscriptions';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Vendor Wallets';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Wallet Information')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'name_ar')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($context) => $context === 'edit'),
                        
                        Forms\Components\TextInput::make('balance')
                            ->label('Current Balance (EGP)')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vendor.name_ar')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('vendor.phone')
                    ->label('Phone')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->money('EGP')
                    ->sortable()
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray')),
                
                Tables\Columns\TextColumn::make('transactions_count')
                    ->label('Transactions')
                    ->counts('transactions')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('positive_balance')
                    ->label('Positive Balance')
                    ->query(fn (Builder $query) => $query->where('balance', '>', 0)),
                Tables\Filters\Filter::make('zero_balance')
                    ->label('Zero Balance')
                    ->query(fn (Builder $query) => $query->where('balance', '=', 0)),
                Tables\Filters\Filter::make('negative_balance')
                    ->label('Negative Balance')
                    ->query(fn (Builder $query) => $query->where('balance', '<', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('charge')
                    ->label('Charge')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (EGP)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Optional note...'),
                    ])
                    ->action(function (VendorWallet $record, array $data) {
                        $record->credit(
                            (float) $data['amount'],
                            Transaction::TYPE_CHARGE,
                            $data['description'] ?? 'شحن رصيد من الإدارة',
                            auth()->id()
                        );
                    })
                    ->successNotificationTitle('Wallet charged successfully'),
                
                Tables\Actions\Action::make('gift')
                    ->label('Gift')
                    ->icon('heroicon-o-gift')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (EGP)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Reason for gift...'),
                    ])
                    ->action(function (VendorWallet $record, array $data) {
                        $record->credit(
                            (float) $data['amount'],
                            Transaction::TYPE_GIFT,
                            $data['description'] ?? 'هدية من الإدارة',
                            auth()->id()
                        );
                    })
                    ->successNotificationTitle('Gift added successfully'),
                
                Tables\Actions\Action::make('deduct')
                    ->label('Deduct')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (EGP)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->placeholder('Reason for deduction...'),
                    ])
                    ->action(function (VendorWallet $record, array $data) {
                        $record->debit(
                            (float) $data['amount'],
                            Transaction::TYPE_COMMISSION,
                            $data['description'],
                            auth()->id()
                        );
                    })
                    ->requiresConfirmation()
                    ->successNotificationTitle('Amount deducted successfully'),
                
                Tables\Actions\Action::make('transactions')
                    ->label('View Transactions')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn (VendorWallet $record) => TransactionResource::getUrl('index', ['tableFilters[wallet_id][value]' => $record->id])),
            ])
            ->bulkActions([])
            ->defaultSort('balance', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorWallets::route('/'),
        ];
    }
}
