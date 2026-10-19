# CakePHP2 Sentry Plugin

[![GitHub License](https://img.shields.io/github/license/pieceofcake2/sentry?label=License)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/pieceofcake2/sentry?label=Packagist)](https://packagist.org/packages/pieceofcake2/sentry)
![PHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/sentry/php?logo=php&logoColor=%23FFFFFF&label=PHP&labelColor=%23777BB4&color=%23FFFFFF)
![CakePHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/sentry/pieceofcake2/cakephp?logo=cakephp&logoColor=%23FFFFFF&label=CakePHP&labelColor=%23D33C43&color=%23FFFFFF)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen?label=PHPStan)](https://github.com/pieceofcake2/sentry/actions/workflows/CI.yml)
[![CI](https://img.shields.io/github/actions/workflow/status/pieceofcake2/sentry/CI.yml?label=CI)](https://github.com/pieceofcake2/sentry/actions/workflows/CI.yml)
[![Codecov](https://img.shields.io/codecov/c/gh/pieceofcake2/sentry?label=Coverage)](https://codecov.io/gh/pieceofcake2/sentry)

pieceofcake2/sentry reports the errors and uncaught exceptions of a CakePHP 2 application
to [Sentry](https://sentry.io/). It provides an error handler that extends CakePHP's
`ErrorHandler`, so errors are still logged and rendered by CakePHP and sent to Sentry as well.
It also gives the URL that Content Security Policy violations are reported to in Sentry.

## Documentation

- Website: https://pieceofcake2.github.io/sentry/ (日本語: https://pieceofcake2.github.io/sentry/ja/)
- Markdown: [docs/README.md](docs/README.md) (日本語: [docs/ja/README.md](docs/ja/README.md)) (for AI tools and offline reading)

The Markdown documentation is also included in the Composer package, in the plugin's directory: `plugins/Sentry/docs/README.md` (the location depends on your `installer-paths`). Install the package with `--dev` if you only need it for development.

## Requirements

* PHP 8.0+
* pieceofcake2/cakephp 2.12+

## Installation

```bash
composer require pieceofcake2/sentry
```

Then load the plugin and register `SentryErrorHandler` as the error and exception handler.
See [Installation](docs/README.md#installation) and [Configuration](docs/README.md#configuration).

## Contributing

See the [contribution guidelines](https://github.com/pieceofcake2/.github/blob/master/CONTRIBUTING.md).

## Origin

This package was created by [pieceofcake2](https://github.com/pieceofcake2), which also maintains
it. It is not a fork of another project and is not based on one.

## License

MIT License. See [LICENSE](LICENSE).
