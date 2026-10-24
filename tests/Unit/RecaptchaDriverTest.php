<?php

namespace Shetabit\Captcha\Tests\Unit;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Shetabit\Captcha\Drivers\Recaptcha\RecaptchaDriver;
use Shetabit\Captcha\Facade\Captcha;
use Shetabit\Captcha\Tests\TestCase;

final class RecaptchaDriverTest extends TestCase
{
    public function testItAsksGoogleAboutTheToken() : void
    {
        Http::fake(['*' => $this->answer()]);

        $this->assertTrue($this->driver()->verify('the-token'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
            && $request->isForm()
            && $request['secret'] === 'the-secret-key'
            && $request['response'] === 'the-token');
    }

    public function testAScoreAsHighAsTheConfiguredOnePasses() : void
    {
        Http::fake(['*' => $this->answer(['score' => 0.5])]);

        $this->assertTrue($this->driver()->verify('the-token'));
    }

    public function testAScoreBelowTheConfiguredOneIsRefused() : void
    {
        Http::fake(['*' => $this->answer(['score' => 0.4])]);

        $this->assertFalse($this->driver()->verify('the-token'));
    }

    public function testTheLowestScoreThatPassesIsConfigurable() : void
    {
        config()->set('captcha.drivers.recaptcha.score', 0.9);
        Http::fake(['*' => $this->answer(['score' => 0.8])]);

        $this->assertFalse($this->freshDriver()->verify('the-token'));
    }

    public function testATokenOfAnotherActionIsRefused() : void
    {
        Http::fake(['*' => $this->answer(['action' => 'login'])]);

        $this->assertFalse($this->driver()->verify('the-token'));
    }

    public function testATokenGoogleDoesNotVouchForIsRefused() : void
    {
        Http::fake(['*' => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);

        $this->assertFalse($this->driver()->verify('the-token'));
    }

    public function testAnAnswerWithoutAScoreIsRefused() : void
    {
        Http::fake(['*' => Http::response(['success' => true, 'action' => 'submit'])]);

        $this->assertFalse($this->driver()->verify('the-token'));
    }

    public function testAnEmptyTokenIsRefusedWithoutAskingGoogle() : void
    {
        Http::fake();

        $this->assertFalse($this->driver()->verify());
        $this->assertFalse($this->driver()->verify(''));

        Http::assertNothingSent();
    }

    public function testTheTokenIsRefusedWhileGoogleCanNotBeReached() : void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $this->assertFalse($this->driver()->verify('the-token'));
    }

    public function testAnAnswerThatIsNotJsonIsRefused() : void
    {
        Http::fake(['*' => Http::response('Service Unavailable', 503)]);

        $this->assertFalse($this->driver()->verify('the-token'));
    }

    public function testItAsksTheConfiguredUrl() : void
    {
        config()->set('captcha.drivers.recaptcha.url', 'https://www.recaptcha.net/recaptcha/api/siteverify');
        Http::fake(['*' => $this->answer()]);

        $this->freshDriver()->verify('the-token');

        Http::assertSent(
            fn (Request $request): bool => $request->url() === 'https://www.recaptcha.net/recaptcha/api/siteverify'
        );
    }

    public function testItRendersTheViewOfTheDriver() : void
    {
        $this->assertInstanceOf(View::class, $this->driver()->generate());
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app) : void
    {
        parent::defineEnvironment($app);

        $app['config']->set('captcha.default', 'recaptcha');
        $app['config']->set('captcha.drivers.recaptcha.siteKey', 'the-site-key');
        $app['config']->set('captcha.drivers.recaptcha.secretKey', 'the-secret-key');
    }

    /**
     * What Google says of a token it vouches for, with the given changes.
     *
     * @param array<string, mixed> $changes
     */
    private function answer(array $changes = []) : PromiseInterface
    {
        return Http::response(array_merge(
            ['success' => true, 'score' => 0.9, 'action' => 'submit', 'hostname' => 'localhost'],
            $changes
        ));
    }

    private function driver() : RecaptchaDriver
    {
        $driver = $this->app->make(Captcha::SERVICE_NAME)->prepareDriver();

        $this->assertInstanceOf(RecaptchaDriver::class, $driver);

        return $driver;
    }

    /**
     * A driver that is built with the configuration of the moment.
     */
    private function freshDriver() : RecaptchaDriver
    {
        $this->app->forgetInstance(Captcha::SERVICE_NAME);

        return $this->driver();
    }
}
