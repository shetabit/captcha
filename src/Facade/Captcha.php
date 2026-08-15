<?php

namespace Shetabit\Captcha\Facade;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Facade;
use Shetabit\Captcha\CaptchaManager;
use Shetabit\Captcha\Contracts\DriverInterface;

/**
 * The captcha manager, as a Laravel facade.
 *
 * @method static CaptchaManager via(string $driver)
 * @method static string getDriver()
 * @method static DriverInterface prepareDriver()
 * @method static View generate()
 * @method static bool verify(string|null $token = null)
 *
 * @see CaptchaManager
 */
class Captcha extends Facade
{
    /**
     * The name the captcha manager is bound to in the service container.
     */
    public const string SERVICE_NAME = 'shetabit-captcha';

    /**
     * Get the registered name of the component.
     */
    public static function getFacadeAccessor() : string
    {
        return self::SERVICE_NAME;
    }
}
