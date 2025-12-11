<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\FirebaseService;
use App\Services\UltraMsgService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(UltraMsgService $ultraMsgService, FirebaseService $firebaseService): void
    {
        $trackingUrl = url("/api/v1/orders/{$this->order->id}/tracking");

        // Send WhatsApp notification
        $ultraMsgService->sendOrderNotification($this->order->user->phone, [
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
            'tracking_url' => $trackingUrl,
        ]);

        // Send FCM push notification if token exists
        if ($this->order->user->fcm_token) {
            $firebaseService->sendPushNotification(
                $this->order->user->fcm_token,
                'طلب جديد / New Order',
                "تم إنشاء طلبك #{$this->order->order_number} بنجاح",
                [
                    'type' => 'order',
                    'order_id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                ]
            );
        }

        // Create in-app notification
        $this->order->user->notifications()->create([
            'title_ar' => 'طلب جديد',
            'title_en' => 'New Order',
            'body_ar' => "تم إنشاء طلبك #{$this->order->order_number} بنجاح. إجمالي المبلغ: {$this->order->total} جنيه",
            'body_en' => "Your order #{$this->order->order_number} has been created successfully. Total: {$this->order->total} EGP",
            'type' => 'order',
            'data' => [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ],
        ]);
    }
}
