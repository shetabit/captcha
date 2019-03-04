<?php

namespace Shetabit\Captcha\Drivers\Simple;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Shetabit\Captcha\Abstracts\Driver;

class SimpleDriver extends Driver
{
    protected $serviceProvider;

    /**
     * Driver settings.
     *
     * @var object
     */
    protected $settings;

    /**
     * SimpleDriver constructor.
     * Construct the class with the relevant settings.
     *
     * SimpleDriver constructor.
     * @param ServiceProvider $serviceProvider
     * @param $settings
     */
    public function __construct(ServiceProvider $serviceProvider, $settings)
    {
        $this->serviceProvider = $serviceProvider;
        $this->settings = (object) $settings;

        $this->bindViews()
             ->bindRoutes()
             ->publishResources();
    }

    /**
     * Bind driver views
     *
     * @return $this
     */
    protected function bindViews()
    {
        $this->serviceProvider->bindViewFile(__DIR__ . '/resources/views', 'captchaSimpleDriver');

        return $this;
    }

    /**
     * Bind driver routes
     *
     * @return $this
     */
    protected function bindRoutes()
    {
        $this->serviceProvider->bindRouteFile(__DIR__ . '/routes.php');

        return $this;
    }

    /**
     * Publish driver assets
     *
     * @return $this
     */
    protected function publishResources()
    {
        $destinationPath = resource_path('views/vendor/captchaSimpleDriver');

        $this->serviceProvider
             ->publish(__DIR__ . '/resources/views', $destinationPath, 'views')
             ->publish(__DIR__ . '/resources/assets', $destinationPath.'/assets', 'assets');

        return $this;
    }

    /**
     * Prepare CAPTCHA image and memorize its token.
     *
     * @return false|string
     */
    public function prepareCaptchaImage()
    {
        $settings = $this->settings;

        $token = $this->randomString(
            $settings->characters,
            $settings->length[0],
            $settings->length[1]
        );

        // save token in memory
        $this->pushInMemory($settings->sessionKey, $token);

        $image = $this->drawImage(
            $token,
            $settings->width,
            $settings->height,
            $settings->foregroundColors,
            $settings->backgroundColor,
            $settings->letterSpacing,
            $settings->fontSize,
            $settings->fontFamily
        );

        return $image;
    }

    /**
     * Generate captcha.
     *
     * @return mixed
     */
    public function generate()
    {
        return View::make(
            'captchaSimpleDriver::captcha',
            [
                'routeName' => $this->settings->route,
                'errorsName' => config('captcha.validator'),
            ]
        );
    }

    /**
     * Verify token.
     *
     * @param null|$token
     * @return bool
     */
    public function verify($token = null)
    {
        $storedToken = $this->pullFromMemory($this->settings->sessionKey);

        if (empty($this->settings->sensitive)) {
            $storedToken = mb_strtolower($storedToken);
            $token = mb_strtolower($token);
        }

        return $token == $storedToken;
    }

    /**
     * Save token in memory.
     *
     * @param $key
     * @param $value
     * @return $this
     */
    protected function pushInMemory($key, $value)
    {
        session()->put($key, $value);

        return $this;
    }

    /**
     * Retrieve token from memory.
     *
     * @return mixed
     */
    protected function pullFromMemory($key)
    {
        $value = session()->pull($key);

        if (! empty($value)) {
            session()->forget($key);
        }

        return $value;
    }

    /**
     * Create new canvas.
     *
     * @param $width
     * @param $height
     * @return resource
     */
    protected function canvas($width, $height)
    {
        $canvas = imagecreatetruecolor($width, $height);

        return $canvas;
    }

