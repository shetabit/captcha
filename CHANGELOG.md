# Changelog

All Notable changes to `captcha` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## Unreleased

### Added
- A test suite of 39 tests: unit tests for the manager, the simple driver, the provider and the helpers, and feature
  tests that run the flow of the readme end to end — a form with a captcha, the image over the route of the driver and
  the validation of the answer. It covers 95% of `src/`.
- GitHub Actions workflows running the test suite (PHP 8.4 and 8.5, Laravel 12 and 13, lowest and highest
  dependencies), the coding style check, the static analysis and the code coverage on every pull request and on every
  push to `master`. The coverage has to stay above 90%.
- A `Dockerfile` (with gd and pcov) and a `Makefile` to run the test suite and every check inside a container, so that
  no PHP installation is needed on the host.
- A `phpcs.xml.dist` ruleset, a `phpstan.neon.dist` configuration (level 7 with larastan) and a `rector.php`
  configuration, plus the `test-coverage`, `analyse`, `rector` and `ci` composer scripts.
- `CaptchaManager::via()` is public, so the driver can be changed at runtime — which the configuration file has always
  said it can be. `getDriver()` answers with the name of the driver in use.
- The `captcha_manager()` helper, `Shetabit\Captcha\Facade\Captcha::SERVICE_NAME` for the name of the binding, and
  the `captcha-config`, `captcha-views` and `captcha-assets` publish tags next to the generic ones.

### Changed
- **Breaking:** PHP 8.4 is now the minimum required version (was PHP 7.2), and only the last two major versions of
  Laravel are supported: `^12.0|^13.0` (was `~5.4`). `illuminate/routing` and `illuminate/view` — both used by the
  package — are declared now.
- The package was modernized for PHP 8.4. Every parameter, return value and property of `src/` declares a type now,
  `DriverInterface::generate()` declares the `View` it answers with and `verify()` declares `bool`.
- **Breaking:** `guzzlehttp/guzzle` was dropped from the dependencies. Nothing in the package ever used it.
- The captcha manager is a singleton and keeps the driver it built. The driver registers views, routes and publishable
  resources while it is being built, which used to happen again for every `generate()` and every `verify()`.
- The route of the simple driver is registered with the controller as a class, and answers with a `no-store` cache
  header so that a browser does not show the same captcha twice.

### Fixed
- **A form could be sent without solving the captcha.** `verify()` compared with `==`, so an empty answer and a
  session without a captcha (`'' == null`) passed. Verification now fails whenever there is no captcha to check
  against, and compares with `hash_equals()`.
- **The package only worked after publishing its configuration.** `mergeConfigFrom()` was missing, so `config('captcha')`
  was `null` and the manager could not even be built.
- **`CaptchaController` extended `App\Http\Controllers\Controller`** — a class of the application, not of the
  package. It extends `Illuminate\Routing\Controller` now.
- **The first character of the configured pool was never part of a token**: the random index started at 1. The
  characters of a captcha are drawn with `random_int()` now, not with `mt_rand()`.
- **The captcha could not be drawn before publishing the assets**: the configured font path points at the published
  file. The font of the package stands in for it until then.
- `CaptchaManager::getFreshDriverInstance()` read a `callbackUrl` property that this package never had.
- `pullFromMemory()` forgot the session key it had already pulled, and `drawImage()` used the font size of the
  settings instead of the one it was given.

### Removed
- The Travis CI configuration (`.travis.yml`), replaced by GitHub Actions.
- The StyleCI configuration (`.styleci.yml`), replaced by the PHP_CodeSniffer workflow.
- The Scrutinizer badge of the readme, replaced by the workflow badges and a code coverage badge.
- The `branch-alias` of `composer.json`, and the broken `tests/CaptchaTest.php`, which referenced a `Hamog\Captcha\Captcha`
  class that does not exist in this package.

## Date - 2019-01-09

### Fixed
- Nothing

### Added
- Nothing

### Deprecated
- Nothing

### Fixed
- Nothing

### Removed
- Nothing

### Security
- Nothing
