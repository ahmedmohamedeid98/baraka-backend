<?php

namespace App\Filament\Resources\VendorOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Order Items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('product_name')
                    ->label('Product Name')
                    ->disabled()
                    ->required(),
                Forms\Components\TextInput::make('variant_name')
                    ->label('Variant')
                    ->disabled(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->disabled()
                    ->numeric(),
                Forms\Components\TextInput::make('price')
                    ->label('Price')
                    ->disabled()
                    ->numeric()
                    ->prefix('EGP'),
                Forms\Components\TextInput::make('subtotal')
                    ->label('Subtotal')
                    ->disabled()
                    ->numeric()
                    ->prefix('EGP'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                Tables\Columns\ImageColumn::make('product_image')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png')),
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->searchable(),
                Tables\Columns\TextColumn::make('variant_name')
                    ->label('Variant')
                    ->default('N/A')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('EGP'),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('EGP'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No create action - items are created automatically
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for vendor order items
            ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
