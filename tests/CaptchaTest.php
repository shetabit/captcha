<?php

namespace Hamog\Tests;

use Hamog\Captcha\Captcha;
use PHPUnit\Framework\TestCase;

class CaptchaTest extends TestCase
{
	/**
	 * @var Captcha
	 */
	private $captcha;

	/**
	 * setUp
	 */
	public function setUp()
	{
		$this->captcha = new Captcha();
	}

	/**
	 * Test captcha image.
	 */
	public function testCaptcha()
	{
		$this->assertTrue($this->captcha instanceof Captcha);
	}
}