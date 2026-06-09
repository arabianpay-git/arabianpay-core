<?php

$envPath = __DIR__ . '/../.env';

if (file_exists($envPath)) {
    header('Content-Type: text/plain');
    
    readfile($envPath);
    exit;
} else {
    http_response_code(404);
    echo ".env file not found.";
}