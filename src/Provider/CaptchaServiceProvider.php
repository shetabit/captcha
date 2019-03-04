<?php

namespace Shetabit\Captcha\Provider;

use Shetabit\Captcha\CaptchaManager;
use Illuminate\Support\ServiceProvider;

class CaptchaServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * Configurations that needs to be done by user.
         */
        $this->publish(
            __DIR__ . '/../../config/captcha.php',
            config_path('captcha.php'),
            'config'
        );

        // Validator extensions
        $this->app['validator']->extend(
            config('captcha.validator','captcha'),
            function($attribute, $value, $parameters) {
                return captcha_verify($value);
            },
            'Inserted :attribute is not valid.'
        );
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        // Bind captcha manager
        $this->app->bind('shetabit-captcha', function () {
            return new CaptchaManager($this, config('captcha'));
        });

        $this->prepare();
    }

    /**
     *  Prepare requirements
     */
    private function prepare()
    {
        app('shetabit-captcha')->prepareDriver();
    }

    /**
     * View binder
     *
     * @param $from
     * @param $namespace
     * @return $this
     */
    public function bindViewFile($from, $namespace)
    {
        $this->loadViewsFrom($from, $namespace);

        return $this;
    }

    /**
     * Route binder
     *
     * @param $route
     * @return $this
     */
    public function bindRouteFile($route)
    {
        $this->loadRoutesFrom($route);

        return $this;
    }

    /**
     * Publisher
     *
     * @param $from
     * @param $to
     * @param null $group
     * @return $this
     */
    public function publish($from, $to, $group = null)
    {
        $this->publishes([$from => $to], $group);

        return $this;
    }
}
