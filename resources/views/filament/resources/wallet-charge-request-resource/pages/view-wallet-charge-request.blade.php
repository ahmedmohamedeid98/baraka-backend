<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        {{-- Status Banner --}}
        <div style="border-radius: 0.5rem; padding: 1.5rem; {{ match($record->status) {
            'pending' => 'background-color: #fef3c7; border: 2px solid #fcd34d;',
            'approved' => 'background-color: #d1fae5; border: 2px solid #6ee7b7;',
            'rejected' => 'background-color: #fee2e2; border: 2px solid #fca5a5;',
            default => 'background-color: #f9fafb; border: 2px solid #e5e7eb;'
        } }}">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="display: flex; height: 3rem; width: 3rem; align-items: center; justify-content: center; border-radius: 9999px; {{ match($record->status) {
                        'pending' => 'background-color: #fef3c7;',
                        'approved' => 'background-color: #d1fae5;',
                        'rejected' => 'background-color: #fee2e2;',
                        default => 'background-color: #f3f4f6;'
                    } }}">
                        @if($record->status === 'pending')
                            <x-heroicon-o-clock style="height: 1.5rem; width: 1.5rem; color: #f59e0b;" />
                        @elseif($record->status === 'approved')
                            <x-heroicon-o-check-circle style="height: 1.5rem; width: 1.5rem; color: #10b981;" />
                        @else
                            <x-heroicon-o-x-circle style="height: 1.5rem; width: 1.5rem; color: #ef4444;" />
                        @endif
                    </div>
                    <div>
                        <h3 style="font-size: 1.125rem; font-weight: 600; {{ match($record->status) {
                            'pending' => 'color: #78350f;',
                            'approved' => 'color: #064e3b;',
                            'rejected' => 'color: #7f1d1d;',
                            default => 'color: #111827;'
                        } }}">
                            {{ $record->statusText }}
                        </h3>
                        <p style="font-size: 0.875rem; {{ match($record->status) {
                            'pending' => 'color: #b45309;',
                            'approved' => 'color: #047857;',
                            'rejected' => 'color: #b91c1c;',
                            default => 'color: #4b5563;'
                        } }}">
                            طلب شحن محفظة #{{ $record->id }}
                        </p>
                    </div>
                </div>
                <div style="text-align: left;">
                    <div style="font-size: 1.875rem; font-weight: 700; {{ match($record->status) {
                        'pending' => 'color: #78350f;',
                        'approved' => 'color: #064e3b;',
                        'rejected' => 'color: #7f1d1d;',
                        default => 'color: #111827;'
                    } }}">
                        {{ number_format($record->amount, 2) }}
                    </div>
                    <div style="font-size: 0.875rem; font-weight: 500; {{ match($record->status) {
                        'pending' => 'color: #b45309;',
                        'approved' => 'color: #047857;',
                        'rejected' => 'color: #b91c1c;',
                        default => 'color: #4b5563;'
                    } }}">
                        جنيه مصري
                    </div>
                </div>
            </div>
        </div>

        <div style="display: grid; gap: 1.5rem; grid-template-columns: 1fr;" class="lg:grid-cols-3">
            {{-- Left Column --}}
            <div style="display: flex; flex-direction: column; gap: 1.5rem;" class="lg:col-span-2">
                {{-- Payment Screenshot --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <x-heroicon-o-photo style="height: 1.25rem; width: 1.25rem;" />
                            <span>صورة إيصال الدفع</span>
                        </div>
                    </x-slot>
                    
                    @if($record->payment_screenshot)
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div style="position: relative; overflow: hidden; border-radius: 0.75rem; border: 2px solid #e5e7eb; background-color: #f9fafb;">
                                <a href="{{ $record->screenshotUrl }}" target="_blank" style="display: block; position: relative; overflow: hidden;">
                                    <img src="{{ $record->screenshotUrl }}" 
                                         alt="Payment Screenshot" 
                                         style="width: 100%; height: auto; transition: transform 0.3s;">
                                    <div style="position: absolute; inset: 0; background-color: rgba(0,0,0,0); transition: background-color 0.3s; display: flex; align-items: center; justify-content: center;">
                                        <div style="opacity: 0; transition: opacity 0.3s; background-color: white; border-radius: 9999px; padding: 0.75rem;">
                                            <x-heroicon-o-magnifying-glass-plus style="height: 1.5rem; width: 1.5rem; color: #374151;" />
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                                <x-heroicon-o-information-circle style="height: 1rem; width: 1rem;" />
                                <span>اضغط على الصورة لعرضها بالحجم الكامل</span>
                            </div>
                        </div>
                    @else
                        <div style="display: flex; align-items: center; justify-content: center; padding: 3rem 0; color: #6b7280;">
                            <div style="text-align: center;">
                                <x-heroicon-o-photo style="margin: 0 auto 0.5rem; height: 3rem; width: 3rem; opacity: 0.5;" />
                                <p>لا توجد صورة مرفقة</p>
                            </div>
                        </div>
                    @endif
                </x-filament::section>

                {{-- User Information --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <x-heroicon-o-user style="height: 1.25rem; width: 1.25rem;" />
                            <span>معلومات المستخدم</span>
                        </div>
                    </x-slot>
                    
                    <div style="display: grid; gap: 1rem; grid-template-columns: 1fr;" class="sm:grid-cols-2">
                        <div style="border-radius: 0.5rem; background-color: #f9fafb; padding: 1rem;">
                            <dt style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">الاسم</dt>
                            <dd style="font-size: 1rem; font-weight: 600; color: #111827;">{{ $record->userName }}</dd>
                        </div>
                        
                        <div style="border-radius: 0.5rem; background-color: #f9fafb; padding: 1rem;">
                            <dt style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">معرف المستخدم</dt>
                            <dd style="font-size: 1rem; font-weight: 600; color: #111827;">#{{ $record->user_id }}</dd>
                        </div>
                        
                        <div style="border-radius: 0.5rem; background-color: #f9fafb; padding: 1rem;">
                            <dt style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">النوع</dt>
                            <dd style="margin-top: 0.25rem;">
                                <x-filament::badge size="lg" :color="$record->user_type === 'App\\Models\\Vendor' ? 'success' : 'info'">
                                    {{ $record->user_type === 'App\\Models\\Vendor' ? 'تاجر' : 'عميل' }}
                                </x-filament::badge>
                            </dd>
                        </div>
                        
                        <div style="border-radius: 0.5rem; background-color: #f9fafb; padding: 1rem;">
                            <dt style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">رقم الهاتف</dt>
                            <dd style="font-size: 1rem; font-weight: 600; color: #111827;" dir="ltr">{{ $record->wallet->walletable->phone ?? 'غير متوفر' }}</dd>
                        </div>
                    </div>
                </x-filament::section>

                @if($record->notes)
                {{-- Notes --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <x-heroicon-o-chat-bubble-left-right style="height: 1.25rem; width: 1.25rem;" />
                            <span>ملاحظات المستخدم</span>
                        </div>
                    </x-slot>
                    
                    <div style="border-radius: 0.5rem; background-color: #eff6ff; border: 1px solid #bfdbfe; padding: 1rem;">
                        <p style="font-size: 0.875rem; color: #111827; line-height: 1.625;">{{ $record->notes }}</p>
                    </div>
                </x-filament::section>
                @endif
            </div>

            {{-- Right Column --}}
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                {{-- Payment Details --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <x-heroicon-o-credit-card style="height: 1.25rem; width: 1.25rem;" />
                            <span>تفاصيل الدفع</span>
                        </div>
                    </x-slot>
                    
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="border-radius: 0.5rem; background-color: #f9fafb; padding: 1rem;">
                            <dt style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">طريقة الدفع</dt>
                            <dd>
                                <x-filament::badge size="lg" :color="match($record->payment_method) {
                                    'vodafone_cash' => 'danger',
                                    'instapay' => 'info',
                                    'bank_transfer' => 'success',
                                    default => 'gray'
                                }">
                                    {{ $record->paymentMethodText }}
                                </x-filament::badge>
                            </dd>
                        </div>
                        
                        @if($record->payment_reference)
                        <div style="border-radius: 0.5rem; background-color: #f9fafb; padding: 1rem;">
                            <dt style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">رقم المرجع</dt>
                            <dd style="font-size: 1rem; font-family: monospace; font-weight: 600; color: #111827;">{{ $record->payment_reference }}</dd>
                        </div>
                        @endif
                        
                        <div style="border-radius: 0.5rem; background-color: #f9fafb; padding: 1rem;">
                            <dt style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">تاريخ الإنشاء</dt>
                            <dd style="font-size: 0.875rem; color: #111827;">
                                <div style="font-weight: 600;">{{ $record->created_at->format('Y-m-d') }}</div>
                                <div style="font-size: 0.75rem; color: #6b7280;" dir="ltr">{{ $record->created_at->format('h:i A') }}</div>
                            </dd>
                        </div>

                        @if($record->is_resubmission)
                        <div style="border-radius: 0.5rem; background-color: #fef3c7; border: 1px solid #fcd34d; padding: 1rem;">
                            <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                                <x-heroicon-o-arrow-path style="height: 1.25rem; width: 1.25rem; color: #f59e0b; flex-shrink: 0; margin-top: 0.125rem;" />
                                <div>
                                    <dt style="font-size: 0.75rem; font-weight: 500; color: #b45309; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">إعادة إرسال</dt>
                                    <dd style="font-size: 0.875rem; color: #78350f;">
                                        طلب سابق: #{{ $record->original_request_id }}
                                    </dd>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </x-filament::section>

                {{-- Wallet Information --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <x-heroicon-o-wallet style="height: 1.25rem; width: 1.25rem;" />
                            <span>المحفظة</span>
                        </div>
                    </x-slot>
                    
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <div style="border-radius: 0.5rem; background-color: #f9fafb; padding: 1rem;">
                            <dt style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">الرصيد الحالي</dt>
                            <dd style="font-size: 1.5rem; font-weight: 700; color: #111827;">
                                {{ number_format($record->wallet->balance, 2) }}
                                <span style="font-size: 0.875rem; font-weight: 400; color: #6b7280;">ج.م</span>
                            </dd>
                        </div>
                        
                        @if($record->status === 'approved')
                        <div style="border-radius: 0.5rem; background-color: #d1fae5; border: 1px solid #6ee7b7; padding: 1rem;">
                            <dt style="font-size: 0.75rem; font-weight: 500; color: #047857; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">بعد الشحن</dt>
                            <dd style="font-size: 1.25rem; font-weight: 700; color: #064e3b;">
                                {{ number_format($record->wallet->balance, 2) }}
                                <span style="font-size: 0.875rem; font-weight: 400;">ج.م</span>
                            </dd>
                        </div>
                        @elseif($record->status === 'pending')
                        <div style="border-radius: 0.5rem; background-color: #fef3c7; border: 1px solid #fcd34d; padding: 1rem;">
                            <dt style="font-size: 0.75rem; font-weight: 500; color: #b45309; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">بعد الموافقة</dt>
                            <dd style="font-size: 1.25rem; font-weight: 700; color: #78350f;">
                                {{ number_format($record->wallet->balance + $record->amount, 2) }}
                                <span style="font-size: 0.875rem; font-weight: 400;">ج.م</span>
                            </dd>
                        </div>
                        @endif
                    </div>
                </x-filament::section>

                {{-- Review Information --}}
                @if($record->status !== 'pending')
                <x-filament::section>
                    <x-slot name="heading">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <x-heroicon-o-document-check style="height: 1.25rem; width: 1.25rem;" />
                            <span>المراجعة</span>
                        </div>
                    </x-slot>
                    
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <div style="border-radius: 0.5rem; background-color: #f9fafb; padding: 1rem;">
                            <dt style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">المراجع</dt>
                            <dd style="font-size: 0.875rem; font-weight: 600; color: #111827;">{{ $record->reviewedBy->name ?? 'غير معروف' }}</dd>
                        </div>
                        
                        <div style="border-radius: 0.5rem; background-color: #f9fafb; padding: 1rem;">
                            <dt style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">التاريخ</dt>
                            <dd style="font-size: 0.875rem; color: #111827;">
                                <div style="font-weight: 600;">{{ $record->reviewed_at?->format('Y-m-d') }}</div>
                                <div style="font-size: 0.75rem; color: #6b7280;" dir="ltr">{{ $record->reviewed_at?->format('h:i A') }}</div>
                            </dd>
                        </div>
                        
                        @if($record->status === 'rejected' && $record->rejection_reason)
                        <div style="border-radius: 0.5rem; background-color: #fee2e2; border: 2px solid #fca5a5; padding: 1rem;">
                            <dt style="font-size: 0.75rem; font-weight: 500; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">سبب الرفض</dt>
                            <dd style="font-size: 0.875rem; color: #7f1d1d; line-height: 1.625;">{{ $record->rejection_reason }}</dd>
                        </div>
                        @endif
                        
                        @if($record->transaction_id)
                        <div style="border-radius: 0.5rem; background-color: #d1fae5; border: 1px solid #6ee7b7; padding: 1rem;">
                            <dt style="font-size: 0.75rem; font-weight: 500; color: #047857; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">المعاملة</dt>
                            <dd style="font-size: 0.875rem; font-family: monospace; font-weight: 600; color: #064e3b;">#{{ $record->transaction_id }}</dd>
                        </div>
                        @endif
                    </div>
                </x-filament::section>
                @endif
            </div>
        </div>

    </div>
</x-filament-panels::page>
