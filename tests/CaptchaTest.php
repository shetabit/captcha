<?php

namespace Hamog\Captcha;

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
		parent::setUp();
		$this->captcha = new Captcha();
	}

	/**
	 * Test captcha image.
	 */
	public function testCaptchaImg()
	{
		$this->assertTrue($this->captcha instanceof Captcha);
	}
}