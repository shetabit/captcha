<?php

namespace Shetabit\Captcha\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Shetabit\Captcha\Facade\Captcha;
use Shetabit\Captcha\Provider\CaptchaServiceProvider;
use Shetabit\Captcha\Tests\Fixtures\FakeDriver;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get the package's service providers.
     *
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app) : array
    {
        return [CaptchaServiceProvider::class];
    }

    /**
     * Get the package's facade aliases.
     *
     * @param  Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app) : array
    {
        return [
            'Captcha' => Captcha::class,
        ];
    }

    /**
     * Define the environment the tests run in.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app) : void
    {
        // The `web` middleware group encrypts its cookies, which needs a key.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('captcha.drivers.fake', ['sessionKey' => 'captcha']);
        $app['config']->set('captcha.map.fake', FakeDriver::class);
    }

    /**
     * The token of the captcha that was handed out last.
     */
    protected function tokenInSession() : string|null
    {
        $token = session()->get((string) config('captcha.drivers.simple.sessionKey'));

        return is_string($token) ? $token : null;
    }
}
