<?php

namespace Shetabit\Captcha\Tests\Feature;

use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Http;
use Shetabit\Captcha\Tests\TestCase;

/**
 * The flow of the readme with Google reCAPTCHA v3: the form carries a hidden
 * input that the script of Google fills with a token when the form is sent,
 * and the token is checked with Google when the form comes back.
 */
final class RecaptchaFlowTest extends TestCase
{
    public function testTheFormCarriesAHiddenInputAndTheScriptOfGoogle() : void
    {
        $rendered = (string) $this->get('/form')->assertOk()->getContent();

        $this->assertStringContainsString('<input type="hidden" name="captcha"', $rendered);
        $this->assertStringContainsString(
            '<script src="https://www.google.com/recaptcha/api.js?render=the-site-key"></script>',
            $rendered
        );
        $this->assertStringContainsString(', "the-site-key", "submit");', $rendered);
    }

    public function testAFormGoogleVouchesForPasses() : void
    {
        Http::fake(['*' => Http::response(['success' => true, 'score' => 0.9, 'action' => 'submit'])]);

        $this->post('/register', ['captcha' => 'the-token'])
            ->assertOk()
            ->assertSee('registered');
    }

    public function testAFormGoogleTakesForABotIsRefused() : void
    {
        Http::fake(['*' => Http::response(['success' => true, 'score' => 0.1, 'action' => 'submit'])]);

        $this->post('/register', ['captcha' => 'the-token'])
            ->assertSessionHasErrors(['captcha' => 'Inserted captcha is not valid.']);
    }

    public function testAFormWithoutATokenIsRefused() : void
    {
        Http::fake();

        $this->post('/register', [])->assertSessionHasErrors('captcha');

        Http::assertNothingSent();
    }

    public function testGoogleIsToldTheAddressOfTheVisitor() : void
    {
        Http::fake(['*' => Http::response(['success' => true, 'score' => 0.9, 'action' => 'submit'])]);

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

        $app['config']->set('captcha.default', 'recaptcha');
        $app['config']->set('captcha.drivers.recaptcha.siteKey', 'the-site-key');
        $app['config']->set('captcha.drivers.recaptcha.secretKey', 'the-secret-key');
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
