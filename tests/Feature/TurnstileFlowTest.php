<?php

namespace Shetabit\Captcha\Tests\Feature;

use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Http;
use Shetabit\Captcha\Facade\Captcha;
use Shetabit\Captcha\Tests\TestCase;

/**
 * The flow of the readme with Cloudflare Turnstile: the form carries the widget,
 * which puts its token in a `captcha` input, and the token is checked with
 * Cloudflare when the form comes back.
 */
final class TurnstileFlowTest extends TestCase
{
    public function testTheFormCarriesTheWidgetAndTheScriptOfCloudflare() : void
    {
        $rendered = (string) $this->get('/form')->assertOk()->getContent();

        $this->assertStringContainsString('class="cf-turnstile"', $rendered);
        $this->assertStringContainsString('data-sitekey="the-site-key"', $rendered);
        $this->assertStringContainsString('data-response-field-name="captcha"', $rendered);
        $this->assertStringContainsString('data-theme="auto"', $rendered);
        $this->assertStringContainsString('data-size="normal"', $rendered);
        $this->assertStringNotContainsString('data-action', $rendered);
        $this->assertStringContainsString(
            '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>',
            $rendered
        );
    }

    public function testTheWidgetCarriesTheConfiguredAction() : void
    {
        config()->set('captcha.drivers.turnstile.action', 'login');
        $this->app->forgetInstance(Captcha::SERVICE_NAME);

        $this->get('/form')->assertOk()->assertSee('data-action="login"', false);
    }

    public function testAFormCloudflareVouchesForPasses() : void
    {
        Http::fake(['*' => Http::response(['success' => true])]);

        $this->post('/register', ['captcha' => 'the-token'])
            ->assertOk()
            ->assertSee('registered');
    }

    public function testAFormCloudflareDoesNotVouchForIsRefused() : void
    {
        Http::fake(['*' => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);

        $this->post('/register', ['captcha' => 'the-token'])
            ->assertSessionHasErrors(['captcha' => 'Inserted captcha is not valid.']);
    }

    public function testAFormWithoutATokenIsRefused() : void
    {
        Http::fake();

        $this->post('/register', [])->assertSessionHasErrors('captcha');

        Http::assertNothingSent();
    }

    public function testCloudflareIsToldTheAddressOfTheVisitor() : void
    {
        Http::fake(['*' => Http::response(['success' => true])]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->post('/register', ['captcha' => 'the-token'])
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => $request['remoteip'] === '203.0.113.7');
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

    /**
     * @param  Router  $router
     */
    protected function defineRoutes($router) : void
    {
        $router->middleware('web')->group(function (Router $router): void {
            $router->get('/form', fn (): string => captcha()->render());

            $router->post('/register', function (): string {
                request()->validate(['captcha' => 'required|captcha']);

                return 'registered';
            });
        });
    }
}
