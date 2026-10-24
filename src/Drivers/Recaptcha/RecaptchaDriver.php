<?php

namespace Shetabit\Captcha\Drivers\Recaptcha;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use RuntimeException;
use Shetabit\Captcha\Abstracts\Driver;
use Shetabit\Captcha\Provider\CaptchaServiceProvider;

class RecaptchaDriver extends Driver
{
    /**
     * RecaptchaDriver constructor.
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
        /** @var view-string $view */
        $view = 'captchaRecaptchaDriver::captcha';

        return ViewFactory::make(
            $view,
            [
                'id' => 'captcha-'.Str::random(8),
                'siteKey' => $this->settings->siteKey,
                'action' => $this->settings->action,
                'script' => $this->settings->script,
                'errorsName' => config('captcha.validator'),
            ]
        );
    }

    /**
     * Verify token.
     *
     * Google scores the token from 0.0 (a bot) to 1.0 (a human), and tells the
     * action it was handed out for.
     */
    public function verify(string|null $token = null) : bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $answer = $this->siteverify($token);
        $score = $answer['score'] ?? null;

        return ($answer['success'] ?? false) === true
            && ($answer['action'] ?? null) === $this->settings->action
            && is_numeric($score)
            && (float) $score >= (float) $this->settings->score;
    }

    /**
     * What Google says of the given token.
     *
     * A Google that can not be reached vouches for nothing.
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
     * Bind driver views
     */
    protected function bindViews() : static
    {
        $this->provider()->bindViewFile(__DIR__.'/resources/views', 'captchaRecaptchaDriver');

        return $this;
    }

    /**
     * Publish driver views
     */
    protected function publishResources() : static
    {
        $this->provider()->publish(
            __DIR__.'/resources/views',
            resource_path('views/vendor/captchaRecaptchaDriver'),
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
