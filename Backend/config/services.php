<?php

return [

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

    'doubao_tts' => [
        'key' => env('DOUBAO_TTS_API_KEY'),
        'resource_id' => env('DOUBAO_TTS_RESOURCE_ID', 'seed-tts-2.0'),
        'speaker' => env('DOUBAO_TTS_SPEAKER', 'zh_female_zhixingnv_uranus_bigtts'),
        'host' => env('DOUBAO_TTS_HOST', 'openspeech.bytedance.com'),
    ],

];
