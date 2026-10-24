<?php

namespace Shetabit\Captcha\Tests\Unit;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Shetabit\Captcha\Drivers\Turnstile\TurnstileDriver;
use Shetabit\Captcha\Facade\Captcha;
use Shetabit\Captcha\Tests\TestCase;

final class TurnstileDriverTest extends TestCase
{
    public function testItAsksCloudflareAboutTheToken() : void
    {
        Http::fake(['*' => Http::response(['success' => true])]);

        $this->assertTrue($this->driver()->verify('the-token'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request->isForm()
            && $request['secret'] === 'the-secret-key'
            && $request['response'] === 'the-token');
    }

    public function testATokenCloudflareDoesNotVouchForIsRefused() : void
    {
        Http::fake(['*' => Http::response(['success' => false, 'error-codes' => ['timeout-or-duplicate']])]);

        $this->assertFalse($this->driver()->verify('the-token'));
    }

    public function testATokenOfAnyActionPassesWhileNoneIsConfigured() : void
    {
        Http::fake(['*' => Http::response(['success' => true, 'action' => 'login'])]);

        $this->assertTrue($this->driver()->verify('the-token'));
    }

    public function testATokenOfTheConfiguredActionPasses() : void
    {
        config()->set('captcha.drivers.turnstile.action', 'login');
        Http::fake(['*' => Http::response(['success' => true, 'action' => 'login'])]);

        $this->assertTrue($this->freshDriver()->verify('the-token'));
    }

    public function testATokenOfAnotherActionIsRefusedOnceOneIsConfigured() : void
    {
        config()->set('captcha.drivers.turnstile.action', 'login');
        Http::fake(['*' => Http::response(['success' => true, 'action' => 'signup'])]);

        $this->assertFalse($this->freshDriver()->verify('the-token'));
    }

    public function testAnEmptyTokenIsRefusedWithoutAskingCloudflare() : void
    {
        Http::fake();

        $this->assertFalse($this->driver()->verify());
        $this->assertFalse($this->driver()->verify(''));

        Http::assertNothingSent();
    }

    public function testTheTokenIsRefusedWhileCloudflareCanNotBeReached() : void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $this->assertFalse($this->driver()->verify('the-token'));
    }

    public function testAnAnswerThatIsNotJsonIsRefused() : void
    {
        Http::fake(['*' => Http::response('Bad Gateway', 502)]);

        $this->assertFalse($this->driver()->verify('the-token'));
    }

    public function testItAsksTheConfiguredUrl() : void
    {
        config()->set('captcha.drivers.turnstile.url', 'https://turnstile.example.com/siteverify');
        Http::fake(['*' => Http::response(['success' => true])]);

        $this->freshDriver()->verify('the-token');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://turnstile.example.com/siteverify');
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

        $app['config']->set('captcha.default', 'turnstile');
        $app['config']->set('captcha.drivers.turnstile.siteKey', 'the-site-key');
        $app['config']->set('captcha.drivers.turnstile.secretKey', 'the-secret-key');
    }

    private function driver() : TurnstileDriver
    {
        $driver = $this->app->make(Captcha::SERVICE_NAME)->prepareDriver();

        $this->assertInstanceOf(TurnstileDriver::class, $driver);

        return $driver;
    }

    /**
     * A driver that is built with the configuration of the moment.
     */
    private function freshDriver() : TurnstileDriver
    {
        $this->app->forgetInstance(Captcha::SERVICE_NAME);

        return $this->driver();
    }
}
