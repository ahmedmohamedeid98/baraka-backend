<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Subscriptions';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Transactions';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\TextInput::make('wallet.owner_type')
                            ->label('Owner Type')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('wallet.owner_name')
                            ->label('Owner')
                            ->disabled(),
                        
                        Forms\Components\Select::make('type')
                            ->options([
                                'charge' => 'Charge',
                                'subscription' => 'Subscription',
                                'gift' => 'Gift',
                                'commission' => 'Commission',
                                'refund' => 'Refund',
                            ])
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('balance_after')
                            ->label('Balance After')
                            ->disabled(),
                        
                        Forms\Components\Textarea::make('description')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('wallet.walletable_type')
                    ->label('Owner Type')
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
                
                Tables\Columns\TextColumn::make('wallet.owner_name')
                    ->label('Owner')
                    ->getStateUsing(function ($record) {
                        return $record->wallet?->walletable?->name_ar ?? $record->wallet?->walletable?->name ?? 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('wallet', function (Builder $query) use ($search) {
                            $query->whereHasMorph('walletable', [Vendor::class, User::class], function (Builder $query, string $type) use ($search) {
                                if ($type === Vendor::class) {
                                    $query->where('name_ar', 'like', "%{$search}%");
                                } elseif ($type === User::class) {
                                    $query->where('name', 'like', "%{$search}%");
                                }
                            });
                        });
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->type_text)
                    ->color(fn (string $state): string => match ($state) {
                        'charge' => 'success',
                        'gift' => 'warning',
                        'subscription' => 'info',
                        'commission' => 'danger',
                        'refund' => 'gray',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('EGP')
                    ->sortable()
                    ->color(fn ($state) => $state < 0 ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Balance After')
                    ->money('EGP')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->description),
                
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->placeholder('System'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('wallet.walletable_type')
                    ->label('Owner Type')
                    ->options([
                        Vendor::class => 'Vendor',
                        User::class => 'User',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'charge' => 'Charge',
                        'subscription' => 'Subscription',
                        'gift' => 'Gift',
                        'commission' => 'Commission',
                        'refund' => 'Refund',
                    ]),
                Tables\Filters\SelectFilter::make('wallet_id')
                    ->label('Wallet')
                    ->relationship('wallet', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->owner_name . ' - ' . $record->owner_type)
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('credits')
                    ->label('Credits Only')
                    ->query(fn (Builder $query) => $query->where('amount', '>', 0)),
                Tables\Filters\Filter::make('debits')
                    ->label('Debits Only')
                    ->query(fn (Builder $query) => $query->where('amount', '<', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
        ];
    }
}
