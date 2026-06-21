<?php

return [
    'base_url' => env('ODOO_BASE_URL', 'https://arabian-pay.odoo.com'),
    'login' => env('ODOO_LOGIN', 'admin'),
    'password' => env('ODOO_PASSWORD', 'admin'),
    'token_ttl' => env('ODOO_TOKEN_TTL', 3500),
];
