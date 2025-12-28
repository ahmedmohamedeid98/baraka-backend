<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Method Information')
                    ->schema([
                        Forms\Components\TextInput::make('name_ar')
                            ->label('Name (Arabic)')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('name_en')
                            ->label('Name (English)')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique identifier for this payment method (e.g., cash, card, wallet)'),
                        
                        Forms\Components\Textarea::make('description_ar')
                            ->label('Description (Arabic)')
                            ->rows(3)
                            ->maxLength(65535),
                        
                        Forms\Components\Textarea::make('description_en')
                            ->label('Description (English)')
                            ->rows(3)
                            ->maxLength(65535),
                        
                        Forms\Components\TextInput::make('icon')
                            ->label('Icon')
                            ->maxLength(255)
                            ->helperText('Icon name or path'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])->columns(2),

                Forms\Components\Section::make('Discount Settings')
                    ->description('Optional discount when user selects this payment method')
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->label('Discount Type')
                            ->options([
                                'percentage' => 'Percentage (%)',
                                'fixed' => 'Fixed Amount',
                            ])
                            ->default('percentage')
                            ->required(),
                        
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Discount Amount')
                            ->numeric()
                            ->default(0)
                            ->step(0.01)
                            ->minValue(0)
                            ->helperText('Enter percentage (e.g., 5 for 5%) or fixed amount')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Transaction Settings')
                    ->description('Configure transaction screenshot requirements')
                    ->schema([
                        Forms\Components\Toggle::make('required_transaction_screenshot')
                            ->label('Require Transaction Screenshot')
                            ->helperText('If enabled, users must upload a screenshot of the transaction'),
                    ]),

                Forms\Components\Section::make('Payment Instructions')
                    ->description('Step-by-step instructions to guide users on how to pay')
                    ->schema([
                        Forms\Components\Repeater::make('instructions')
                            ->relationship('instructions')
                            ->schema([
                                Forms\Components\Textarea::make('instruction_en')
                                    ->label('Instruction (English)')
                                    ->required()
                                    ->rows(2),
                                
                                Forms\Components\Textarea::make('instruction_ar')
                                    ->label('Instruction (Arabic)')
                                    ->required()
                                    ->rows(2),
                                
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('font_size')
                                            ->label('Font Size (px)')
                                            ->numeric()
                                            ->default(14)
                                            ->minValue(10)
                                            ->maxValue(30)
                                            ->required(),
                                        
                                        Forms\Components\Toggle::make('is_bold')
                                            ->label('Bold Text')
                                            ->default(false),
                                        
                                        Forms\Components\ColorPicker::make('color')
                                            ->label('Text Color')
                                            ->default('#000000')
                                            ->required(),
                                    ]),
                                
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Toggle::make('is_copyable')
                                            ->label('Is Copyable')
                                            ->helperText('Enable to show a copy button for this instruction')
                                            ->default(false),
                                        
                                        Forms\Components\Toggle::make('is_link')
                                            ->label('Is Link')
                                            ->helperText('Enable if this instruction is a clickable link/URL')
                                            ->default(false),
                                    ]),
                                
                                Forms\Components\TextInput::make('placeholder')
                                    ->label('Placeholder for Dynamic Values')
                                    ->helperText('Use {amount} for order amount, {phone} for phone number, etc. Mobile app will replace these with actual values')
                                    ->placeholder('e.g., *9*0102345678*{amount}#')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Lower numbers appear first'),
                            ])
                            ->orderColumn('sort_order')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['instruction_en']) 
                                    ? \Illuminate\Support\Str::limit($state['instruction_en'], 50) 
                                    : null
                            )
                            ->addActionLabel('Add Instruction')
                            ->columns(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_en')
                    ->label('Name (EN)')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('Name (AR)')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('discount_type')
                    ->label('Discount Type')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'success',
                        'fixed' => 'warning',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Discount')
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        if ($record->discount_amount == 0) {
                            return 'None';
                        }
                        return $record->discount_type === 'percentage' 
                            ? $record->discount_amount . '%' 
                            : number_format($record->discount_amount, 2) . ' EGP';
                    })
                    ->color(fn ($record) => $record->discount_amount > 0 ? 'success' : 'gray'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All methods')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
