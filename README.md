# Laravel Captcha

This packages works with multiple drivers, 
and you can create custom drivers if there are not available in the 
current drivers list (below list).

# Laravel Captcha

[![Software License][ico-license]](LICENSE.md)
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Quality Score][ico-code-quality]][link-code-quality]

This is a Laravel Package for captcha Integration. This package supports `Laravel 5.4+`.

> This packages works with multiple drivers, and you can create custom drivers if there are not available in the [current drivers list](#list-of-available-drivers) (below list).

# List of contents

- [Available drivers](#list-of-available-drivers)
- [Install](#install)
- [Configure](#configure)
- [How to use](#how-to-use)
  - [Add captcha in forms](#add-captcha-in-forms)
  - [Validation](#validation)
  - [Create custom drivers](#create-custom-drivers)
- [Change log](#change-log)
- [Contributing](#contributing)
- [Security](#security)
- [Credits](#credits)
- [License](#license)

# List of available drivers

- Simple : a simple image captcha.
- Others are under way.

> you can create your own custom driver if not  exists in the list , read the `Create custom drivers` section.

## Install

Via Composer

``` bash
$ composer require shetabit/captcha
```

## Configure

If you are using `Laravel 5.5` or higher then you don't need to add the provider and alias.

In your `config/app.php` file add these two lines.

```php
# In your providers array.
'providers' => [
    ...
    Shetabit\Captcha\Provider\CaptchaServiceProvider::class,
],

# In your aliases array.
'aliases' => [
    ...
    'Payment' => Shetabit\Captcha\Facade\Captcha::class,
],
```

then run `php artisan vendor:publish` to publish `config/captcha.php` file in your config directory.

In the config file you can set the `default driver` to use for all your payments. But you can also change the driver at runtime.

Choose what gateway you would like to use in your application. Then make that as default driver so that you don't have to specify that everywhere. But, you can also use multiple gateways in a project.

```php
// Eg. if you want to use simple. (simple is the driver's name)
'default' => 'simple',
```

Then see the configs in the drivers array.

```php
'drivers' => [
    'simple' => [
        'middleware' => ['web'], // middleware
        'route' => 'captcha', // route name
        'characters' => 'ABCDEFGHIKJLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789',
        'width'  => 230,
        'height' => 70,
        'foregroundColors' => ['2980b9','2E9FFF','FF1166','000000','22EE99'],
        'backgroundColor' => '#FFF',
        'letterSpacing' => 6,
        'fontFamily' => resource_path('views/vendor/captchaSimpleDriver/assets/fonts/DroidSerif.ttf'),
        'fontSize' => 30,
        'length' => [4, 6],
        'scratches' => [5, 8],
        'sensitive' => false,
        'sessionKey' => 'captcha',
    ],
    ...
]
```

## How to use

you have 2 steps to go

- add captcha in forms
- add validation

#### Add captcha in forms

In your code, use it like the below:

```php
<form>
...

{!! captcha() !!}

...
</form>
```

if you use `simple` driver, you can change the styles and UI easily,
just have a look on `resources/views/vendor/captchaSimpleDriver.blade.php`

#### Validation
in order to validate forms wich use captcha, you can use `captcha` validation role.

The below example shows every thing you need to know about captcha validation:

```php
...

$request->validate([
    'email' => 'required|email',
    'password' => 'required|string',
    'captcha' => 'required|captcha',
]);

...
```

#### Create custom drivers:

First you have to add the name of your driver, in the drivers array and also you can specify any config parameters you want.

```php
'drivers' => [
    'simple' => [...],
    'my_driver' => [
        ... # Your Config Params here.
    ]
]
```

Now you have to create a Driver Map Class that will be used to pay invoices.
In your driver, You just have to extend `Shetabit\Captcha\Abstracts\Driver`.

Ex. You created a class : `App\Packages\CaptchaDriver\MyDriver`.

```php
namespace App\Packages\CaptchaDriver;

use Illuminate\Support\ServiceProvider;
use Shetabit\Captcha\Abstracts\Driver;

class MyDriver extends Driver
{
    protected $serviceProvider;

    /**
     * Driver settings.
     *
     * @var object
     */
    protected $settings;

    public function __construct(ServiceProvider $serviceProvider, $settings)
    {
        $this->serviceProvider = $serviceProvider;
        $this->settings = (object) $settings;
    }
    
    /**
        you must write your captcha generation 
        logic in the below method.
    **/
    public function generate()
    {
        ...
    
        // create captcha view and return it
        return View::make('yourCustomDriverView');
    }

    /**
        you must write your captcha verification
        logic in the below method.
    **/
    public function verify($token = null)
    {
        ...
    
        $storedToken = ...

        if (empty($this->settings->sensitive)) {
            $storedToken = mb_strtolower($storedToken);
            $token = mb_strtolower($token);
        }

        return $token == $storedToken;
    }
    
}
```

Once you create that class you have to specify it in the `captcha.php` config file `map` section.

```php
'map' => [
    ...
    'my_driver' => App\Packages\CaptchaDriver\MyDriver::class,
]
```

**Note:-** You have to make sure that the key of the `map` array is identical to the key of the `drivers` array.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email hashemm364@gmail.com instead of using the issue tracker.

## Credits

- [Hashem Moghaddari][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/shetabit/captcha.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/shetabit/captcha.svg?label=Code%20Quality&style=flat-square

[link-packagist]: https://packagist.org/packages/shetabit/captcha
[link-code-quality]: https://scrutinizer-ci.com/g/shetabit/captcha
[link-author]: https://github.com/hamog
[link-contributors]: ../../contributors
