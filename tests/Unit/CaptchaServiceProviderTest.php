<?php

namespace Shetabit\Captcha\Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Shetabit\Captcha\CaptchaManager;
use Shetabit\Captcha\Facade\Captcha;
use Shetabit\Captcha\Provider\CaptchaServiceProvider;
use Shetabit\Captcha\Tests\TestCase;

final class CaptchaServiceProviderTest extends TestCase
{
    public function testItBindsTheManagerToTheContainer() : void
    {
        $this->assertInstanceOf(CaptchaManager::class, $this->app->make(Captcha::SERVICE_NAME));
    }

    public function testTheManagerIsShared() : void
    {
        // The driver registers views and routes while it is being built, so the
        // manager that holds it is built once.
        $this->assertSame(
            $this->app->make(Captcha::SERVICE_NAME),
            $this->app->make(Captcha::SERVICE_NAME)
        );
    }

    public function testTheManagerCanBeResolvedByItsClassNameAndThroughTheFacadeAndTheHelper() : void
    {
        $manager = $this->app->make(Captcha::SERVICE_NAME);

        $this->assertSame($manager, $this->app->make(CaptchaManager::class));
        $this->assertSame($manager, Captcha::getFacadeRoot());
        $this->assertSame($manager, captcha_manager());
        $this->assertSame(Captcha::SERVICE_NAME, Captcha::getFacadeAccessor());
    }

    public function testItMergesTheConfigurationOfThePackage() : void
    {
        // Nothing was published, so every one of these comes from the file that
        // ships with the package.
        $this->assertSame('simple', config('captcha.default'));
        $this->assertSame('captcha', config('captcha.validator'));
        $this->assertSame('captcha', config('captcha.drivers.simple.sessionKey'));
        $this->assertArrayHasKey('simple', config('captcha.map'));
    }

    public function testItPublishesTheConfigurationFile() : void
    {
        $expected = [dirname(__DIR__, 2).'/config/captcha.php' => config_path('captcha.php')];

        $this->assertSame($expected, ServiceProvider::pathsToPublish(CaptchaServiceProvider::class, 'config'));
        $this->assertSame($expected, ServiceProvider::pathsToPublish(CaptchaServiceProvider::class, 'captcha-config'));
    }

    public function testTheDriverPublishesItsViewsAndAssets() : void
    {
        $views = ServiceProvider::pathsToPublish(CaptchaServiceProvider::class, 'captcha-views');
        $assets = ServiceProvider::pathsToPublish(CaptchaServiceProvider::class, 'captcha-assets');

        $resources = dirname(__DIR__, 2).'/src/Drivers/Simple/resources';
        $published = resource_path('views/vendor/captchaSimpleDriver');

        $this->assertSame([$resources.'/views' => $published], $views);
        $this->assertSame([$resources.'/assets' => $published.'/assets'], $assets);
    }

    public function testItRegistersTheViewsAndTheRouteOfTheDriver() : void
    {
        // The namespace is registered by the driver while the package boots,
        // which static analysis can not see.
        $exists = view()->exists('captchaSimpleDriver::captcha'); // @phpstan-ignore method.impossibleType

        $this->assertTrue($exists);
        $this->assertNotNull($this->app->make('router')->getRoutes()->getByName('captcha'));
    }

    public function testItAddsTheValidationRule() : void
    {
        $this->startSession();

        $this->app->make(Captcha::SERVICE_NAME)->prepareDriver();

        $validator = Validator::make(['captcha' => 'anything'], ['captcha' => 'captcha']);

        $this->assertTrue($validator->fails());
        $this->assertSame('Inserted captcha is not valid.', $validator->errors()->first('captcha'));
    }
}
