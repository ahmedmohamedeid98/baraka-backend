<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Filament\Resources\BannerResource\RelationManagers;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    
    protected static ?string $navigationGroup = 'Marketing';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Banner Content')
                    ->schema([
                        Forms\Components\TextInput::make('title_ar')
                            ->label('Title (Arabic)')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('title_en')
                            ->label('Title (English)')
                            ->maxLength(255),
                        
                        Forms\Components\FileUpload::make('image')
                            ->label('Banner Image')
                            ->image()
                            ->required()
                            ->disk('public')
                            ->directory('banners')
                            ->visibility('public')
                            ->imageEditor()
                            ->columnSpanFull(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Banner Settings')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Banner Type')
                            ->required()
                            ->options([
                                'slider' => 'Main Slider',
                                'promotion' => 'Promotion',
                                'category' => 'Category Banner',
                            ])
                            ->default('slider'),
                        
                        Forms\Components\Select::make('link_type')
                            ->label('Link Type')
                            ->options([
                                'category' => 'Category',
                                'product' => 'Product',
                                'vendor' => 'Vendor',
                                'url' => 'External URL',
                                'none' => 'No Link',
                            ])
                            ->default('none')
                            ->live(),
                        
                        Forms\Components\Select::make('link_id')
                            ->label('Link To')
                            ->options(function ($get) {
                                return match ($get('link_type')) {
                                    'category' => \App\Models\Category::pluck('name_ar', 'id'),
                                    'product' => \App\Models\Product::pluck('name_ar', 'id'),
                                    'vendor' => \App\Models\Vendor::pluck('name_ar', 'id'),
                                    default => [],
                                };
                            })
                            ->searchable()
                            ->visible(fn ($get) => in_array($get('link_type'), ['category', 'product', 'vendor'])),
                        
                        Forms\Components\TextInput::make('link_url')
                            ->label('External URL')
                            ->url()
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('link_type') === 'url'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),
                
                Forms\Components\Section::make('Scheduling')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Start Date')
                            ->native(false),
                        
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expiry Date')
                            ->native(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->circular(),
                
                Tables\Columns\TextColumn::make('title_ar')
                    ->label('Title (Arabic)')
                    ->searchable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'slider' => 'success',
                        'promotion' => 'warning',
                        'category' => 'info',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('link_type')
                    ->label('Link')
                    ->badge()
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sort')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Start')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'slider' => 'Main Slider',
                        'promotion' => 'Promotion',
                        'category' => 'Category Banner',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
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
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
