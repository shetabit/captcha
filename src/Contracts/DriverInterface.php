<?php

namespace Shetabit\Captcha\Contracts;

use Illuminate\Contracts\View\View;

interface DriverInterface
{
    /**
     * Generate captcha view.
     */
    public function generate() : View;

    /**
     * Verify captcha.
     */
    public function verify(string|null $token = null) : bool;
}
