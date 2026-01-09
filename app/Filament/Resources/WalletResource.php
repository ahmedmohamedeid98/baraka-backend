<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;
    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'Wallet Management';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Wallets';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Wallet Information')
                    ->schema([
                        Forms\Components\Select::make('walletable_type')
                            ->label('Owner Type')
                            ->options([
                                Vendor::class => 'Vendor',
                                User::class => 'User',
                            ])
                            ->required()
                            ->reactive()
                            ->disabled(fn ($context) => $context === 'edit'),
                        
                        Forms\Components\Select::make('walletable_id')
                            ->label('Owner')
                            ->options(function (callable $get) {
                                $type = $get('walletable_type');
                                if ($type === Vendor::class) {
                                    return Vendor::pluck('name_ar', 'id');
                                } elseif ($type === User::class) {
                                    return User::pluck('name', 'id');
                                }
                                return [];
                            })
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
                Tables\Columns\TextColumn::make('walletable_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        Vendor::class => 'Vendor',
                        User::class => 'User',
                        default => 'Unknown',
                    })
                    ->color(fn ($state) => match($state) {
                        Vendor::class => 'success',
                        User::class => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('owner_name')
                    ->label('Owner')
                    ->getStateUsing(function ($record) {
                        return $record->walletable?->name_ar ?? $record->walletable?->name ?? 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHasMorph('walletable', [Vendor::class, User::class], function (Builder $query, string $type) use ($search) {
                            if ($type === Vendor::class) {
                                $query->where('name_ar', 'like', "%{$search}%");
                            } elseif ($type === User::class) {
                                $query->where('name', 'like', "%{$search}%");
                            }
                        });
                    })
                    ->sortable(),
                
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
                Tables\Filters\SelectFilter::make('walletable_type')
                    ->label('Type')
                    ->options([
                        Vendor::class => 'Vendor',
                        User::class => 'User',
                    ]),
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
                    ->action(function (Wallet $record, array $data) {
                        $record->credit(
                            (float) $data['amount'],
                            Transaction::TYPE_CHARGE,
                            $data['description'] ?? 'شحن رصيد من الإدارة',
                            \Filament\Facades\Filament::auth()->id()
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
                    ->action(function (Wallet $record, array $data) {
                        $record->credit(
                            (float) $data['amount'],
                            Transaction::TYPE_GIFT,
                            $data['description'] ?? 'هدية من الإدارة',
                            \Filament\Facades\Filament::auth()->id()
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
                    ->action(function (Wallet $record, array $data) {
                        $record->debit(
                            (float) $data['amount'],
                            Transaction::TYPE_COMMISSION,
                            $data['description'],
                            \Filament\Facades\Filament::auth()->id()
                        );
                    })
                    ->requiresConfirmation()
                    ->successNotificationTitle('Amount deducted successfully'),
                
                Tables\Actions\Action::make('transactions')
                    ->label('View Transactions')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn (Wallet $record) => TransactionResource::getUrl('index', ['tableFilters[wallet_id][value]' => $record->id])),
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
            'index' => Pages\ListWallets::route('/'),
        ];
    }
}
