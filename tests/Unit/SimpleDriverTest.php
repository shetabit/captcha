<?php

namespace Shetabit\Captcha\Tests\Unit;

use Illuminate\Contracts\View\View;
use ReflectionMethod;
use Shetabit\Captcha\Drivers\Simple\SimpleDriver;
use Shetabit\Captcha\Facade\Captcha;
use Shetabit\Captcha\Tests\TestCase;

final class SimpleDriverTest extends TestCase
{
    public function testItDrawsAPngAndRemembersItsToken() : void
    {
        $this->startSession();

        $image = $this->driver()->prepareCaptchaImage();

        $this->assertStringStartsWith("\x89PNG", $image);
        $this->assertNotNull($this->tokenInSession());
    }

    public function testTheTokenOfTheImageIsBuiltOfTheConfiguredCharacters() : void
    {
        $this->startSession();

        config()->set('captcha.drivers.simple.characters', 'AB');
        config()->set('captcha.drivers.simple.length', [5, 5]);

        $this->freshDriver()->prepareCaptchaImage();

        $this->assertMatchesRegularExpression('/^[AB]{5}$/', (string) $this->tokenInSession());
    }

    public function testEveryCharacterOfThePoolCanBeDrawn() : void
    {
        // The random string used to start at index 1, so the first character of
        // the pool was never part of a captcha.
        $method = new ReflectionMethod(SimpleDriver::class, 'randomString');

        $drawn = [];

        for ($try = 0; $try < 50; $try++) {
            $drawn[] = $method->invoke($this->driver(), 'AB', 4, 4);
        }

        $this->assertStringContainsString('A', implode('', $drawn));
        $this->assertStringContainsString('B', implode('', $drawn));
    }

    public function testItVerifiesTheTokenItHandedOut() : void
    {
        $this->startSession();

        $driver = $this->driver();
        $driver->prepareCaptchaImage();

        $this->assertTrue($driver->verify($this->tokenInSession()));
    }

    public function testATokenIsGoodForOneTryOnly() : void
    {
        $this->startSession();

        $driver = $this->driver();
        $driver->prepareCaptchaImage();

        $token = (string) $this->tokenInSession();

        $this->assertTrue($driver->verify($token));
        $this->assertFalse($driver->verify($token));
    }

    public function testItRefusesAWrongToken() : void
    {
        $this->startSession();

        $driver = $this->driver();
        $driver->prepareCaptchaImage();

        $this->assertFalse($driver->verify('a-token-that-was-never-handed-out'));
    }

    public function testItRefusesEveryTokenWhileNoCaptchaWasHandedOut() : void
    {
        $this->startSession();

        // Without a stored token, `null == null` and `'' == null` used to pass
        // the comparison — a form could be sent without solving a captcha.
        $this->assertFalse($this->driver()->verify());
        $this->assertFalse($this->driver()->verify(''));
        $this->assertFalse($this->driver()->verify('anything'));
    }

    public function testItIgnoresTheCaseUnlessItIsConfiguredToCare() : void
    {
        $this->startSession();

        config()->set('captcha.drivers.simple.characters', 'AB');

        $driver = $this->freshDriver();
        $driver->prepareCaptchaImage();
        $token = (string) $this->tokenInSession();

        $this->assertTrue($driver->verify(mb_strtolower($token)));

        config()->set('captcha.drivers.simple.sensitive', true);

        $sensitive = $this->freshDriver();
        $sensitive->prepareCaptchaImage();
        $token = (string) $this->tokenInSession();

        $this->assertFalse($sensitive->verify(mb_strtolower($token)));
    }

    public function testItFallsBackToTheFontOfThePackage() : void
    {
        // The configured path points at the published font, which is only there
        // once `artisan vendor:publish` copied it.
        config()->set('captcha.drivers.simple.fontFamily', '/a/font/that/is/not/there.ttf');

        $font = $this->freshDriver()->fontFamily();

        $this->assertFileExists($font);
        $this->assertStringEndsWith('/resources/assets/fonts/DroidSerif.ttf', $font);
    }

    public function testItRendersTheViewOfTheDriver() : void
    {
        $this->assertInstanceOf(View::class, $this->driver()->generate());
    }

    private function driver() : SimpleDriver
    {
        $driver = $this->app->make(Captcha::SERVICE_NAME)->prepareDriver();

        $this->assertInstanceOf(SimpleDriver::class, $driver);

        return $driver;
    }

    /**
     * A driver that is built with the configuration of the moment.
     */
    private function freshDriver() : SimpleDriver
    {
        $this->app->forgetInstance(Captcha::SERVICE_NAME);

        return $this->driver();
    }
}
