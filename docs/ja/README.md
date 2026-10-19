# Sentry ドキュメント

pieceofcake2/sentry は、CakePHP 2 アプリケーションのエラーとキャッチされなかった例外を、
[Sentry PHP SDK](https://github.com/getsentry/sentry-php) を使って [Sentry](https://sentry.io/) に報告します。
CakePHP の `ErrorHandler` を継承した `SentryErrorHandler` を提供します。エラーと例外はこれまでどおり CakePHP が
ログに記録して表示し、さらに Sentry にも送信されます。

- [インストール](#installation)
- [設定](#configuration)
  - [DSN を部分ごとに指定する](#dsn-from-its-parts)
- [Content Security Policy の違反の報告](#content-security-policy-reports)
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
すでに `CakePlugin::loadAll()` を使っている場合は必要ありません。

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
| `Sentry.dsn` | Sentry プロジェクトの DSN。文字列か、その部分ごとの値 ([DSN を部分ごとに指定する](#dsn-from-its-parts) をご覧ください) です。設定されていないか空の場合は、イベントを送信しません。 |
| `Sentry.options` | `environment`、`release`、`sample_rate` など、`Sentry\init()` に渡すオプション。`dsn` には `Sentry.dsn` の値が設定されます。 |
| `Sentry.ignoredExceptions` | Sentry に送信しない例外クラス。サブクラスも無視されます。これらの例外も CakePHP では引き続き処理されます。 |

<a id="dsn-from-its-parts"></a>

### DSN を部分ごとに指定する

`Sentry.dsn` には、DSN の文字列の代わりに、その部分ごとの値を指定できます。DSN は
`https://{publicKey}@{host}/{projectId}` の形なので、それぞれを別の環境変数から読み込めます。

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

| 部分 | 説明 |
| --- | --- |
| `publicKey` | プロジェクトのクライアントキーの公開鍵 (`abc123...` など)。 |
| `host` | イベントを受け付けるホスト (`o123456.ingest.us.sentry.io` など)。ポートを含めることもできます (`sentry.example.com:9000`)。 |
| `projectId` | プロジェクトの ID (`4501234567890123` など)。 |
| `scheme` | 省略できます。`https` (デフォルト) か `http`。 |

`publicKey`、`host`、`projectId` のいずれかが空か設定されていない場合 (開発環境で環境変数を設定していない場合など) は、
Sentry は設定されていないものとして扱われ、イベントを送信しません。不明な部分、文字列でない部分、`@`、`/`、`?`、`#` を
含む部分を指定すると `InvalidArgumentException` が発生します。

公開鍵は秘密の値ではありません。プロジェクトにイベントを送信できるだけで、ブラウザーで DSN を使う場合や
[Content Security Policy の報告先の URL](#content-security-policy-reports) に使う場合は、どのみちすべてのブラウザーに渡ります。
シークレットの保管場所ではなく、設定に書いてかまいません。

<a id="content-security-policy-reports"></a>

## Content Security Policy の違反の報告

Sentry は、[Content Security Policy](https://docs.sentry.io/security-legal-pii/security/security-policy-reporting/) の違反を
プロジェクトのセキュリティのエンドポイントで集められます。`SentryConfig::cspReportUri()` は、設定された DSN の
このエンドポイントの URL を返します。

```
https://o123456.ingest.us.sentry.io/api/4501234567890123/security/?sentry_key=abc123&sentry_environment=production&sentry_release=1.2.3
```

`sentry_environment` と `sentry_release` は `Sentry.options.environment` と `Sentry.options.release` から付け、
空の場合は付けません。Sentry が設定されていない場合、`SentryConfig::cspReportUri()` は `null` を返します。
`SentryConfig::dsn()` は、設定された DSN を `Sentry\Dsn` として返します (設定されていない場合は `null`)。

たとえば [pieceofcake2/security-headers](https://github.com/pieceofcake2/security-headers) では、プラグインを読み込んだ後で
`config/bootstrap.php` に報告先の URL を設定します (`Sentry` は、`bootstrap.php` より前に読み込まれる `config/core.php` で設定します)。

```php
App::uses('SentryConfig', 'Sentry.Lib');

Configure::write('SecurityHeaders', [
    // ...
    'reportUri' => SentryConfig::cspReportUri() ?? false,
]);
```

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

このパッケージは [pieceofcake2](https://github.com/pieceofcake2) が作成し、メンテナンスしています。
他のプロジェクトのフォークではなく、他のプロジェクトをもとにしたものでもありません。
