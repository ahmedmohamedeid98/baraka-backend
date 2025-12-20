<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AreaResource\Pages;
use App\Filament\Resources\AreaResource\RelationManagers;
use App\Models\Area;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AreaResource extends Resource
{
    protected static ?string $model = Area::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    
    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Area Information')
                    ->schema([
                        Forms\Components\TextInput::make('name_ar')
                            ->label('Name (Arabic)')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('name_en')
                            ->label('Name (English)')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('delivery_fee')
                            ->label('Delivery Fee')
                            ->required()
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0)
                            ->step(0.01),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Section::make('Delivery Coverage Zones')
                    ->description('Define circular zones for this area. Users within any of these zones will automatically be assigned to this area.')
                    ->schema([
                        Forms\Components\Repeater::make('center_points')
                            ->label('Coverage Zones')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('latitude')
                                            ->label('Center Latitude')
                                            ->required()
                                            ->numeric()
                                            ->step(0.00000001)
                                            ->placeholder('e.g., 30.0444')
                                            ->helperText('Center point latitude'),
                                        
                                        Forms\Components\TextInput::make('longitude')
                                            ->label('Center Longitude')
                                            ->required()
                                            ->numeric()
                                            ->step(0.00000001)
                                            ->placeholder('e.g., 31.2357')
                                            ->helperText('Center point longitude'),
                                        
                                        Forms\Components\TextInput::make('radius_km')
                                            ->label('Radius (km)')
                                            ->required()
                                            ->numeric()
                                            ->step(0.1)
                                            ->minValue(0.1)
                                            ->suffix('km')
                                            ->placeholder('e.g., 4')
                                            ->helperText('Coverage radius in kilometers'),
                                    ]),
                            ])
                            ->addActionLabel('Add Coverage Zone')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['latitude'], $state['longitude'], $state['radius_km'])
                                    ? "Zone: ({$state['latitude']}, {$state['longitude']}) - {$state['radius_km']}km radius"
                                    : null
                            )
                            ->defaultItems(0)
                            ->columns(1),

                        Forms\Components\Placeholder::make('map_helper')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div class="text-sm text-gray-600 dark:text-gray-400 mt-4">' .
                                '<p><strong>How to set coverage zones:</strong></p>' .
                                '<ol class="list-decimal ml-4 mt-2 space-y-1">' .
                                '<li>Open <a href="https://www.google.com/maps" target="_blank" class="text-primary-600 hover:underline">Google Maps</a></li>' .
                                '<li>Find the center point of your coverage area</li>' .
                                '<li>Right-click on the center point and copy coordinates (latitude, longitude)</li>' .
                                '<li>Paste coordinates in the zone fields and set radius in kilometers</li>' .
                                '<li>Add multiple zones if your area has multiple coverage points</li>' .
                                '<li>Example: Downtown center (30.0444, 31.2357) with 4km radius covers the entire downtown area</li>' .
                                '</ol>' .
                                '<p class="mt-3"><strong>Note:</strong> Users whose location falls within ANY of these circular zones will be assigned to this area.</p>' .
                                '</div>'
                            )),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('Name (Arabic)')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('name_en')
                    ->label('Name (English)')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('delivery_fee')
                    ->label('Delivery Fee')
                    ->money('EGP')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('addresses_count')
                    ->label('Addresses')
                    ->counts('addresses')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sort')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAreas::route('/'),
            'create' => Pages\CreateArea::route('/create'),
            'edit' => Pages\EditArea::route('/{record}/edit'),
        ];
    }
}
