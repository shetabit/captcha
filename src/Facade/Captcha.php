<?php

namespace Shetabit\Captcha\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class captcha
 *
 * @package Shetabit\Captcha\Facade
 * @see \Shetabit\Captcha\CaptchaManager
 */
class Captcha extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'shetabit-captcha';
    }
}
