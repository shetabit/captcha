<?php

namespace Shetabit\Captcha\Provider;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Shetabit\Captcha\CaptchaManager;
use Shetabit\Captcha\Facade\Captcha;

class CaptchaServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot() : void
    {
        /**
         * Configurations that needs to be done by user.
         */
        $this->publish(
            $this->packagePath('config/captcha.php'),
            config_path('captcha.php'),
            ['config', 'captcha-config']
        );

        /**
         * The driver brings the views and the routes it needs with it.
         */
        $this->app->make(Captcha::SERVICE_NAME)->prepareDriver();

        $this->registerValidationRule();
    }

    /**
     * Register any package services.
     */
    public function register() : void
    {
        // Load default configurations
        $this->mergeConfigFrom($this->packagePath('config/captcha.php'), 'captcha');

        // Bind captcha manager
        $this->app->singleton(
            Captcha::SERVICE_NAME,
            fn (): CaptchaManager => new CaptchaManager($this, (array) config('captcha', []))
        );

        $this->app->alias(Captcha::SERVICE_NAME, CaptchaManager::class);
    }

    /**
     * View binder
     */
    public function bindViewFile(string $from, string $namespace) : static
    {
        $this->loadViewsFrom($from, $namespace);

        return $this;
    }

    /**
     * Route binder
     */
    public function bindRouteFile(string $route) : static
    {
        $this->loadRoutesFrom($route);

        return $this;
    }

    /**
     * Publisher
     *
     * @param array<int, string>|string|null $group
     */
    public function publish(string $from, string $to, array|string|null $group = null) : static
    {
        $this->publishes([$from => $to], $group);

        return $this;
    }

    /**
     * Add the validation rule that verifies a captcha.
     */
    protected function registerValidationRule() : void
    {
        Validator::extend(
            (string) config('captcha.validator', 'captcha'),
            static fn (string $attribute, mixed $value): bool => captcha_verify(is_string($value) ? $value : null),
            'Inserted :attribute is not valid.'
        );
    }

    /**
     * The absolute path of the given file of the package.
     */
    private function packagePath(string $path) : string
    {
        return dirname(__DIR__, 2).'/'.$path;
    }
}
