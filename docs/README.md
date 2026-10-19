# Sentry Documentation

pieceofcake2/sentry reports the errors and uncaught exceptions of a CakePHP 2 application
to [Sentry](https://sentry.io/) with the [Sentry PHP SDK](https://github.com/getsentry/sentry-php).
It provides `PieceOfCake2\Sentry\Error\SentryErrorHandler`, which extends CakePHP's `ErrorHandler`: errors and
exceptions are still logged and rendered by CakePHP, and are sent to Sentry as well.

- [Installation](#installation)
- [Configuration](#configuration)
  - [DSN from Its Parts](#dsn-from-its-parts)
- [Content Security Policy Reports](#content-security-policy-reports)
- [What Is Reported](#what-is-reported)
- [Upgrading from 1.x](#upgrading-from-1-x)

## Installation

Install the plugin with [Composer](https://getcomposer.org/):

```bash
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
in `config/core.php` (legacy layout: `app/Config/core.php`). The classes of the plugin are in the
`PieceOfCake2\Sentry` namespace and are loaded by the Composer autoloader, so `App::uses()` is not
needed, and they can be used in `core.php`, before the plugin is loaded:

```php
use PieceOfCake2\Sentry\Error\SentryErrorHandler;

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
```

```php
Configure::write('Error', [
    'handler' => [SentryErrorHandler::class, 'handleError'],
    'level' => E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED,
    'trace' => true,
]);
```

```php
Configure::write('Exception', [
    'handler' => [SentryErrorHandler::class, 'handleException'],
    'renderer' => 'ExceptionRenderer',
    'log' => true,
]);
```

The handlers can also be written as strings, such as
`'PieceOfCake2\Sentry\Error\SentryErrorHandler::handleError'`.

| Key | Description |
| --- | --- |
| `Sentry.dsn` | The DSN of your Sentry project: a string, or its parts (see [DSN from Its Parts](#dsn-from-its-parts)). When it is not set or empty, events are not sent. |
| `Sentry.options` | Options passed to `Sentry\init()`, such as `environment`, `release` or `sample_rate`. `dsn` is set from `Sentry.dsn`. |
| `Sentry.ignoredExceptions` | Exception classes that are not sent to Sentry. Subclasses are ignored as well. They are still handled by CakePHP. |

### DSN from Its Parts

Instead of a DSN string, `Sentry.dsn` can be given as its parts. A DSN has the form
`https://{publicKey}@{host}/{projectId}`, and you can read the parts from separate
environment variables:

```php
Configure::write('Sentry', [
    'dsn' => [
        'publicKey' => env('SENTRY_KEY'),
        'host' => env('SENTRY_HOST'),
        'projectId' => env('SENTRY_PROJECT_ID'),
    ],
    'options' => [
        'environment' => env('SENTRY_ENVIRONMENT'),
        'release' => env('SENTRY_RELEASE'),
    ],
]);
```

| Part | Description |
| --- | --- |
| `publicKey` | The public key of the project's client key, such as `abc123...`. |
| `host` | The host that receives the events, such as `o123456.ingest.us.sentry.io`. It may include a port (`sentry.example.com:9000`). |
| `projectId` | The ID of the project, such as `4501234567890123`. |
| `scheme` | Optional. `https` (default) or `http`. |

When any of `publicKey`, `host` and `projectId` is empty or not set (for example, the
environment variables are not set in development), Sentry is not configured and events are
not sent. An unknown part, a part that is not a string, or a part containing `@`, `/`, `?`
or `#` throws an `InvalidArgumentException`.

The public key is not a secret: it only allows sending events to the project, and it is
exposed to every browser anyway when the DSN is used in the browser or in the
[report URL of the Content Security Policy](#content-security-policy-reports). You can keep it
in your configuration, not in a secret store.

## Content Security Policy Reports

Sentry can collect the violations of your [Content Security Policy](https://docs.sentry.io/security-legal-pii/security/security-policy-reporting/)
at the security endpoint of the project. `PieceOfCake2\Sentry\SentryConfig::cspReportUri()` returns the URL of that
endpoint for the configured DSN:

```
https://o123456.ingest.us.sentry.io/api/4501234567890123/security/?sentry_key=abc123&sentry_environment=production&sentry_release=1.2.3
```

`sentry_environment` and `sentry_release` are added from `Sentry.options.environment` and
`Sentry.options.release`, and are left out when they are empty. When Sentry is not configured,
`SentryConfig::cspReportUri()` returns `null`. `SentryConfig::dsn()` returns the configured DSN
as a `Sentry\Dsn`, or `null`.

For example, with [pieceofcake2/security-headers](https://github.com/pieceofcake2/security-headers),
set the report URL in `config/bootstrap.php` (`Sentry` is configured in `config/core.php`,
which is read before `bootstrap.php`):

```php
use PieceOfCake2\Sentry\SentryConfig;

Configure::write('SecurityHeaders', [
    // ...
    'reportUri' => SentryConfig::cspReportUri() ?? false,
]);
```

## What Is Reported

The Sentry SDK is initialized when the first error or exception is handled.

- **Errors** (warnings, notices, etc.) are passed to CakePHP's `ErrorHandler::handleError()` and
  sent to Sentry as an `ErrorException` when their level is included in `error_reporting()`.
- **Uncaught exceptions** are passed to CakePHP's `ErrorHandler::handleException()` and sent to
  Sentry unless they are an instance of one of `Sentry.ignoredExceptions`.
- **Fatal errors** (`E_ERROR`, `E_PARSE`, `E_CORE_ERROR` and `E_COMPILE_ERROR`) are passed to
  the error handler by CakePHP when the request shuts down, and are reported like other errors.

<a id="upgrading-from-1-x"></a>

## Upgrading from 1.x

In 1.x, the error handler was the global class `SentryErrorHandler` in `Lib/Error`. It is now
`PieceOfCake2\Sentry\Error\SentryErrorHandler` in `src/Error`, loaded by Composer. Update
`config/core.php`:

```php
// Before
App::uses('SentryErrorHandler', 'Sentry.Lib/Error');
Configure::write('Error', ['handler' => 'SentryErrorHandler::handleError', /* ... */]);
Configure::write('Exception', ['handler' => 'SentryErrorHandler::handleException', /* ... */]);

// After
use PieceOfCake2\Sentry\Error\SentryErrorHandler;

Configure::write('Error', ['handler' => [SentryErrorHandler::class, 'handleError'], /* ... */]);
Configure::write('Exception', ['handler' => [SentryErrorHandler::class, 'handleException'], /* ... */]);
```

If you cannot change it yet, load the bootstrap of the plugin, which defines `SentryErrorHandler` as
an alias of the new class. Remove `App::uses('SentryErrorHandler', 'Sentry.Lib/Error')`, or it is
harmless as long as the alias is defined before the handler is called (CakePHP registers the
handlers after `bootstrap.php` has been loaded). New applications do not need this:

```php
CakePlugin::load('Sentry', ['bootstrap' => true]);
```

## Origin

This package was created by [pieceofcake2](https://github.com/pieceofcake2), which also maintains
it. It is not a fork of another project and is not based on one.
