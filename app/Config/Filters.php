<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use App\Filters\JWTAuth;

class Filters extends BaseConfig
{
    public $aliases = [
        'csrf'     => \CodeIgniter\Filters\CSRF::class,
        'toolbar'  => \CodeIgniter\Filters\DebugToolbar::class,
        'honeypot' => \CodeIgniter\Filters\Honeypot::class,
        'cors'     => \CodeIgniter\Filters\Cors::class,
        'auth'     => JWTAuth::class,
    ];

    public $globals = [
        'before' => [
            'cors',
            // 'csrf',
        ],
        'after' => [
            'toolbar',
        ],
    ];

    public $filters = [
        'cors' => [
            'before' => ['api/*', 'client/*', 'members/*', 'member/*'],
            'after'  => ['api/*', 'client/*', 'members/*', 'member/*']
        ],
        'auth' => [
            'before' => [
                'client/*', 
                'members/*', 
                'member/*'
            ],
            // IMPORTANT: Exclude logout so the controller can handle expired tokens
            'except' => [
                'api/login',
                'api/register',
                'api/logout' 
            ]
        ],
    ];
}