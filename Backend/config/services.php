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

    // aliyun：当前启用；doubao：保留实现，改 SPEECH_PROVIDER=doubao 可切回
    'speech' => [
        'provider' => env('SPEECH_PROVIDER', 'aliyun'),
    ],

    'aliyun_tts' => [
        'app_key' => env('ALIYUN_NLS_APP_KEY'),
        'access_key_id' => env('ALIYUN_NLS_ACCESS_KEY_ID'),
        'access_key_secret' => env('ALIYUN_NLS_ACCESS_KEY_SECRET'),
        'voice' => env('ALIYUN_NLS_VOICE', 'xiaoyun'),
        'sample_rate' => (int) env('ALIYUN_NLS_SAMPLE_RATE', 16000),
        'gateway' => env('ALIYUN_NLS_GATEWAY', 'https://nls-gateway-cn-shanghai.aliyuncs.com/stream/v1/tts'),
        'meta_host' => env('ALIYUN_NLS_META_HOST', 'nls-meta.cn-shanghai.aliyuncs.com'),
    ],

    'doubao_tts' => [
        'key' => env('DOUBAO_TTS_API_KEY'),
        'resource_id' => env('DOUBAO_TTS_RESOURCE_ID', 'seed-tts-2.0'),
        'speaker' => env('DOUBAO_TTS_SPEAKER', 'zh_female_yingyujiaoxue_uranus_bigtts'),
        'host' => env('DOUBAO_TTS_HOST', 'openspeech.bytedance.com'),
    ],

];
