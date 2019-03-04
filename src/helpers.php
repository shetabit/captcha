<?php

if ( ! function_exists('captcha')) {

    /**
     * Return Image
     *
     * @return resource
     */
    function captcha()
    {
        return app('shetabit-captcha')->generate();
    }
}

if ( ! function_exists('captcha_refresh')) {

    /**
     * Return Image
     *
     * @return resource
     */
    function captcha_refresh()
    {
        return app('shetabit-captcha')->generate();
    }
}

if ( ! function_exists('captcha_verify')) {
    /**
     * verify captcha
     *
     * @param null|$value
     * @return mixed
     */
    function captcha_verify($value = null)
    {
        return app('shetabit-captcha')->verify($value);
    }
}
