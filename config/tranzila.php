<?php

return [
    'terminal' => env('TRANZILA_TERMINAL'),
    'password' => env('TRANZILA_PASSWORD'),
    'secret_key' => env('TRANZILA_SECRET_KEY'),
    'public_key' => env('TRANZILA_PUBLIC_KEY'),
    'api_url' => env('TRANZILA_API_URL', 'https://secure5.tranzila.com/cgi-bin/tranzila71u.cgi'),
];
