<?php

namespace Shetabit\Captcha;

use Shetabit\Captcha\Contracts\DriverInterface;
use Shetabit\Captcha\Exceptions\DriverNotFoundException;
use Shetabit\Captcha\Provider\CaptchaServiceProvider;

class CaptchaManager
{
    protected $serviceProvider;

    /**
     * Captcha Configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Captcha Driver Settings.
     *
     * @var array
     */
    protected $settings;

    /**
     * Captcha Driver Name.
     *
     * @var string
     */
    protected $driver;

    /**
     * Captcha Driver Instance.
     *
     * @var object
     */
    protected $driverInstance;

    /**
     * CaptchaManager constructor.
     *
     * CaptchaManager constructor.
     * @param CaptchaServiceProvider $serviceProvider
     * @param $config
     * @throws \Exception
     */
    public function __construct(CaptchaServiceProvider $serviceProvider, $config)
    {
        $this->serviceProvider = $serviceProvider;
        $this->config = $config;

        $this->via($this->config['default']);
    }

    /**
     * Set driver.
     *
     * @param $driver
     * @return $this
     * @throws \Exception
     */
    private function via($driver)
    {
        $this->driver = $driver;
        $this->validateDriver();
        $this->settings = $this->config['drivers'][$driver];

        return $this;
    }

    /**
     * Prepare driver's instance to load requirements if needed.
     *
     * @return mixed|object
     * @throws \Exception
     */
    public function prepareDriver()
    {
        $this->driverInstance = $this->getDriverInstance();

        return $this->driverInstance;
    }

    /**
     * Generate the captcha
     *
     * @return mixed
     * @throws \Exception
     */
    public function generate()
    {
        return $this->getDriverInstance()->generate();
    }

    /**
     * Verify CAPTCHA with given token.
     *
     * @param null|$token
     * @return mixed
     * @throws \Exception
     */
    public function verify($token = null)
    {
        return $this->getDriverInstance()->verify($token);
    }

    /**
     * Retrieve current driver instance or generate new one.
     *
     * @return mixed
     * @throws \Exception
     */
    protected function getDriverInstance()
    {
        if (!empty($this->driverInstance)) {
            return $this->driverInstance;
        }

        return $this->getFreshDriverInstance();
    }

    /**
     * Get new driver instance
     *
     * @return mixed
     * @throws \Exception
     */
    protected function getFreshDriverInstance()
    {
        $this->validateDriver();
        $class = $this->config['map'][$this->driver];

        if (!empty($this->callbackUrl)) { // use custom callbackUrl if exists
            $this->settings['callbackUrl'] = $this->callbackUrl;
        }

        return new $class($this->serviceProvider, $this->settings);
    }

    /**
     * Validate driver.
     *
     * @throws \Exception
     */
    protected function validateDriver()
    {
        if (empty($this->driver)) {
            throw new DriverNotFoundException('Driver not selected or default driver does not exist.');
        }

        if (empty($this->config['drivers'][$this->driver]) || empty($this->config['map'][$this->driver])) {
            throw new DriverNotFoundException('Driver not found in config file. Try updating the package.');
        }

        if (!class_exists($this->config['map'][$this->driver])) {
            throw new DriverNotFoundException('Driver source not found. Please update the package.');
        }

        $reflect = new \ReflectionClass($this->config['map'][$this->driver]);

        if (!$reflect->implementsInterface(DriverInterface::class)) {
            throw new \Exception("Driver must be an instance of Contracts\DriverInterface.");
        }
    }
}
