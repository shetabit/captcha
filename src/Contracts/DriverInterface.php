<?php

namespace Shetabit\Captcha\Contracts;

interface DriverInterface
{
    /**
     * Generate captcha view.
     *
     * @return string
     */
    public function generate();

    /**
     * Verify captcha.
     *
     * @param null|$token
     * @return mixed
     */
    public function verify($token = null);
}
