<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletChargeRequestResource\Pages;
use App\Models\WalletChargeRequest;
use App\Models\Transaction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class WalletChargeRequestResource extends Resource
{
    protected static ?string $model = WalletChargeRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'طلبات شحن المحفظة';
    
    protected static ?string $modelLabel = 'طلب شحن';
    
    protected static ?string $pluralModelLabel = 'طلبات الشحن';
    
    protected static ?string $navigationGroup = 'Wallet Management';
    
    protected static ?int $navigationSort = 3;

    // Default rejection reasons in Arabic
    public static array $defaultRejectionReasons = [
        'الصورة غير واضحة أو غير مقروءة' => 'الصورة غير واضحة أو غير مقروءة',
        'المبلغ المدفوع لا يطابق المبلغ المطلوب' => 'المبلغ المدفوع لا يطابق المبلغ المطلوب',
        'معلومات الدفع غير صحيحة أو مفقودة' => 'معلومات الدفع غير صحيحة أو مفقودة',
        'لم يتم استلام الدفعة بعد' => 'لم يتم استلام الدفعة بعد',
        'الصورة مكررة أو مستخدمة من قبل' => 'الصورة مكررة أو مستخدمة من قبل',
        'طريقة الدفع غير مدعومة' => 'طريقة الدفع غير مدعومة',
        'البيانات المدخلة غير كاملة' => 'البيانات المدخلة غير كاملة',
        'يرجى التواصل مع الدعم الفني' => 'يرجى التواصل مع الدعم الفني',
        'custom' => 'سبب مخصص (اكتب السبب)',
    ];

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::pending()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الطلب')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->prefix('ر.س')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\Select::make('payment_method')
                            ->label('طريقة الدفع')
                            ->options([
                                'vodafone_cash' => 'فودافون كاش',
                                'instapay' => 'إنستاباي',
                                'bank_transfer' => 'تحويل بنكي',
                                'other' => 'أخرى',
                            ])
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\TextInput::make('payment_reference')
                            ->label('رقم المرجع')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات المستخدم')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(2),
                    ])->columns(2),
                
                Forms\Components\Section::make('صورة الدفع')
                    ->schema([
                        Forms\Components\FileUpload::make('payment_screenshot')
                            ->label('صورة إيصال الدفع')
                            ->image()
                            ->imagePreviewHeight('300')
                            ->downloadable()
                            ->openable()
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                
                Forms\Components\Section::make('معلومات المحفظة')
                    ->schema([
                        Forms\Components\Placeholder::make('user_name')
                            ->label('المستخدم')
                            ->content(fn ($record) => $record->userName ?? '-'),
                        
                        Forms\Components\Placeholder::make('wallet_balance')
                            ->label('رصيد المحفظة الحالي')
                            ->content(fn ($record) => $record->wallet ? number_format($record->wallet->balance, 2) . ' ر.س' : '-'),
                    ])->columns(2),
                
                Forms\Components\Section::make('حالة الطلب')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'pending' => 'قيد الانتظار',
                                'approved' => 'موافق عليه',
                                'rejected' => 'مرفوض',
                            ])
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->visible(fn ($record) => $record?->status === 'rejected')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(3),
                        
                        Forms\Components\Placeholder::make('reviewed_by')
                            ->label('تمت المراجعة بواسطة')
                            ->content(fn ($record) => $record->reviewedBy?->name ?? '-')
                            ->visible(fn ($record) => $record?->status !== 'pending'),
                        
                        Forms\Components\Placeholder::make('reviewed_at')
                            ->label('تاريخ المراجعة')
                            ->content(fn ($record) => $record->reviewed_at?->format('Y-m-d H:i:s') ?? '-')
                            ->visible(fn ($record) => $record?->status !== 'pending'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('رقم الطلب')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('userName')
                    ->label('المستخدم')
                    ->searchable(['users.name', 'vendors.name'])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('paymentMethodText')
                    ->label('طريقة الدفع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'فودافون كاش' => 'danger',
                        'إنستاباي' => 'info',
                        'تحويل بنكي' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\ImageColumn::make('payment_screenshot')
                    ->label('صورة الدفع')
                    ->disk('public')
                    ->height(50)
                    ->width(50),
                
                Tables\Columns\TextColumn::make('statusText')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'قيد الانتظار' => 'warning',
                        'موافق عليه' => 'success',
                        'مرفوض' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('is_resubmission')
                    ->label('إعادة إرسال')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-document-text'),
                
                Tables\Columns\TextColumn::make('reviewedBy.name')
                    ->label('المراجع')
                    ->placeholder('لم تتم المراجعة')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                    ]),
                
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('طريقة الدفع')
                    ->options([
                        'vodafone_cash' => 'فودافون كاش',
                        'instapay' => 'إنستاباي',
                        'bank_transfer' => 'تحويل بنكي',
                        'other' => 'أخرى',
                    ]),
                
                Tables\Filters\Filter::make('is_resubmission')
                    ->label('إعادة إرسال فقط')
                    ->query(fn (Builder $query): Builder => $query->where('is_resubmission', true)),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('من تاريخ'),
                        Forms\Components\DatePicker::make('created_until')->label('إلى تاريخ'),
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
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                
                Tables\Actions\Action::make('approve')
                    ->label('الموافقة')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (WalletChargeRequest $record) => $record->status === WalletChargeRequest::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->modalHeading('الموافقة على طلب الشحن')
                    ->modalDescription(fn (WalletChargeRequest $record) => "هل تريد الموافقة على طلب شحن بمبلغ " . number_format($record->amount, 2) . " ر.س؟")
                    ->action(function (WalletChargeRequest $record) {
                        try {
                            DB::beginTransaction();
                            
                            $admin = Filament::auth()->user();
                            $record->approve($admin->id);
                            
                            DB::commit();
                            
                            Notification::make()
                                ->success()
                                ->title('تمت الموافقة بنجاح')
                                ->body('تم شحن المحفظة بمبلغ ' . number_format($record->amount, 2) . ' ر.س')
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->danger()
                                ->title('فشل في الموافقة')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('reject')
                    ->label('الرفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (WalletChargeRequest $record) => $record->status === WalletChargeRequest::STATUS_PENDING)
                    ->form([
                        Forms\Components\Select::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->options(self::$defaultRejectionReasons)
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state === 'custom') {
                                    $set('custom_reason', '');
                                }
                            }),
                        
                        Forms\Components\Textarea::make('custom_reason')
                            ->label('سبب مخصص')
                            ->placeholder('اكتب سبب الرفض...')
                            ->rows(3)
                            ->visible(fn (Forms\Get $get) => $get('rejection_reason') === 'custom')
                            ->required(fn (Forms\Get $get) => $get('rejection_reason') === 'custom'),
                    ])
                    ->action(function (WalletChargeRequest $record, array $data) {
                        try {
                            $reason = $data['rejection_reason'] === 'custom' 
                                ? $data['custom_reason'] 
                                : $data['rejection_reason'];
                            
                            $admin = Filament::auth()->user();
                            $record->reject($admin->id, $reason);
                            
                            Notification::make()
                                ->success()
                                ->title('تم رفض الطلب')
                                ->body('تم إرسال سبب الرفض للمستخدم')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('فشل في رفض الطلب')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => false), // Disable bulk delete
                ]),
            ]);
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
            'index' => Pages\ListWalletChargeRequests::route('/'),
            'view' => Pages\ViewWalletChargeRequest::route('/{record}'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Users create requests from API, not admin panel
    }
}
