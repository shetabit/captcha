<?php

namespace Shetabit\Captcha\Tests\Unit;

use Exception;
use Illuminate\Contracts\View\View;
use Shetabit\Captcha\CaptchaManager;
use Shetabit\Captcha\Drivers\Simple\SimpleDriver;
use Shetabit\Captcha\Exceptions\DriverNotFoundException;
use Shetabit\Captcha\Facade\Captcha;
use Shetabit\Captcha\Tests\Fixtures\FakeDriver;
use Shetabit\Captcha\Tests\Fixtures\NotADriver;
use Shetabit\Captcha\Tests\TestCase;

final class CaptchaManagerTest extends TestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        FakeDriver::$instances = 0;
    }

    public function testItStartsOutWithTheDriverOfTheConfiguration() : void
    {
        $this->assertSame('simple', $this->manager()->getDriver());
        $this->assertInstanceOf(SimpleDriver::class, $this->manager()->prepareDriver());
    }

    public function testTheDriverCanBeChangedOnTheFly() : void
    {
        $manager = $this->manager()->via('fake');

        $this->assertSame('fake', $manager->getDriver());
        $this->assertInstanceOf(FakeDriver::class, $manager->prepareDriver());
    }

    public function testItBuildsTheDriverOnceAndThenKeepsIt() : void
    {
        $manager = $this->manager()->via('fake');

        $manager->prepareDriver();
        $manager->generate();
        $manager->verify('a-token');

        // The driver of this package registers views and routes while it is
        // being built, so it must not be built again for every call.
        $this->assertSame(1, FakeDriver::$instances);
    }

    public function testSwitchingTheDriverThrowsTheOldOneAway() : void
    {
        $manager = $this->manager()->via('fake');

        $manager->prepareDriver();
        $manager->via('simple');

        $this->assertInstanceOf(SimpleDriver::class, $manager->prepareDriver());
    }

    public function testItHandsTheWorkToItsDriver() : void
    {
        $manager = $this->manager()->via('fake');

        $this->assertInstanceOf(View::class, $manager->generate());
        $this->assertTrue($manager->verify(FakeDriver::TOKEN));
        $this->assertFalse($manager->verify('something-else'));
    }

    public function testItRefusesADriverWithoutAName() : void
    {
        $this->expectException(DriverNotFoundException::class);
        $this->expectExceptionMessage('Driver not selected or default driver does not exist.');

        $this->manager()->via('');
    }

    public function testItRefusesADriverThatIsNotConfigured() : void
    {
        $this->expectException(DriverNotFoundException::class);
        $this->expectExceptionMessage('Driver not found in config file. Try updating the package.');

        $this->manager()->via('a-driver-of-nothing');
    }

    public function testItRefusesADriverWhoseClassIsMissing() : void
    {
        config()->set('captcha.drivers.ghost', ['sessionKey' => 'captcha']);
        config()->set('captcha.map.ghost', 'A\Class\That\Does\Not\Exist');

        $this->expectException(DriverNotFoundException::class);
        $this->expectExceptionMessage('Driver source not found. Please update the package.');

        $this->manager()->via('ghost');
    }

    public function testItRefusesADriverThatDoesNotImplementTheContract() : void
    {
        config()->set('captcha.drivers.invalid', ['sessionKey' => 'captcha']);
        config()->set('captcha.map.invalid', NotADriver::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Driver must be an instance of Contracts\DriverInterface.');

        $this->manager()->via('invalid');
    }

    /**
     * The manager is built with the configuration of the moment, so a test that
     * changes the configuration asks for a new one.
     */
    private function manager() : CaptchaManager
    {
        $this->app->forgetInstance(Captcha::SERVICE_NAME);

        return $this->app->make(Captcha::SERVICE_NAME);
    }
}
