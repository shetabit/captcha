<?php

namespace Shetabit\Captcha\Drivers\Simple\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Shetabit\Captcha\Drivers\Simple\SimpleDriver;
use Shetabit\Captcha\Facade\Captcha;

class CaptchaController extends Controller
{
    /**
     * Get captcha image
     */
    public function getCaptcha() : Response
    {
        $driver = app(Captcha::SERVICE_NAME)->prepareDriver();

        abort_unless($driver instanceof SimpleDriver, 404);

        return response(
            $driver->prepareCaptchaImage(),
            200,
            [
                'content-type' => 'image/png',
                'cache-control' => 'no-store, no-cache, must-revalidate',
            ]
        );
    }
}
