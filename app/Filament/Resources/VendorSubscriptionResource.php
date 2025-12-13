<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorSubscriptionResource\Pages;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\VendorSubscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VendorSubscriptionResource extends Resource
{
    protected static ?string $model = VendorSubscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Subscriptions';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Vendor Subscriptions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subscription Details')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'name_ar')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($context) => $context === 'edit'),
                        
                        Forms\Components\Select::make('package_id')
                            ->label('Package')
                            ->relationship('package', 'name_ar')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $package = Package::find($state);
                                    $set('price_paid', $package->price);
                                    $set('pricing_type', $package->pricing_type);
                                    $set('ends_at', now()->addDays($package->duration_days));
                                }
                            }),
                    ])->columns(2),
                
                Forms\Components\Section::make('Period')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Starts At')
                            ->required()
                            ->default(now()),
                        
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Ends At')
                            ->required(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('price_paid')
                            ->label('Price Paid')
                            ->numeric()
                            ->required()
                            ->suffix(fn (Forms\Get $get) => $get('pricing_type') === 'percentage' ? '%' : 'EGP'),
                        
                        Forms\Components\Select::make('pricing_type')
                            ->label('Pricing Type')
                            ->options([
                                'fixed' => 'Fixed Amount',
                                'percentage' => 'Percentage',
                            ])
                            ->required(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('auto_renew')
                            ->label('Auto Renew')
                            ->default(true),
                        
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'expired' => 'Expired',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('active')
                            ->required(),
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
                
                Tables\Columns\TextColumn::make('package.name_ar')
                    ->label('Package')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('price_paid')
                    ->label('Price')
                    ->formatStateUsing(fn ($record) => $record->pricing_type === 'percentage' 
                        ? $record->price_paid . '%' 
                        : number_format($record->price_paid, 2) . ' EGP'),
                
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Started')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ends')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->ends_at->isPast() ? 'danger' : ($record->ends_at->diffInDays(now()) < 7 ? 'warning' : 'success')),
                
                Tables\Columns\TextColumn::make('days_remaining')
                    ->label('Days Left')
                    ->sortable()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : ($state < 7 ? 'warning' : 'success')),
                
                Tables\Columns\IconColumn::make('auto_renew')
                    ->label('Auto Renew')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->status_text)
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('package_id')
                    ->label('Package')
                    ->relationship('package', 'name_ar'),
                Tables\Filters\TernaryFilter::make('auto_renew'),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring in 7 days')
                    ->query(fn (Builder $query) => $query->where('status', 'active')
                        ->whereBetween('ends_at', [now(), now()->addDays(7)])),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('changePackage')
                    ->label('Change Package')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === 'active')
                    ->form([
                        Forms\Components\Select::make('package_id')
                            ->label('New Package')
                            ->options(fn ($record) => Package::active()
                                ->where('id', '!=', $record->package_id)
                                ->pluck('name_ar', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function (VendorSubscription $record, array $data) {
                        $newPackage = Package::find($data['package_id']);
                        $newSubscription = $record->changePackage($newPackage, auth()->id());
                        
                        if (!$newSubscription) {
                            \Filament\Notifications\Notification::make()
                                ->title('Insufficient Balance')
                                ->body('Vendor wallet has insufficient balance for the new package.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Package Changed')
                            ->body("Old subscription expired. New subscription created with {$newPackage->name_ar}.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('renew')
                    ->label('Renew Now')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'active')
                    ->requiresConfirmation()
                    ->action(function (VendorSubscription $record) {
                        $newSubscription = $record->renew();
                        if (!$newSubscription) {
                            \Filament\Notifications\Notification::make()
                                ->title('Insufficient Balance')
                                ->body('Vendor wallet has insufficient balance for renewal.')
                                ->danger()
                                ->send();
                            return;
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Subscription Renewed')
                            ->body("Old subscription expired. New subscription created until {$newSubscription->ends_at->format('Y-m-d')}.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'active')
                    ->requiresConfirmation()
                    ->action(fn (VendorSubscription $record) => $record->cancel()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorSubscriptions::route('/'),
            'create' => Pages\CreateVendorSubscription::route('/create'),
            'edit' => Pages\EditVendorSubscription::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
