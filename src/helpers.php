<?php

use Illuminate\Contracts\View\View;
use Shetabit\Captcha\CaptchaManager;
use Shetabit\Captcha\Facade\Captcha;

if (!function_exists('captcha')) {
    /**
     * Render the captcha of the current driver.
     */
    function captcha() : View
    {
        return captcha_manager()->generate();
    }
}

if (!function_exists('captcha_refresh')) {
    /**
     * Render a new captcha.
     *
     * @alias captcha
     */
    function captcha_refresh() : View
    {
        return captcha();
    }
}

if (!function_exists('captcha_verify')) {
    /**
     * Verify the given token against the captcha that was handed out.
     */
    function captcha_verify(string|null $value = null) : bool
    {
        return captcha_manager()->verify($value);
    }
}

if (!function_exists('captcha_manager')) {
    /**
     * The captcha manager of the application.
     */
    function captcha_manager() : CaptchaManager
    {
        return app(Captcha::SERVICE_NAME);
    }
}