    /**
     * Generate image
     *
     * @param $token
     * @param $width
     * @param $height
     * @param $foregroundColors
     * @param $backgroundColor
     * @param $letterSpacing
     * @param $fontSize
     * @param $fontFamily
     * @return false|string
     */
    protected function drawImage(
        $token,
        $width,
        $height,
        $foregroundColors,
        $backgroundColor,
        $letterSpacing,
        $fontSize,
        $fontFamily
    ) {
        $canvas = $this->canvas($width, $height);

        $this->fillWithColor($canvas, $backgroundColor);

        $offsetX = ($width - strlen($token) * ($letterSpacing + $fontSize * 0.66)) / 2;
        $offsetY = ceil(($height) / 1.5);

        // write token
        for ($i = 0; $i < strlen($token); $i++) {
            $randomForegroundColor = $foregroundColors[mt_rand(0, count($foregroundColors) - 1)];
            imagettftext(
                $canvas,
                $this->settings->fontSize,
                ceil(mt_rand(0,10)),
                $offsetX,
                $offsetY,
                $this->prepareColor($canvas, $randomForegroundColor),
                $fontFamily,
                $token[$i]
            );
            $offsetX += ceil($fontSize * 0.66) + $letterSpacing;
        }

        //Scratches foreground
        for ($i = 0; $i < $this->settings->scratches[0]; $i++) {
            $randomForegroundColor = $foregroundColors[mt_rand(0, count($foregroundColors) - 1)];

            $this->drawScratch($canvas, $width, $height, $randomForegroundColor);
        }

        //Scratches background
        for ($i = 0; $i < $this->settings->scratches[1]; $i++) {
            $this->drawScratch($canvas, $width, $height, $backgroundColor);
        }

        ob_start();
        imagepng($canvas);
        $content = ob_get_contents();
        ob_end_clean();

        imagedestroy($canvas);

        return $content;
    }

    /**
     * Fill canvas with the given color
     *
     * @param $canvas
     * @param $color
     * @return $this
     */
    protected function fillWithColor($canvas, $color)
    {
        $fillColor = $this->prepareColor($canvas, $color);

        imagefill($canvas, 0, 0, $fillColor);

        return $this;
    }

    /**
     * Draw scratches
     *
     * @param $img
     * @param $imageWidth
     * @param $imageHeight
     * @param $hex
     */
    private function drawScratch($img, $imageWidth, $imageHeight, $hex)
    {
        $rgb = $this->hexToRgb($hex);

        imageline(
            $img,
            mt_rand(0, floor($imageWidth / 2)),
            mt_rand(1, $imageHeight),
            mt_rand(floor($imageWidth / 2), $imageWidth),
            mt_rand(1, $imageHeight),
            imagecolorallocate($img, $rgb['red'], $rgb['green'], $rgb['blue'])
        );
    }

    /**
     * prepare a color
     *
     * @param $canvas
     * @param $color
     * @return int
     */
    private function prepareColor($canvas, $hexColor)
    {
        $rgbColor = $this->hexToRGB($hexColor);

        return imagecolorallocate(
            $canvas,
            $rgbColor['red'],
            $rgbColor['green'],
            $rgbColor['blue']
        );
    }

    /**
     * Create a random string
     *
     * @param string $characters
     * @param int $minLength
     * @param int $maxLength
     * @return string
     */
    private function randomString($characters = '123456789' , $minLength = 4, $maxLength = 6)
    {
        $randomLength = mt_rand($minLength, $maxLength);
        $string = [];

        for ($i = 0; $i < $randomLength; $i++) {
            $string[] = $characters[mt_rand(1, mb_strlen($characters) - 1)];
        }

        $string = implode($string);

        return $string;
    }

    /**
     * Convert hex color to rgb
     *
     * @param $hexColor
     * @return array
     */
    private function hexToRGB($hexColor)
    {
        $hexColor = ($hexColor[0] == '#') ? substr($hexColor, 1) : $hexColor;

        // Separate colors
        switch(strlen($hexColor)) {
            case 6:
                $red = $hexColor[0].$hexColor[1];
                $green = $hexColor[2].$hexColor[3];
                $blue = $hexColor[4].$hexColor[5];
                break;
            case 3:
                $red = str_repeat($hexColor[0],2);
                $green = str_repeat($hexColor[1],2);
                $blue = str_repeat($hexColor[2],2);
                break;
            default:
                $red = $green = $blue = 0;
                break;
        }

        // Convert hex to dec
        $red = hexdec($red);
        $green = hexdec($green);
        $blue = hexdec($blue);

        return [
            'red' => $red,
            'green' => $green,
            'blue' => $blue,
        ];
    }
}
