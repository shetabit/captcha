<?php

namespace Shetabit\Captcha\Drivers\Turnstile;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Shetabit\Captcha\Abstracts\Driver;
use Shetabit\Captcha\Provider\CaptchaServiceProvider;

class TurnstileDriver extends Driver
{
    /**
     * TurnstileDriver constructor.
     * Construct the class with the relevant settings.
     *
     * @param array<string, mixed>|object $settings
     */
    public function __construct(protected ServiceProvider $serviceProvider, mixed $settings)
    {
        $this->settings = (object) (array) $settings;

        $this->bindViews()
             ->publishResources();
    }

    /**
     * Generate captcha.
     */
    public function generate() : View
    {
        return ViewFactory::make(
            'captchaTurnstileDriver::captcha',
            [
                'siteKey' => $this->settings->siteKey,
                'theme' => $this->settings->theme,
                'size' => $this->settings->size,
                'action' => $this->action(),
                'script' => $this->settings->script,
                'errorsName' => config('captcha.validator'),
            ]
        );
    }

    /**
     * Verify token.
     *
     * When an action is configured, a token made for another one is refused.
     */
    public function verify(string|null $token = null) : bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $answer = $this->siteverify($token);

        return ($answer['success'] ?? false) === true
            && ($this->action() === '' || ($answer['action'] ?? null) === $this->action());
    }

    /**
     * What Cloudflare says of the given token.
     *
     * A Cloudflare that can not be reached vouches for nothing.
     *
     * @return array<string, mixed>
     */
    protected function siteverify(string $token) : array
    {
        try {
            $response = Http::asForm()
                ->timeout((int) $this->settings->timeout)
                ->post($this->settings->url, [
                    'secret' => $this->settings->secretKey,
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);
        } catch (ConnectionException) {
            return [];
        }

        $answer = $response->json();

        return is_array($answer) ? $answer : [];
    }

    /**
     * The action the widget is configured for, empty when it is not.
     */
    protected function action() : string
    {
        return (string) ($this->settings->action ?? '');
    }

    /**
     * Bind driver views
     */
    protected function bindViews() : static
    {
        $this->provider()->bindViewFile(__DIR__.'/resources/views', 'captchaTurnstileDriver');

        return $this;
    }

    /**
     * Publish driver views
     */
    protected function publishResources() : static
    {
        $this->provider()->publish(
            __DIR__.'/resources/views',
            resource_path('views/vendor/captchaTurnstileDriver'),
            ['views', 'captcha-views']
        );

        return $this;
    }

    /**
     * The provider the driver registers its views with.
     */
    private function provider() : CaptchaServiceProvider
    {
        if (!$this->serviceProvider instanceof CaptchaServiceProvider) {
            throw new RuntimeException(CaptchaServiceProvider::class.' is what this driver registers itself with.');
        }

        return $this->serviceProvider;
    }
}
