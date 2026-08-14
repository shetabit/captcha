<?php

namespace Shetabit\Captcha\Drivers\Simple;

use GdImage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Shetabit\Captcha\Abstracts\Driver;
use Shetabit\Captcha\Provider\CaptchaServiceProvider;

class SimpleDriver extends Driver
{
    /**
     * SimpleDriver constructor.
     * Construct the class with the relevant settings.
     *
     * @param array<string, mixed>|object $settings
     */
    public function __construct(protected ServiceProvider $serviceProvider, mixed $settings)
    {
        $this->settings = (object) (array) $settings;

        $this->bindViews()
             ->bindRoutes()
             ->publishResources();
    }

    /**
     * Prepare CAPTCHA image and memorize its token.
     */
    public function prepareCaptchaImage() : string
    {
        $settings = $this->settings;

        $token = $this->randomString(
            $settings->characters,
            $settings->length[0],
            $settings->length[1]
        );

        // save token in memory
        $this->pushInMemory($settings->sessionKey, $token);

        return $this->drawImage(
            $token,
            $settings->width,
            $settings->height,
            $settings->foregroundColors,
            $settings->backgroundColor,
            $settings->letterSpacing,
            $settings->fontSize,
            $this->fontFamily()
        );
    }

    /**
     * Generate captcha.
     */
    public function generate() : View
    {
        return ViewFactory::make(
            'captchaSimpleDriver::captcha',
            [
                'routeName' => $this->settings->route,
                'errorsName' => config('captcha.validator'),
            ]
        );
    }

    /**
     * Verify token.
     */
    public function verify(string|null $token = null) : bool
    {
        $storedToken = $this->pullFromMemory($this->settings->sessionKey);

        // Without a captcha that was handed out there is nothing to verify.
        if ($storedToken === null || $token === null) {
            return false;
        }

        if (empty($this->settings->sensitive)) {
            $storedToken = mb_strtolower($storedToken);
            $token = mb_strtolower($token);
        }

        return hash_equals($storedToken, $token);
    }

    /**
     * The font the captcha is written with.
     *
     * The configuration points at the published font, which is only there once
     * `artisan vendor:publish` copied it. The one of the package stands in for
     * it until then.
     */
    public function fontFamily() : string
    {
        $configured = (string) ($this->settings->fontFamily ?? '');

        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        return __DIR__.'/resources/assets/fonts/DroidSerif.ttf';
    }

    /**
     * Bind driver views
     */
    protected function bindViews() : static
    {
        $this->provider()->bindViewFile(__DIR__.'/resources/views', 'captchaSimpleDriver');

        return $this;
    }

    /**
     * Bind driver routes
     */
    protected function bindRoutes() : static
    {
        $this->provider()->bindRouteFile(__DIR__.'/routes.php');

        return $this;
    }

    /**
     * Publish driver assets
     */
    protected function publishResources() : static
    {
        $destinationPath = resource_path('views/vendor/captchaSimpleDriver');

        $this->provider()
             ->publish(__DIR__.'/resources/views', $destinationPath, ['views', 'captcha-views'])
             ->publish(__DIR__.'/resources/assets', $destinationPath.'/assets', ['assets', 'captcha-assets']);

        return $this;
    }

    /**
     * Save token in memory.
     */
    protected function pushInMemory(string $key, string $value) : static
    {
        session()->put($key, $value);

        return $this;
    }

    /**
     * Retrieve token from memory.
     *
     * A captcha is only good for one try, so it is taken out on the way.
     */
    protected function pullFromMemory(string $key) : string|null
    {
        $value = session()->pull($key);

        return is_string($value) ? $value : null;
    }

    /**
     * Create new canvas.
     */
    protected function canvas(int $width, int $height) : GdImage
    {
        $canvas = imagecreatetruecolor(max(1, $width), max(1, $height));

        if (!$canvas instanceof GdImage) {
            throw new RuntimeException('The canvas of the captcha could not be created.');
        }

        return $canvas;
    }

