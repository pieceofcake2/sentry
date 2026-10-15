# Sentry ドキュメント

pieceofcake2/sentry は、CakePHP 2 アプリケーションのエラーとキャッチされなかった例外を、
[Sentry PHP SDK](https://github.com/getsentry/sentry-php) を使って [Sentry](https://sentry.io/) に報告します。
CakePHP の `ErrorHandler` を継承した `SentryErrorHandler` を提供します。エラーと例外はこれまでどおり CakePHP が
ログに記録して表示し、さらに Sentry にも送信されます。

- [インストール](#installation)
- [設定](#configuration)
- [報告される内容](#what-is-reported)

<a id="installation"></a>

## インストール

[Composer](https://getcomposer.org/) でプラグインをインストールします。

```bash
composer require pieceofcake2/sentry
```

このプラグインは `cakephp-plugin` タイプなので、`composer/installers` によってプラグインのディレクトリに
インストールされます。正しい場所にインストールされるように、アプリケーションの `composer.json` で
`installer-paths` を設定してください。

| 構成 | `installer-paths` |
| --- | --- |
| モダン (`config/`、`src/`、`templates/`、`plugins/`) | `"plugins/{$name}/": ["type:cakephp-plugin"]` |
| レガシー (`app/Config/`、`app/Plugin/`) | `"app/Plugin/{$name}/": ["type:cakephp-plugin"]` |

プラグインは `config/bootstrap.php` (レガシー構成では `app/Config/bootstrap.php`) で読み込みます。
すでに `CakePlugin::loadAll()` を使っている場合は必要ありません:

```php
CakePlugin::load('Sentry');
```

<a id="configuration"></a>

## 設定

`config/core.php` (レガシー構成では `app/Config/core.php`) でプラグインを設定し、`SentryErrorHandler` を
エラーハンドラーと例外ハンドラーとして登録します。

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

CakePHP は `bootstrap.php` を読み込んだ後でハンドラーを登録するので、そこでプラグインを読み込んでいればクラスが見つかります。

| キー | 説明 |
| --- | --- |
| `Sentry.dsn` | Sentry プロジェクトの DSN。 |
| `Sentry.options` | `environment`、`release`、`sample_rate` など、`Sentry\init()` に渡すオプション。`dsn` には `Sentry.dsn` の値が設定されます。 |
| `Sentry.ignoredExceptions` | Sentry に送信しない例外クラス。サブクラスも無視されます。これらの例外も CakePHP では引き続き処理されます。 |

<a id="what-is-reported"></a>

## 報告される内容

Sentry SDK は、最初のエラーまたは例外を処理するときに初期化されます。

- **エラー** (warning、notice など) は CakePHP の `ErrorHandler::handleError()` に渡され、そのレベルが
  `error_reporting()` に含まれていれば `ErrorException` として Sentry に送信されます。
- **キャッチされなかった例外** は CakePHP の `ErrorHandler::handleException()` に渡され、
  `Sentry.ignoredExceptions` のいずれかのインスタンスでなければ Sentry に送信されます。
- **致命的なエラー** (`E_ERROR`、`E_PARSE`、`E_CORE_ERROR` と `E_COMPILE_ERROR`) は、リクエストの終了時に CakePHP が
  エラーハンドラーに渡し、他のエラーと同じように報告されます。

<a id="origin"></a>

## 由来

このパッケージは [pieceofcake2](https://github.com/pieceofcake2) が作成し、メンテナンスしています。他のプロジェクトのフォークではなく、
他のプロジェクトを元にしたものでもありません。
