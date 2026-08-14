<?php

namespace Shetabit\Captcha;

use Exception;
use Illuminate\Contracts\View\View;
use ReflectionClass;
use Shetabit\Captcha\Contracts\DriverInterface;
use Shetabit\Captcha\Exceptions\DriverNotFoundException;
use Shetabit\Captcha\Provider\CaptchaServiceProvider;

class CaptchaManager
{
    /**
     * Captcha Driver Settings.
     *
     * @var array<string, mixed>
     */
    protected array $settings = [];

    /**
     * Captcha Driver Name.
     */
    protected string $driver = '';

    /**
     * Captcha Driver Instance.
     */
    protected DriverInterface|null $driverInstance = null;

    /**
     * CaptchaManager constructor.
     *
     * @param array<string, mixed> $config
     *
     * @throws Exception
     */
    public function __construct(
        protected CaptchaServiceProvider $serviceProvider,
        protected array $config,
    ) {
        $this->via($this->config['default'] ?? '');
    }

    /**
     * Change the driver on the fly.
     *
     * @throws Exception
     */
    public function via(string $driver) : static
    {
        $this->driver = $driver;
        $this->validateDriver();

        $this->settings = $this->config['drivers'][$driver];
        $this->driverInstance = null;

        return $this;
    }

    /**
     * The name of the driver in use.
     */
    public function getDriver() : string
    {
        return $this->driver;
    }

    /**
     * Prepare driver's instance to load requirements if needed.
     *
     * @throws Exception
     */
    public function prepareDriver() : DriverInterface
    {
        return $this->getDriverInstance();
    }

    /**
     * Generate the captcha
     *
     * @throws Exception
     */
    public function generate() : View
    {
        return $this->getDriverInstance()->generate();
    }

    /**
     * Verify CAPTCHA with given token.
     *
     * @throws Exception
     */
    public function verify(string|null $token = null) : bool
    {
        return $this->getDriverInstance()->verify($token);
    }

    /**
     * Retrieve current driver instance or generate new one.
     *
     * @throws Exception
     */
    protected function getDriverInstance() : DriverInterface
    {
        return $this->driverInstance ??= $this->getFreshDriverInstance();
    }

    /**
     * Get new driver instance
     *
     * @throws Exception
     */
    protected function getFreshDriverInstance() : DriverInterface
    {
        $this->validateDriver();

        $class = $this->driverClass();

        return new $class($this->serviceProvider, $this->settings);
    }

    /**
     * Validate driver.
     *
     * @throws Exception
     */
    protected function validateDriver() : void
    {
        if ($this->driver === '') {
            throw new DriverNotFoundException('Driver not selected or default driver does not exist.');
        }

        $class = $this->driverClass();

        if (empty($this->config['drivers'][$this->driver]) || $class === null) {
            throw new DriverNotFoundException('Driver not found in config file. Try updating the package.');
        }

        if (!class_exists($class)) {
            throw new DriverNotFoundException('Driver source not found. Please update the package.');
        }

        if (!new ReflectionClass($class)->implementsInterface(DriverInterface::class)) {
            throw new Exception("Driver must be an instance of Contracts\DriverInterface.");
        }
    }

    /**
     * The class of the driver that is selected.
     *
     * @return class-string|null
     */
    private function driverClass() : string|null
    {
        return $this->config['map'][$this->driver] ?? null;
    }
}
