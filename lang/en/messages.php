<?php

return [
    'otp' => [
        'invalid_or_expired' => 'Invalid or expired OTP code',
        'request_processed' => 'OTP request processed',
        'sent_to_new_phone' => 'OTP sent to new phone number',
    ],
    'auth' => [
        'login_successful' => 'Login successful',
        'logout_successful' => 'Logged out successfully',
    ],
    'profile' => [
        'updated_successfully' => 'Profile updated successfully',
        'avatar_updated' => 'Avatar updated successfully',
        'avatar_deleted' => 'Avatar deleted successfully',
        'phone_updated' => 'Phone number updated successfully',
        'fcm_token_updated' => 'FCM token updated',
    ],
    'address' => [
        'created_successfully' => 'Address created successfully',
        'updated_successfully' => 'Address updated successfully',
        'deleted_successfully' => 'Address deleted successfully',
    ],
    'cart' => [
        'item_added' => 'Item added to cart',
        'item_updated' => 'Cart item updated',
        'item_removed' => 'Cart item removed',
        'cleared' => 'Cart cleared',
        'insufficient_stock' => 'Insufficient stock',
        'empty' => 'Cart is empty',
    ],
    'order' => [
        'created_successfully' => 'Order created successfully',
        'cancelled_successfully' => 'Order cancelled successfully',
        'cannot_cancel' => 'Order cannot be cancelled at this stage',
        'single_vendor_only' => 'Please order from one vendor at a time',
        'creation_failed' => 'Failed to create order',
    ],
    'coupon' => [
        'invalid' => 'Invalid coupon code',
        'cannot_be_used' => 'Coupon cannot be used',
        'expired' => 'Coupon is not valid or has expired',
        'usage_limit_reached' => 'You have reached the usage limit for this coupon',
        'minimum_not_met' => 'Minimum order amount not met',
    ],
    'smart_order' => [
        'analyzed_successfully' => 'Order analyzed successfully',
        'updated_successfully' => 'Smart order updated successfully',
        'deleted_successfully' => 'Smart order deleted successfully',
        'retrieved_successfully' => 'Order retrieved successfully',
    ],
    'favorite' => [
        'added' => 'Product added to favorites',
        'removed' => 'Product removed from favorites',
        'already_exists' => 'Product is already in favorites',
        'not_found' => 'Product not found in favorites',
        'no_favorites' => 'No favorites to clear',
        'cleared_all' => 'All favorites cleared successfully',
    ],
];
