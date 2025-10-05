# CakePHP2 Sentry Plugin

[![GitHub License](https://img.shields.io/github/license/pieceofcake2/sentry?label=License)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/pieceofcake2/sentry?label=Packagist)](https://packagist.org/packages/pieceofcake2/sentry)
[![PHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/sentry/php?logo=php&logoColor=%23FFFFFF&label=PHP&labelColor=%23777BB4&color=%23FFFFFF)](https://packagist.org/packages/pieceofcake2/sentry)
[![CakePHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/sentry/pieceofcake2/cakephp?logo=cakephp&logoColor=%23FFFFFF&label=CakePHP&labelColor=%23D33C43&color=%23FFFFFF)](https://packagist.org/packages/pieceofcake2/sentry)
[![Tests](https://img.shields.io/github/actions/workflow/status/pieceofcake2/sentry/tests.yml?label=Tests)](https://github.com/pieceofcake2/sentry/actions/workflows/tests.yml)
[![Codecov](https://img.shields.io/codecov/c/gh/pieceofcake2/sentry?label=Coverage)](https://codecov.io/gh/pieceofcake2/sentry)

## Installation

```
composer require pieceofcake2/sentry
```

## Config

`Config/core.php`

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
    ]
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
