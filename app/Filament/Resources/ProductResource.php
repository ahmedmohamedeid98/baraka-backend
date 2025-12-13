<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->relationship('vendor', 'name_ar')
                            ->required()
                            ->searchable(),
                        
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name_ar')
                            ->required()
                            ->searchable(),
                        
                        Forms\Components\TextInput::make('name_ar')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                        
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true),
                        
                        Forms\Components\TextInput::make('unit')
                            ->label('Unit')
                            ->maxLength(255)
                            ->placeholder('e.g., كجم, قطعة, حزمة'),
                        
                        Forms\Components\Textarea::make('description_ar')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Pricing & Stock')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Price')
                            ->required()
                            ->numeric()
                            ->prefix('EGP')
                            ->step(0.01),
                        
                        Forms\Components\TextInput::make('compare_price')
                            ->label('Compare Price')
                            ->numeric()
                            ->prefix('EGP')
                            ->step(0.01),
                        
                        Forms\Components\TextInput::make('stock')
                            ->label('Stock')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ])->columns(3),
                
                Forms\Components\Section::make('Images')
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->label('Product Images')
                            ->image()
                            ->multiple()
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public')
                            ->maxFiles(10),
                    ]),
                
                Forms\Components\Section::make('Product Variations')
                    ->schema([
                        Forms\Components\Repeater::make('variations')
                            ->relationship('variations')
                            ->schema([
                                Forms\Components\TextInput::make('name_ar')
                                    ->label('Variation Name')
                                    ->required()
                                    ->placeholder('e.g., أسود - 128GB')
                                    ->columnSpan(2),
                                
                                Forms\Components\KeyValue::make('attributes')
                                    ->label('Attributes')
                                    ->keyLabel('Attribute Name')
                                    ->valueLabel('Value')
                                    ->addActionLabel('Add Attribute')
                                    ->columnSpan(2),
                                
                                Forms\Components\TextInput::make('price')
                                    ->label('Price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('EGP')
                                    ->step(0.01),
                                
                                Forms\Components\TextInput::make('stock')
                                    ->label('Stock')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU')
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->columns(4)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name_ar'] ?? null)
                            ->defaultItems(0)
                            ->addActionLabel('Add Variation'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured')
                            ->default(false),
                        
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('vendor.name_ar')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('category.name_ar')
                    ->label('Category')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('EGP')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->relationship('vendor', 'name_ar')
                    ->label('Vendor'),
                
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name_ar')
                    ->label('Category'),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
