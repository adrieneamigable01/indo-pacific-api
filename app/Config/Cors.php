<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cors extends BaseConfig
{
    // Fix for "Undefined property: Config\Cors::$default"
    // Your version of CI4 requires the settings to be wrapped in a 'default' property.
    public array $default = [
        // allowedOrigins defines which domains can access your API
        'allowedOrigins' => [
            'http://localhost:59110', // <-- YOUR FRONTEND ORIGIN
            'http://127.0.0.1:59110' // <-- ADD THIS LINE
            // '*' // Use only if necessary for quick testing
        ],

        // allowedMethods defines which HTTP verbs are permitted (GET, POST, OPTIONS, etc.)
        'allowedMethods' => [
            'GET', 
            'POST', 
            'OPTIONS', 
            'PUT', 
            'DELETE'
        ],

        // allowCredentials must be true to allow the browser to send 'Authorization' headers
        'allowCredentials' => true, 

        // allowedHeaders lists the headers your API will accept, crucial for 'Authorization'
        'allowedHeaders' => [
            'X-Requested-With', 
            'Content-Type', 
            'Authorization' 
        ],
        
        // maxAge caches the preflight response in the browser for this duration (in seconds)
        'maxAge' => 7200,
    ];
}