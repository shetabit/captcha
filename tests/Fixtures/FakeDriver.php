<?php

namespace Shetabit\Captcha\Tests\Fixtures;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\Support\ServiceProvider;
use Shetabit\Captcha\Abstracts\Driver;

/**
 * A driver that answers with a fixed token, and counts how often it was built.
 */
class FakeDriver extends Driver
{
    public const string TOKEN = 'a-fake-token';

    public static int $instances = 0;

    /**
     * @param array<string, mixed>|object $settings
     */
    public function __construct(protected ServiceProvider $serviceProvider, mixed $settings)
    {
        $this->settings = (object) (array) $settings;

        self::$instances++;
    }

    public function generate() : View
    {
        return ViewFactory::make('captchaSimpleDriver::captcha', [
            'routeName' => 'captcha',
            'errorsName' => 'captcha',
        ]);
    }

    public function verify(string|null $token = null) : bool
    {
        return $token === self::TOKEN;
    }
}
