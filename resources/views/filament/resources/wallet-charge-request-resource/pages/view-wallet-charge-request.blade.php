<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Request Information --}}
        <x-filament::section>
            <x-slot name="heading">
                معلومات الطلب
            </x-slot>
            
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">رقم الطلب</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">#{{ $record->id }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">المستخدم</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->userName }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">المبلغ</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ number_format($record->amount, 2) }} ر.س</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">طريقة الدفع</dt>
                    <dd class="mt-1">
                        <x-filament::badge :color="match($record->payment_method) {
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
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">رقم المرجع</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->payment_reference }}</dd>
                </div>
                @endif
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">الحالة</dt>
                    <dd class="mt-1">
                        <x-filament::badge :color="match($record->status) {
                            'pending' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default => 'gray'
                        }">
                            {{ $record->statusText }}
                        </x-filament::badge>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">تاريخ الإنشاء</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->created_at->format('Y-m-d H:i:s') }}</dd>
                </div>
                
                @if($record->is_resubmission)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">إعادة إرسال</dt>
                    <dd class="mt-1">
                        <x-filament::badge color="warning">
                            <x-slot name="icon">heroicon-o-arrow-path</x-slot>
                            هذا الطلب إعادة إرسال من طلب سابق (#{{ $record->original_request_id }})
                        </x-filament::badge>
                    </dd>
                </div>
                @endif
                
                @if($record->notes)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ملاحظات المستخدم</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->notes }}</dd>
                </div>
                @endif
            </dl>
        </x-filament::section>
        
        {{-- Payment Screenshot --}}
        <x-filament::section>
            <x-slot name="heading">
                صورة إيصال الدفع
            </x-slot>
            
            @if($record->payment_screenshot)
                <div class="flex justify-center">
                    <a href="{{ $record->screenshotUrl }}" target="_blank" class="block">
                        <img src="{{ $record->screenshotUrl }}" 
                             alt="Payment Screenshot" 
                             class="max-w-full h-auto rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-200"
                             style="max-height: 600px;">
                    </a>
                </div>
                <p class="mt-2 text-sm text-center text-gray-500 dark:text-gray-400">
                    اضغط على الصورة لعرضها بالحجم الكامل
                </p>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">لا توجد صورة مرفقة</p>
            @endif
        </x-filament::section>
        
        {{-- Wallet Information --}}
        <x-filament::section>
            <x-slot name="heading">
                معلومات المحفظة
            </x-slot>
            
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">رصيد المحفظة الحالي</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ number_format($record->wallet->balance, 2) }} ر.س</dd>
                </div>
                
                @if($record->status === 'approved')
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">الرصيد بعد الشحن</dt>
                    <dd class="mt-1 text-sm text-green-600 dark:text-green-400 font-semibold">
                        {{ number_format($record->wallet->balance, 2) }} ر.س
                    </dd>
                </div>
                @endif
            </dl>
        </x-filament::section>
        
        {{-- Review Information --}}
        @if($record->status !== 'pending')
        <x-filament::section>
            <x-slot name="heading">
                معلومات المراجعة
            </x-slot>
            
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">تمت المراجعة بواسطة</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->reviewedBy->name ?? 'غير معروف' }}</dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">تاريخ المراجعة</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->reviewed_at?->format('Y-m-d H:i:s') }}</dd>
                </div>
                
                @if($record->status === 'rejected' && $record->rejection_reason)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">سبب الرفض</dt>
                    <dd class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $record->rejection_reason }}</dd>
                </div>
                @endif
                
                @if($record->transaction_id)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">معرف المعاملة</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">#{{ $record->transaction_id }}</dd>
                </div>
                @endif
            </dl>
        </x-filament::section>
        @endif
        
        {{-- Actions --}}
        @if($record->status === 'pending')
        <x-filament::section>
            <x-slot name="heading">
                الإجراءات
            </x-slot>
            
            <div class="flex gap-4">
                {{ ($this->approveAction)(['record' => $record->id]) }}
                {{ ($this->rejectAction)(['record' => $record->id]) }}
            </div>
        </x-filament::section>
        @endif
    </div>
    
    <x-filament-actions::modals />
</x-filament-panels::page>
