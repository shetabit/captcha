<?php

namespace Shetabit\Captcha\Abstracts;

use Illuminate\Contracts\View\View;
use Illuminate\Support\ServiceProvider;
use Shetabit\Captcha\Contracts\DriverInterface;
use stdClass;

abstract class Driver implements DriverInterface
{
    /**
     * Driver's settings
     */
    protected stdClass $settings;

    /**
     * Driver constructor.
     *
     * @param array<string, mixed>|object $settings
     */
    abstract public function __construct(ServiceProvider $serviceProvider, mixed $settings);

    /**
     * Generate captcha view.
     */
    abstract public function generate() : View;

    /**
     * Verify the given token against the one that was handed out.
     */
    abstract public function verify(string|null $token = null) : bool;
}
