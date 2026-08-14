<?php

namespace Shetabit\Captcha\Tests\Feature;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Shetabit\Captcha\Tests\TestCase;

/**
 * The flow of the readme, over requests that go through the whole framework:
 * a form shows a captcha, the browser fetches its image, and the answer is
 * validated when the form comes back.
 */
final class CaptchaFlowTest extends TestCase
{
    public function testTheRouteOfTheDriverAnswersWithAPngImage() : void
    {
        $response = $this->get('/captcha');

        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", (string) $response->getContent());
    }

    public function testTheImageComesWithTheTokenTheFormIsCheckedAgainst() : void
    {
        $this->get('/captcha')->assertOk();

        $this->assertNotNull($this->tokenInSession());
    }

    public function testTheImageIsNotCached() : void
    {
        $cacheControl = (string) $this->get('/captcha')->assertOk()->headers->get('cache-control');

        // A cached image would show the same captcha again.
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
    }

    public function testAFormThatCarriesTheRightTokenPasses() : void
    {
        $this->get('/captcha')->assertOk();

        $this->post('/register', ['captcha' => (string) $this->tokenInSession()])
            ->assertOk()
            ->assertSee('registered');
    }

    public function testAFormThatCarriesTheWrongTokenIsRefused() : void
    {
        $this->get('/captcha')->assertOk();

        $this->post('/register', ['captcha' => 'a-wrong-answer'])
            ->assertSessionHasErrors(['captcha' => 'Inserted captcha is not valid.']);
    }

    public function testAFormWithoutACaptchaIsRefused() : void
    {
        $this->post('/register', [])->assertSessionHasErrors('captcha');
    }

    public function testATokenCanOnlyBeUsedOnce() : void
    {
        $this->get('/captcha')->assertOk();

        $token = (string) $this->tokenInSession();

        $this->post('/register', ['captcha' => $token])->assertOk();
        $this->post('/register', ['captcha' => $token])->assertSessionHasErrors('captcha');
    }

    public function testEveryImageComesWithATokenOfItsOwn() : void
    {
        $this->get('/captcha')->assertOk();
        $first = $this->tokenInSession();

        $this->get('/captcha')->assertOk();

        $this->assertNotSame($first, $this->tokenInSession());
    }

    public function testTheViewOfTheDriverShowsTheImageAndTheInput() : void
    {
        $rendered = (string) $this->get('/form')->assertOk()->getContent();

        $this->assertStringContainsString('<img id="captcha" src="http://localhost/captcha"', $rendered);
        $this->assertStringContainsString('name="captcha"', $rendered);
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
