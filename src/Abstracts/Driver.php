<?php

namespace Shetabit\Captcha\Abstracts;

use Illuminate\Support\ServiceProvider;
use Shetabit\Captcha\Contracts\DriverInterface;

abstract class Driver implements DriverInterface
{
    /**
     * Driver's settings
     *
     * @var
     */
    protected $settings;

    /**
     * Driver constructor.
     *
     * Driver constructor.
     * @param ServiceProvider $serviceProvider
     * @param $settings
     */
    abstract public function __construct(ServiceProvider $serviceProvider, $settings);


    /**
     * Generate captcha view.
     *
     * @return mixed
     */
    abstract public function generate();

    /**
     * Verify the payment
     *
     * @param null|$token
     * @return bool
     */
    abstract public function verify($token = null);
}
