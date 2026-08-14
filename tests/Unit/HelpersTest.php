<?php

namespace Shetabit\Captcha\Tests\Unit;

use Illuminate\Contracts\View\View;
use Shetabit\Captcha\CaptchaManager;
use Shetabit\Captcha\Facade\Captcha;
use Shetabit\Captcha\Tests\TestCase;

final class HelpersTest extends TestCase
{
    public function testTheManagerHelperAnswersWithTheManager() : void
    {
        $this->assertInstanceOf(CaptchaManager::class, captcha_manager());
    }

    public function testTheCaptchaHelpersRenderTheView() : void
    {
        $this->assertInstanceOf(View::class, captcha());
        $this->assertInstanceOf(View::class, captcha_refresh());
    }

    public function testTheVerifyHelperVerifiesTheTokenOfTheCaptcha() : void
    {
        $this->startSession();

        $this->app->make(Captcha::SERVICE_NAME)->via('fake');

        $this->assertTrue(captcha_verify(\Shetabit\Captcha\Tests\Fixtures\FakeDriver::TOKEN));
        $this->assertFalse(captcha_verify('something-else'));
        $this->assertFalse(captcha_verify());
    }
}
