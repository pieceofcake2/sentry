# Sentry Documentation

pieceofcake2/sentry reports the errors and uncaught exceptions of a CakePHP 2 application
to [Sentry](https://sentry.io/) with the [Sentry PHP SDK](https://github.com/getsentry/sentry-php).
It provides `SentryErrorHandler`, which extends CakePHP's `ErrorHandler`: errors and
exceptions are still logged and rendered by CakePHP, and are sent to Sentry as well.

- [Installation](#installation)
- [Configuration](#configuration)
- [What Is Reported](#what-is-reported)

## Installation

Install the plugin with [Composer](https://getcomposer.org/):

```
composer require pieceofcake2/sentry
```

The plugin has the `cakephp-plugin` type, so it is installed into your plugins directory
by `composer/installers`. Set `installer-paths` in your application's `composer.json` so it
is installed into the right place:

| Layout | `installer-paths` |
| --- | --- |
| Modern (`config/`, `src/`, `templates/`, `plugins/`) | `"plugins/{$name}/": ["type:cakephp-plugin"]` |
| Legacy (`app/Config/`, `app/Plugin/`) | `"app/Plugin/{$name}/": ["type:cakephp-plugin"]` |

Load the plugin in `config/bootstrap.php` (legacy layout: `app/Config/bootstrap.php`).
If you already use `CakePlugin::loadAll()`, this is not necessary:

```php
CakePlugin::load('Sentry');
```

## Configuration

Configure the plugin and register `SentryErrorHandler` as the error and exception handler
in `config/core.php` (legacy layout: `app/Config/core.php`):

```php
Configure::write('Sentry', [
    'dsn' => 'SENTRY_DSN',
    'options' => [
        'environment' => 'SENTRY_ENVIRONMENT',
        'release' => 'SENTRY_RELEASE',
    ],
    'ignoredExceptions' => [
        NotFoundException::class,
        MissingControllerException::class,
        MissingActionException::class,
    ],
]);
App::uses('SentryErrorHandler', 'Sentry.Lib/Error');
```

```php
Configure::write('Error', [
    'handler' => 'SentryErrorHandler::handleError',
    'level' => E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED,
    'trace' => true,
]);
```

```php
Configure::write('Exception', [
    'handler' => 'SentryErrorHandler::handleException',
    'renderer' => 'ExceptionRenderer',
    'log' => true,
]);
```

CakePHP registers the handlers after `bootstrap.php` has been loaded, so the class is found
once the plugin is loaded there.

| Key | Description |
| --- | --- |
| `Sentry.dsn` | The DSN of your Sentry project. |
| `Sentry.options` | Options passed to `Sentry\init()`, such as `environment`, `release` or `sample_rate`. `dsn` is set from `Sentry.dsn`. |
| `Sentry.ignoredExceptions` | Exception classes that are not sent to Sentry. Subclasses are ignored as well. They are still handled by CakePHP. |

## What Is Reported

The Sentry SDK is initialized when the first error or exception is handled.

- **Errors** (warnings, notices, etc.) are passed to CakePHP's `ErrorHandler::handleError()` and
  sent to Sentry as an `ErrorException` when their level is included in `error_reporting()`.
- **Uncaught exceptions** are passed to CakePHP's `ErrorHandler::handleException()` and sent to
  Sentry unless they are an instance of one of `Sentry.ignoredExceptions`.
- **Fatal errors** (`E_ERROR`, `E_PARSE`, `E_CORE_ERROR` and `E_COMPILE_ERROR`) are passed to
  the error handler by CakePHP when the request shuts down, and are reported like other errors.
