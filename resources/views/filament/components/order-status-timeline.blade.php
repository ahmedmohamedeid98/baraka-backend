<div style="width: 100%;">
    @php
        $currentStatus = is_callable($currentStatus) ? $currentStatus($getRecord()) : $currentStatus;
        $orderId = is_callable($orderId) ? $orderId($getRecord()) : $orderId;
        
        // Define the main flow (excluding cancelled)
        $mainFlow = ['pending', 'confirmed', 'preparing', 'on_the_way', 'delivered'];
        $currentIndex = array_search($currentStatus, $mainFlow);
    @endphp
    
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Main Status Flow -->
        <div style="position: relative;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                @foreach($mainFlow as $index => $status)
                    @php
                        $config = $statusFlow[$status];
                        $isCurrent = $status === $currentStatus;
                        $isCompleted = $currentIndex !== false && $index < $currentIndex;
                        $isClickable = in_array($status, $statusFlow[$currentStatus]['next'] ?? []);
                        $isDisabled = !$isCurrent && !$isCompleted && !$isClickable;
                        
                        // Determine button styles
                        $buttonStyles = 'position: relative; z-index: 10; display: flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: 50%; border-width: 2px; transition: all 0.2s;';
                        
                        if ($isCompleted) {
                            $buttonStyles .= ' background-color: #10b981; border-color: #059669; color: white;';
                        } elseif ($isCurrent) {
                            $buttonStyles .= ' background-color: #3b82f6; border-color: #2563eb; color: white; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3); transform: scale(1.1);';
                        } elseif ($isClickable) {
                            $buttonStyles .= ' background-color: white; border-color: #10b981; color: #10b981; cursor: pointer;';
                        } else {
                            $buttonStyles .= ' background-color: #f3f4f6; border-color: #d1d5db; color: #9ca3af; cursor: not-allowed;';
                        }
                        
                        // Determine label styles
                        $labelStyles = 'margin-top: 0.5rem; font-size: 0.875rem; font-weight: 500; text-align: center;';
                        if ($isCompleted) {
                            $labelStyles .= ' color: #10b981;';
                        } elseif ($isCurrent) {
                            $labelStyles .= ' color: #3b82f6; font-weight: 700;';
                        } elseif ($isClickable) {
                            $labelStyles .= ' color: #10b981;';
                        } else {
                            $labelStyles .= ' color: #9ca3af;';
                        }
                    @endphp
                    
                    <div style="display: flex; flex-direction: column; align-items: center; flex: 1; position: relative;">
                        <!-- Connecting Line (before current node) -->
                        @if($index > 0)
                            <div style="position: absolute; top: 1.5rem; right: 50%; width: 100%; height: 0.125rem; z-index: -10; transform: translateY(-50%); {{ $isCompleted || $isCurrent ? 'background-color: #10b981;' : 'background-color: #d1d5db;' }}"></div>
                        @endif
                        
                        <!-- Status Node -->
                        <button
                            type="button"
                            wire:click="$dispatch('openStatusModal', { status: '{{ $status }}', orderId: {{ $orderId }} })"
                            style="{{ $buttonStyles }}"
                            @if($isDisabled || $isCurrent) disabled @endif
                            @if($isClickable) onmouseover="this.style.backgroundColor='#dcfce7'; this.style.transform='scale(1.05)';" onmouseout="this.style.backgroundColor='white'; this.style.transform='scale(1)';" @endif
                            title="{{ $isClickable ? 'Click to update to ' . $config['label'] : $config['label'] }}"
                        >
                            <x-filament::icon
                                :icon="$config['icon']"
                                style="width: 1.5rem; height: 1.5rem;"
                            />
                        </button>
                        
                        <!-- Status Label -->
                        <span style="{{ $labelStyles }}">
                            {{ $config['label'] }}
                        </span>
                        
                        @if($isCurrent)
                            <span style="margin-top: 0.25rem; font-size: 0.75rem; color: #6b7280;">Current</span>
                        @elseif($isClickable)
                            <span style="margin-top: 0.25rem; font-size: 0.75rem; color: #10b981;">Available</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Cancelled Status (Separate) -->
        @if($currentStatus !== 'cancelled' && in_array('cancelled', $statusFlow[$currentStatus]['next'] ?? []))
            <div style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <div style="display: flex; align-items: center; justify-content: center;">
                    <button
                        type="button"
                        wire:click="$dispatch('openStatusModal', { status: 'cancelled', orderId: {{ $orderId }} })"
                        style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; color: white; background-color: #dc2626; border-radius: 0.5rem; transition: background-color 0.2s; border: none; cursor: pointer;"
                        onmouseover="this.style.backgroundColor='#b91c1c';"
                        onmouseout="this.style.backgroundColor='#dc2626';"
                    >
                        <x-filament::icon
                            icon="heroicon-o-x-circle"
                            style="width: 1.25rem; height: 1.25rem;"
                        />
                        Cancel Order
                    </button>
                </div>
            </div>
        @elseif($currentStatus === 'cancelled')
            <div style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <div style="display: flex; align-items: center; justify-content: center;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; color: white; background-color: #dc2626; border-radius: 0.5rem;">
                        <x-filament::icon
                            icon="heroicon-o-x-circle"
                            style="width: 1.25rem; height: 1.25rem;"
                        />
                        Order Cancelled
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Helper Text -->
        <div style="margin-top: 1rem; padding: 0.75rem; background-color: #f9fafb; border-radius: 0.5rem;">
            <p style="font-size: 0.75rem; color: #4b5563;">
                <span style="font-weight: 600;">Instructions:</span> Click on any <span style="color: #10b981; font-weight: 600;">green</span> status to move the order forward. You must provide a reason for each status change.
            </p>
        </div>
    </div>
</div>
