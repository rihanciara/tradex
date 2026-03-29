<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Exchange Module Assets Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the asset configuration for the Exchange module,
    | including JavaScript files, CSS files, and publishing paths.
    |
    */

    'js' => [
        'pos-exchange' => [
            'source' => 'Resources/assets/js/pos-exchange.js',
            'destination' => 'js/pos-exchange.js',
            'description' => 'POS Exchange functionality JavaScript',
            'dependencies' => ['jquery', 'bootstrap'],
            'auto_publish' => true,
        ],
    ],

    'css' => [
        // Future CSS files can be defined here
    ],

    'publishing' => [
        'auto_publish_in_development' => true,
        'versioning' => true,
        'minification' => false, // Set to true for production
    ],

    'pos_integration' => [
        'enabled' => true,
        'permission_required' => 'exchange.create',
        'button_position' => 'after_card_payment',
        'modal_size' => 'modal-xl',
    ],
];