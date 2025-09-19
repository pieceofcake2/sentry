# CakePHP2 Sentry Plugin

[![GitHub License](https://img.shields.io/github/license/friendsofcake2/sentry?label=License)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/friendsofcake2/sentry?label=Packagist)](https://packagist.org/packages/friendsofcake2/sentry)
[![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/friendsofcake2/sentry/php?logo=php&logoColor=%23FFFFFF&label=PHP&labelColor=%23777BB4&color=%23FFFFFF)](https://packagist.org/packages/friendsofcake2/sentry)
[![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/friendsofcake2/sentry/cakephp/cakephp?logo=cakephp&logoColor=%23FFFFFF&label=CakePHP&labelColor=%23D33C43&color=%23FFFFFF)](https://packagist.org/packages/friendsofcake2/sentry)
[![Tests](https://img.shields.io/github/actions/workflow/status/friendsofcake2/sentry/tests.yml?label=Tests)](https://github.com/friendsofcake2/sentry/actions/workflows/tests.yml)
[![Codecov](https://img.shields.io/codecov/c/gh/friendsofcake2/sentry?label=Coverage)](https://codecov.io/gh/friendsofcake2/sentry)

## Installation

```
composer require friendsofcake2/sentry
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
