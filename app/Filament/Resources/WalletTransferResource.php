<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransferResource\Pages;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WalletTransfer;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletTransferResource extends Resource
{
    protected static ?string $model = WalletTransfer::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Wallet Management';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Wallet Transfers';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transfer Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->disabled(),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Sender')
                    ->schema([
                        Forms\Components\TextInput::make('from_user_type')
                            ->label('Sender Type')
                            ->formatStateUsing(fn ($state) => $state === Vendor::class ? 'Vendor' : 'User')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('sender_name')
                            ->label('Sender Name')
                            ->formatStateUsing(fn ($record) => 
                                $record->fromWallet?->walletable?->name_ar ?? 
                                $record->fromWallet?->walletable?->name ?? 
                                'N/A'
                            )
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Receiver')
                    ->schema([
                        Forms\Components\TextInput::make('to_user_type')
                            ->label('Receiver Type')
                            ->formatStateUsing(fn ($state) => $state === Vendor::class ? 'Vendor' : 'User')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('receiver_name')
                            ->label('Receiver Name')
                            ->formatStateUsing(fn ($record) => 
                                $record->toWallet?->walletable?->name_ar ?? 
                                $record->toWallet?->walletable?->name ?? 
                                'N/A'
                            )
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Amount Details')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (EGP)')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('fee')
                            ->label('Fee (EGP)')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('total_deducted')
                            ->label('Total Deducted (EGP)')
                            ->disabled(),
                        
                        Forms\Components\Textarea::make('description')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Security & Review')
                    ->schema([
                        Forms\Components\Toggle::make('is_flagged')
                            ->label('Flagged for Review'),
                        
                        Forms\Components\Textarea::make('flagged_reason')
                            ->label('Flag Reason')
                            ->visible(fn ($record) => $record?->is_flagged),
                        
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP Address')
                            ->disabled(),
                        
                        Forms\Components\Textarea::make('user_agent')
                            ->label('User Agent')
                            ->disabled(),
                    ])->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('sender')
                    ->label('Sender')
                    ->getStateUsing(function ($record) {
                        $name = $record->fromWallet?->walletable?->name_ar ?? 
                                $record->fromWallet?->walletable?->name ?? 'N/A';
                        $type = $record->from_user_type === Vendor::class ? 'Vendor' : 'User';
                        return "{$name} ({$type})";
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('fromWallet.walletable', function (Builder $q) use ($search) {
                            $q->where('name_ar', 'like', "%{$search}%")
                              ->orWhere('name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('receiver')
                    ->label('Receiver')
                    ->getStateUsing(function ($record) {
                        $name = $record->toWallet?->walletable?->name_ar ?? 
                                $record->toWallet?->walletable?->name ?? 'N/A';
                        $type = $record->to_user_type === Vendor::class ? 'Vendor' : 'User';
                        return "{$name} ({$type})";
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('toWallet.walletable', function (Builder $q) use ($search) {
                            $q->where('name_ar', 'like', "%{$search}%")
                              ->orWhere('name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('EGP')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('fee')
                    ->label('Fee')
                    ->money('EGP')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->status_text)
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('is_flagged')
                    ->label('Flagged')
                    ->boolean()
                    ->trueIcon('heroicon-o-flag')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Tables\Filters\SelectFilter::make('from_user_type')
                    ->label('Sender Type')
                    ->options([
                        Vendor::class => 'Vendor',
                        User::class => 'User',
                    ]),
                
                Tables\Filters\SelectFilter::make('to_user_type')
                    ->label('Receiver Type')
                    ->options([
                        Vendor::class => 'Vendor',
                        User::class => 'User',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_flagged')
                    ->label('Flagged for Review')
                    ->placeholder('All')
                    ->trueLabel('Flagged Only')
                    ->falseLabel('Not Flagged'),
                
                Tables\Filters\Filter::make('high_amount')
                    ->label('High Amount (≥ 5000 EGP)')
                    ->query(fn (Builder $query) => $query->where('amount', '>=', 5000)),
                
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->is_flagged && !$record->reviewed_at)
                    ->requiresConfirmation()
                    ->action(function (WalletTransfer $record) {
                        $record->update([
                            'is_flagged' => false,
                            'reviewed_by' => Filament::auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    })
                    ->successNotificationTitle('Transfer approved'),
                
                Tables\Actions\Action::make('flag')
                    ->label('Flag')
                    ->icon('heroicon-o-flag')
                    ->color('warning')
                    ->visible(fn ($record) => !$record->is_flagged)
                    ->form([
                        Forms\Components\Textarea::make('flagged_reason')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->action(function (WalletTransfer $record, array $data) {
                        $record->update([
                            'is_flagged' => true,
                            'flagged_reason' => $data['flagged_reason'],
                            'flagged_at' => now(),
                            'reviewed_by' => Filament::auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    })
                    ->successNotificationTitle('Transfer flagged'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransfers::route('/'),
            'view' => Pages\ViewWalletTransfer::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) WalletTransfer::where('is_flagged', true)
            ->whereNull('reviewed_at')
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return WalletTransfer::where('is_flagged', true)->whereNull('reviewed_at')->count() > 0 
            ? 'danger' 
            : null;
    }
}
