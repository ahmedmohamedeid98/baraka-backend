<?php

namespace App\Filament\Resources\WalletChargeRequestResource\Pages;

use App\Filament\Resources\WalletChargeRequestResource;
use App\Models\WalletChargeRequest;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewWalletChargeRequest extends ViewRecord
{
    protected static string $resource = WalletChargeRequestResource::class;
    
    protected static string $view = 'filament.resources.wallet-charge-request-resource.pages.view-wallet-charge-request';
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('الموافقة')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === WalletChargeRequest::STATUS_PENDING)
                ->requiresConfirmation()
                ->modalHeading('الموافقة على طلب الشحن')
                ->modalDescription(fn () => "هل تريد الموافقة على طلب شحن بمبلغ " . number_format($this->record->amount, 2) . " ج.م؟")
                ->action(function () {
                    try {
                        DB::beginTransaction();
                        
                        $admin = Filament::auth()->user();
                        $this->record->approve($admin->id);
                        
                        DB::commit();
                        
                        Notification::make()
                            ->success()
                            ->title('تمت الموافقة بنجاح')
                            ->body('تم شحن المحفظة بمبلغ ' . number_format($this->record->amount, 2) . ' ج.م')
                            ->send();
                        
                        return redirect()->to(WalletChargeRequestResource::getUrl('index'));
                    } catch (\Exception $e) {
                        DB::rollBack();
                        
                        Notification::make()
                            ->danger()
                            ->title('فشل في الموافقة')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            
            Actions\Action::make('reject')
                ->label('الرفض')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === WalletChargeRequest::STATUS_PENDING)
                ->form([
                    Forms\Components\Select::make('rejection_reason')
                        ->label('سبب الرفض')
                        ->options(WalletChargeRequestResource::$defaultRejectionReasons)
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
                ->action(function (array $data) {
                    try {
                        $reason = $data['rejection_reason'] === 'custom' 
                            ? $data['custom_reason'] 
                            : $data['rejection_reason'];
                        
                        $admin = Filament::auth()->user();
                        $this->record->reject($admin->id, $reason);
                        
                        Notification::make()
                            ->success()
                            ->title('تم رفض الطلب')
                            ->body('تم إرسال سبب الرفض للمستخدم')
                            ->send();
                        
                        return redirect()->to(WalletChargeRequestResource::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('فشل في رفض الطلب')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
