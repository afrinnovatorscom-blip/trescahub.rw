<?php

return [

    /*
    | Customer-facing payment hint. Do not put live bank account numbers here;
    | set MARKETPLACE_BANK_INSTRUCTIONS in the server environment if needed.
    */
    'bank_instructions' => env(
        'MARKETPLACE_BANK_INSTRUCTIONS',
        'Pay on delivery, or contact TRESCA Hub for bank transfer details.'
    ),

    'delivery_methods' => [
        'in_house' => 'In-house Delivery',
        'third_party_courier' => 'Third-party Courier',
        'customer_collection' => 'Customer Collection',
    ],

    'order_transitions' => [
        'pending_confirmation' => ['confirmed', 'cancellation_requested', 'cancelled'],
        'confirmed' => ['processing', 'awaiting_stock', 'cancellation_requested', 'cancelled'],
        'processing' => ['awaiting_stock', 'ready_for_dispatch', 'cancellation_requested', 'cancelled'],
        'awaiting_stock' => ['processing', 'ready_for_dispatch', 'cancellation_requested', 'cancelled'],
        'ready_for_dispatch' => ['dispatched', 'processing'],
        'dispatched' => ['out_for_delivery', 'delivered'],
        'out_for_delivery' => ['delivered', 'dispatched'],
        'delivered' => ['completed', 'return_requested'],
        'completed' => ['return_requested'],
        'cancellation_requested' => ['cancelled', 'processing'],
        'cancelled' => [],
        'return_requested' => ['returned', 'completed'],
        'returned' => [],
    ],

    'request_transitions' => [
        'submitted' => ['under_review', 'awaiting_information', 'rejected', 'cancelled'],
        'under_review' => ['awaiting_information', 'sourcing', 'awaiting_vendor_response', 'quotation_prepared', 'rejected', 'cancelled'],
        'awaiting_information' => ['under_review', 'sourcing', 'cancelled'],
        'sourcing' => ['awaiting_vendor_response', 'quotation_prepared', 'under_review', 'rejected', 'cancelled'],
        'awaiting_vendor_response' => ['sourcing', 'quotation_prepared', 'awaiting_customer_confirmation', 'cancelled'],
        'quotation_prepared' => ['awaiting_customer_confirmation', 'sourcing', 'cancelled'],
        'awaiting_customer_confirmation' => ['converted_to_order', 'quotation_prepared', 'cancelled'],
        'converted_to_order' => ['fulfillment_in_progress', 'completed', 'cancelled'],
        'fulfillment_in_progress' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
        'rejected' => [],
    ],

    'vendor_item_transitions' => [
        'pending_confirmation' => ['processing'],
        'confirmed' => ['processing', 'awaiting_stock'],
        'processing' => ['awaiting_stock', 'ready_for_dispatch'],
        'awaiting_stock' => ['processing', 'ready_for_dispatch'],
        'ready_for_dispatch' => [],
    ],

    'critical_product_fields' => [
        'product_name',
        'description',
        'price',
        'discount_enabled',
        'original_price',
        'features',
        'specifications',
        'working_days',
        'thumbnail',
        'images',
        'status',
        'is_finished',
        'shop_category_id',
        'subcategory_id',
        'is_popular',
    ],

];