    /**
     * Generate image
     *
     * @param array<int, string> $foregroundColors
     */
    protected function drawImage(
        string $token,
        int $width,
        int $height,
        array $foregroundColors,
        string $backgroundColor,
        int $letterSpacing,
        int $fontSize,
        string $fontFamily
    ) : string {
        $canvas = $this->canvas($width, $height);

        $this->fillWithColor($canvas, $backgroundColor);

        $length = mb_strlen($token);
        $offsetX = (int) (($width - $length * ($letterSpacing + $fontSize * 0.66)) / 2);
        $offsetY = (int) ceil($height / 1.5);

        // write token
        for ($i = 0; $i < $length; $i++) {
            imagettftext(
                $canvas,
                $fontSize,
                random_int(0, 10),
                $offsetX,
                $offsetY,
                $this->prepareColor($canvas, $this->randomColor($foregroundColors)),
                $fontFamily,
                mb_substr($token, $i, 1)
            );

            $offsetX += (int) ceil($fontSize * 0.66) + $letterSpacing;
        }

        //Scratches foreground
        for ($i = 0; $i < $this->settings->scratches[0]; $i++) {
            $this->drawScratch($canvas, $width, $height, $this->randomColor($foregroundColors));
        }

        //Scratches background
        for ($i = 0; $i < $this->settings->scratches[1]; $i++) {
            $this->drawScratch($canvas, $width, $height, $backgroundColor);
        }

        ob_start();
        imagepng($canvas);
        $content = (string) ob_get_clean();

        imagedestroy($canvas);

        return $content;
    }

    /**
     * Fill canvas with the given color
     */
    protected function fillWithColor(GdImage $canvas, string $color) : static
    {
        imagefill($canvas, 0, 0, $this->prepareColor($canvas, $color));

        return $this;
    }

    /**
     * The provider the driver registers its views and routes with.
     */
    private function provider() : CaptchaServiceProvider
    {
        if (!$this->serviceProvider instanceof CaptchaServiceProvider) {
            throw new RuntimeException(CaptchaServiceProvider::class.' is what this driver registers itself with.');
        }

        return $this->serviceProvider;
    }

    /**
     * One of the given colors.
     *
     * @param array<int, string> $colors
     */
    private function randomColor(array $colors) : string
    {
        return $colors[random_int(0, count($colors) - 1)];
    }

    /**
     * Draw scratches
     */
    private function drawScratch(GdImage $img, int $imageWidth, int $imageHeight, string $hex) : void
    {
        $rgb = $this->hexToRGB($hex);

        imageline(
            $img,
            random_int(0, (int) floor($imageWidth / 2)),
            random_int(1, $imageHeight),
            random_int((int) floor($imageWidth / 2), $imageWidth),
            random_int(1, $imageHeight),
            (int) imagecolorallocate($img, $rgb['red'], $rgb['green'], $rgb['blue'])
        );
    }

    /**
     * prepare a color
     */
    private function prepareColor(GdImage $canvas, string $hexColor) : int
    {
        $rgbColor = $this->hexToRGB($hexColor);

        return (int) imagecolorallocate(
            $canvas,
            $rgbColor['red'],
            $rgbColor['green'],
            $rgbColor['blue']
        );
    }

    /**
     * Create a random string
     */
    private function randomString(string $characters = '123456789', int $minLength = 4, int $maxLength = 6) : string
    {
        $pool = mb_str_split($characters);

        if ($pool === []) {
            throw new RuntimeException('A captcha needs characters to be built of.');
        }

        $string = '';

        for ($i = random_int($minLength, $maxLength); $i > 0; $i--) {
            // The first character of the pool used to be out of reach, and the
            // token of a captcha is worth a random source that can not be told.
            $string .= $pool[random_int(0, count($pool) - 1)];
        }

        return $string;
    }

    /**
     * Convert hex color to rgb
     *
     * @return array{red: int<0, 255>, green: int<0, 255>, blue: int<0, 255>}
     */
    private function hexToRGB(string $hexColor) : array
    {
        $hexColor = str_starts_with($hexColor, '#') ? substr($hexColor, 1) : $hexColor;

        // Separate colors
        switch (strlen($hexColor)) {
            case 6:
                $red = $hexColor[0].$hexColor[1];
                $green = $hexColor[2].$hexColor[3];
                $blue = $hexColor[4].$hexColor[5];
                break;
            case 3:
                $red = str_repeat($hexColor[0], 2);
                $green = str_repeat($hexColor[1], 2);
                $blue = str_repeat($hexColor[2], 2);
                break;
            default:
                $red = $green = $blue = '0';
                break;
        }

        return [
            'red' => $this->channel($red),
            'green' => $this->channel($green),
            'blue' => $this->channel($blue),
        ];
    }

    /**
     * One channel of a color, as a number a color is allocated with.
     *
     * @return int<0, 255>
     */
    private function channel(string $hex) : int
    {
        return min(255, max(0, (int) hexdec($hex)));
    }
}
