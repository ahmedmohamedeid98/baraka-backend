<?php

return [
    'otp' => [
        'invalid_or_expired' => 'رمز التحقق غير صالح أو منتهي الصلاحية',
        'request_processed' => 'تم معالجة طلب رمز التحقق',
        'sent_to_new_phone' => 'تم إرسال رمز التحقق إلى رقم الهاتف الجديد',
    ],
    'auth' => [
        'login_successful' => 'تم تسجيل الدخول بنجاح',
        'logout_successful' => 'تم تسجيل الخروج بنجاح',
    ],
    'profile' => [
        'updated_successfully' => 'تم تحديث الملف الشخصي بنجاح',
        'avatar_updated' => 'تم تحديث الصورة الشخصية بنجاح',
        'avatar_deleted' => 'تم حذف الصورة الشخصية بنجاح',
        'phone_updated' => 'تم تحديث رقم الهاتف بنجاح',
        'fcm_token_updated' => 'تم تحديث رمز الإشعارات',
    ],
    'address' => [
        'created_successfully' => 'تم إضافة العنوان بنجاح',
        'updated_successfully' => 'تم تحديث العنوان بنجاح',
        'deleted_successfully' => 'تم حذف العنوان بنجاح',
    ],
    'cart' => [
        'item_added' => 'تم إضافة المنتج إلى السلة',
        'item_updated' => 'تم تحديث منتج السلة',
        'item_removed' => 'تم حذف المنتج من السلة',
        'cleared' => 'تم تفريغ السلة',
        'insufficient_stock' => 'الكمية المتوفرة غير كافية',
        'empty' => 'السلة فارغة',
    ],
    'order' => [
        'created_successfully' => 'تم إنشاء الطلب بنجاح',
        'cancelled_successfully' => 'تم إلغاء الطلب بنجاح',
        'cannot_cancel' => 'لا يمكن إلغاء الطلب في هذه المرحلة',
        'single_vendor_only' => 'يرجى الطلب من متجر واحد في كل مرة',
        'creation_failed' => 'فشل إنشاء الطلب',
        'payment_screenshot_required' => 'صورة إثبات الدفع مطلوبة لهذه الطريقة',
        'cannot_update_payment' => 'يمكن تحديث الدفع فقط للطلبات المعلقة',
        'payment_updated' => 'تم تحديث طريقة الدفع بنجاح',
    ],
    'coupon' => [
        'invalid' => 'كود الخصم غير صالح',
        'cannot_be_used' => 'لا يمكن استخدام هذا الكود',
        'expired' => 'كود الخصم غير صالح أو منتهي الصلاحية',
        'usage_limit_reached' => 'لقد وصلت إلى الحد الأقصى لاستخدام هذا الكود',
        'minimum_not_met' => 'الحد الأدنى للطلب :min_order_amount جنيه',
        'applied_successfully' => 'تم تطبيق كود الخصم بنجاح. وفرت :discount جنيه',
        'removed_successfully' => 'تم إزالة كود الخصم بنجاح',
        'vendor_mismatch' => 'هذا الكود غير صالح للمتجر المحدد',
    ],
    'smart_order' => [
        'analyzed_successfully' => 'تم تحليل الطلب بنجاح',
        'updated_successfully' => 'تم تحديث الطلب بنجاح',
        'deleted_successfully' => 'تم حذف الطلب بنجاح',
        'retrieved_successfully' => 'تم استرجاع الطلب بنجاح',
    ],
    'favorite' => [
        'added' => 'تم إضافة المنتج إلى المفضلة',
        'removed' => 'تم إزالة المنتج من المفضلة',
        'already_exists' => 'المنتج موجود بالفعل في المفضلة',
        'not_found' => 'المنتج غير موجود في المفضلة',        'no_favorites' => 'لا توجد مفضلات لمسحها',
        'cleared_all' => 'تم مسح جميع المفضلات بنجاح',    ],
];
