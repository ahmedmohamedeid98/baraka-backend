<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorOrderResource\Pages;
use App\Filament\Resources\VendorOrderResource\RelationManagers;
use App\Models\VendorOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VendorOrderResource extends Resource
{
    protected static ?string $model = VendorOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Orders';

    protected static ?string $navigationLabel = 'Vendor Orders';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('Order Number')
                            ->disabled()
                            ->required(),
                        Forms\Components\Select::make('order_id')
                            ->relationship('order', 'order_number')
                            ->label('Main Order')
                            ->disabled()
                            ->required(),
                        Forms\Components\Select::make('vendor_id')
                            ->relationship('vendor', 'name_ar')
                            ->label('Vendor')
                            ->disabled()
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Financial Information')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal (Vendor Payment)')
                            ->disabled()
                            ->numeric()
                            ->prefix('EGP')
                            ->helperText('This amount will be paid to vendor when order is delivered')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Order Status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'ready' => 'Ready',
                                'collected' => 'Collected',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order Number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('order.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('vendor.name_ar')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Vendor Payment')
                    ->money('EGP')
                    ->sortable()
                    ->description('Amount to be paid to vendor'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'ready' => 'success',
                        'collected' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.status')
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
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'ready' => 'Ready',
                        'collected' => 'Collected',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('vendor')
                    ->relationship('vendor', 'name_ar')
                    ->label('Vendor')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorOrders::route('/'),
            'view' => Pages\ViewVendorOrder::route('/{record}'),
            'edit' => Pages\EditVendorOrder::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Vendor orders are created automatically
    }
}
