<?php

namespace Shetabit\Captcha\Drivers\Simple\Controllers;

use App\Http\Controllers\Controller;
use Shetabit\Captcha\CaptchaManager;

/**
 * Class CaptchaController
 *
 */
class CaptchaController extends Controller
{
    /**
     * Get captcha image
     *
     * @return mixed
     */
    public function getCaptcha()
    {
        return response(
            app('shetabit-captcha')->prepareDriver()->prepareCaptchaImage(),
            200,
            ['content-type' => 'image/png']
        );
    }
}
