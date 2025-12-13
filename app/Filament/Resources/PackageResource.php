<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Subscriptions';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Packages';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Package Information')
                    ->schema([
                        Forms\Components\TextInput::make('name_ar')
                            ->label('Name (Arabic)')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description_ar')
                            ->label('Description (Arabic)')
                            ->rows(3),
                    ])->columns(1),
                
                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\Select::make('pricing_type')
                            ->label('Pricing Type')
                            ->options([
                                'fixed' => 'Fixed Amount (Monthly Fee)',
                                'percentage' => 'Percentage (From Each Order)',
                            ])
                            ->default('fixed')
                            ->required()
                            ->live(),
                        
                        Forms\Components\TextInput::make('price')
                            ->label(fn (Forms\Get $get) => $get('pricing_type') === 'percentage' ? 'Default Percentage (%)' : 'Price (EGP)')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->suffix(fn (Forms\Get $get) => $get('pricing_type') === 'percentage' ? '%' : 'EGP')
                            ->helperText(fn (Forms\Get $get) => $get('pricing_type') === 'percentage' ? 'Used when no tiers match or tiers are empty' : null),
                        
                        Forms\Components\Select::make('duration_days')
                            ->label('Duration')
                            ->options([
                                30 => 'Monthly (30 days)',
                                90 => 'Quarterly (90 days)',
                                180 => 'Semi-Annual (180 days)',
                                365 => 'Annual (365 days)',
                            ])
                            ->default(30)
                            ->required(),
                    ])->columns(3),
                
                Forms\Components\Section::make('Percentage Tiers')
                    ->schema([
                        Forms\Components\Repeater::make('percentage_tiers')
                            ->label('Commission Tiers (Based on Order Amount)')
                            ->schema([
                                Forms\Components\TextInput::make('min')
                                    ->label('Min Amount (EGP)')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0),
                                
                                Forms\Components\TextInput::make('max')
                                    ->label('Max Amount (EGP)')
                                    ->numeric()
                                    ->nullable()
                                    ->helperText('Leave empty for unlimited'),
                                
                                Forms\Components\TextInput::make('percentage')
                                    ->label('Commission (%)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Tier')
                            ->reorderable(false)
                            ->helperText('Example: Orders < 1000 EGP → 5%, 1000-3000 EGP → 3%, > 3000 EGP → 1%'),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('pricing_type') === 'percentage'),
                
                Forms\Components\Section::make('Limits')
                    ->schema([
                        Forms\Components\TextInput::make('max_products')
                            ->label('Max Products')
                            ->numeric()
                            ->nullable()
                            ->helperText('Leave empty for unlimited'),
                        
                        Forms\Components\TextInput::make('max_orders_per_month')
                            ->label('Max Orders Per Month')
                            ->numeric()
                            ->nullable()
                            ->helperText('Leave empty for unlimited'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Features')
                    ->schema([
                        Forms\Components\Repeater::make('features')
                            ->label('Features List')
                            ->simple(
                                Forms\Components\TextInput::make('feature')
                                    ->required()
                            )
                            ->defaultItems(0)
                            ->addActionLabel('Add Feature'),
                    ]),
                
                Forms\Components\Section::make('Settings')
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
                
                Tables\Columns\TextColumn::make('pricing_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'success',
                        'percentage' => 'warning',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('price')
                    ->label('Price/Rate')
                    ->formatStateUsing(fn ($record) => $record->pricing_type === 'percentage' 
                        ? ($record->tiers_text ?: $record->price . '%')
                        : number_format($record->price, 2) . ' EGP')
                    ->sortable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Duration')
                    ->formatStateUsing(fn ($record) => $record->duration_text),
                
                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('Subscribers')
                    ->counts('subscriptions')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pricing_type')
                    ->options([
                        'fixed' => 'Fixed Amount',
                        'percentage' => 'Percentage',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
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
