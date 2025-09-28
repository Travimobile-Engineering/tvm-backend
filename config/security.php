<?php

return [
    'encoding_key' => env('ENCODING_KEY'),

    'header_key' => env('X_HEADER_KEY', 'X-SECURE-AUTH'),
    'header_value' => env('X_HEADER_VALUE', 'your-secret-value'),
];
