<?php

return [

    'server_signing' => [
        'python' => env('SERVER_SIGNING_PYTHON', base_path('storage/app/signing-venv/bin/python')),
        'script' => env('SERVER_SIGNING_SCRIPT', base_path('tools/server_sign_pdf.py')),
    ],

    'reniec_agent' => [
        'token' => env('RENIEC_AGENT_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'reniec' => [
        'url' => env('RENIEC_API_URL', 'https://api.decolecta.com/v1/reniec/dni'),
        'token' => env('RENIEC_API_TOKEN'),
    ],

    'perudevs' => [
        'url' => env('PERUDEVS_DNI_URL', 'https://api.perudevs.com/api/v1/dni/simple'),
        'token' => env('PERUDEVS_DNI_TOKEN'),
    ],

    'apiperu' => [
        'url' => env('APIPERU_DNI_URL', 'https://apiperu.dev/api/dni'),
        'token' => env('APIPERU_DNI_TOKEN'),
    ],

    'firma_peru' => [
        'enabled' => env('FIRMA_PERU_ENABLED', false),
        'client_id' => env('FIRMA_PERU_CLIENT_ID'),
        'client_secret' => env('FIRMA_PERU_CLIENT_SECRET'),
        'token_url' => env('FIRMA_PERU_TOKEN_URL'),
        'tsa_url' => env('FIRMA_PERU_TSA_URL', ''),
        'local_port' => (int) env('FIRMA_PERU_LOCAL_PORT', 48596),
        'script_url' => env('FIRMA_PERU_SCRIPT_URL', 'https://apps.firmaperu.gob.pe/web/clienteweb/firmaperu.min.js'),
    ],

];
