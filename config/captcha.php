<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | This value determines which of the following gateway to use.
    | You can switch to a different driver at runtime.
    |
    */
    'default' => 'simple',

    /*
    |--------------------------------------------------------------------------
    | List of Drivers
    |--------------------------------------------------------------------------
    |
    | These are the list of drivers to use for this package.
    | You can change the name. Then you'll have to change
    | it in the map array too.
    |
    */
    'drivers' => [
        'simple' => [
            'middleware' => ['web'], // middleware
            'route' => 'captcha', // route name
            'characters' => 'ABCDEFGHIKJLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789',
            'width'  => 230,
            'height' => 70,
            'foregroundColors' => ['2980b9','2E9FFF','FF1166','000000','22EE99'],
            'backgroundColor' => '#FFF',
            'letterSpacing' => 6,
            'fontFamily' => resource_path('views/vendor/captchaSimpleDriver/assets/fonts/DroidSerif.ttf'),
            'fontSize' => 30,
            'length' => [4, 6],
            'scratches' => [5, 8],
            'sensitive' => false,
            'sessionKey' => 'captcha',
        ],
    ],

    'validator' => 'captcha',

    /*
    |--------------------------------------------------------------------------
    | Class Maps
    |--------------------------------------------------------------------------
    |
    | This is the array of Classes that maps to Drivers above.
    | You can create your own driver if you like and add the
    | config in the drivers array and the class to use for
    | here with the same name. You will have to extend
    | Shetabit\captcha\Abstracts\Driver in your driver.
    |
    */
    'map' => [
        'simple' => \Shetabit\Captcha\Drivers\Simple\SimpleDriver::class,
    ]
];
