<?php

return [
    'enabled'  => (bool) env('WA_ENABLED', true),
    'provider' => env('WA_PROVIDER', 'fonnte'),
    'api_key'  => env('WA_API_KEY'),

    // endpoint kirim Fonnte
    'fonnte_url' => env('FONNTE_BASE_URL', 'https://api.fonnte.com/send'),
];
