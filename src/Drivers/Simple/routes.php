<?php

use Illuminate\Support\Facades\Route;
use Shetabit\Captcha\Drivers\Simple\Controllers\CaptchaController;

Route::get('captcha', [CaptchaController::class, 'getCaptcha'])
    ->name(config('captcha.drivers.simple.route', 'captcha'))
    ->middleware(config('captcha.drivers.simple.middleware', ['web']));
