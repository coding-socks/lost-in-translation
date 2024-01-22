<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lost in Translation locale
    |--------------------------------------------------------------------------
    |
    | Here you may specify the locale that should be used by the service.
    | The locale will be considered the origin language of
    | all translation strings.
    |
    */

    'locale' => config('app.locale', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Lost in Translation paths
    |--------------------------------------------------------------------------
    |
    | Here you may specify the paths that should be scanned by the service.
    | The root directories of your blade and application files.
    |
    */

    'paths' => [
        app_path(),
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lost in Translation detection
    |--------------------------------------------------------------------------
    |
    | Here you may specify the different nodes that should be detected
    | by the service. These methods, functions, and static calls
    | will be detected in your **complied** blade files.
    |
    */

    'detect' => [
        'function' => [
            '__',    // __('key')
            'trans', // trans('key')
            'trans_choice', // trans_choice('key', x)
        ],

        'method-function' => [
            ["app('translator')", 'get'], // app('translator')->get('key')
        ],

        'method-static' => [
            ["\App::make('translator')", 'get'], // App::make('translator')->get('key')
            ["\Illuminate\Support\Facades\App::make('translator')", 'get'],
        ],

        'static' => [
            ['\Lang', 'get'], // Lang::get('key')
            ['\Illuminate\Support\Facades\Lang', 'get'],
        ],
    ],

];
