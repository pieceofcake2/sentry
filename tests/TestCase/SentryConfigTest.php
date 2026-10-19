<?php

use PieceOfCake2\Sentry\SentryConfig;

class SentryConfigTest extends CakeTestCase
{
    private const DSN = 'https://abc123@o1.ingest.us.sentry.io/456';

    private const RELEASE = '1.2.3';

    public function tearDown(): void
    {
        Configure::delete('Sentry');
        parent::tearDown();
    }

    public function testDsnFromString(): void
    {
        Configure::write('Sentry.dsn', self::DSN);

        $dsn = SentryConfig::dsn();

        $this->assertSame(self::DSN, (string)$dsn);
        $this->assertSame('abc123', $dsn->getPublicKey());
        $this->assertSame('o1.ingest.us.sentry.io', $dsn->getHost());
        $this->assertSame('456', $dsn->getProjectId());
    }

    public function testDsnFromParts(): void
    {
        Configure::write('Sentry.dsn', [
            'publicKey' => 'abc123',
            'host' => 'o1.ingest.us.sentry.io',
            'projectId' => '456',
        ]);

        $this->assertSame(self::DSN, (string)SentryConfig::dsn());
    }

    public function testDsnFromPartsWithSchemePortAndIntegerProjectId(): void
    {
        Configure::write('Sentry.dsn', [
            'scheme' => 'http',
            'publicKey' => 'abc123',
            'host' => 'localhost:9000',
            'projectId' => 2,
        ]);

        $dsn = SentryConfig::dsn();

        $this->assertSame('http://abc123@localhost:9000/2', (string)$dsn);
        $this->assertSame(9000, $dsn->getPort());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function notConfiguredProvider(): array
    {
        return [
            'not set' => [null],
            'false' => [false],
            'empty string' => [''],
            'SDK empty value' => ['(empty)'],
            'empty public key' => [['publicKey' => '', 'host' => 'o1.ingest.us.sentry.io', 'projectId' => '456']],
            'missing host' => [['publicKey' => 'abc123', 'projectId' => '456']],
            'null project' => [['publicKey' => 'abc123', 'host' => 'o1.ingest.us.sentry.io', 'projectId' => null]],
            'no parts' => [[]],
        ];
    }

    /**
     * @dataProvider notConfiguredProvider
     * @param mixed $dsn The value of Sentry.dsn
     */
    public function testNotConfigured($dsn): void
    {
        Configure::write('Sentry.dsn', $dsn);
        Configure::write('Sentry.options', ['environment' => 'production', 'release' => self::RELEASE]);

        $this->assertNull(SentryConfig::dsn());
        $this->assertNull(SentryConfig::cspReportUri());
    }

    public function testNotConfiguredWithoutSentry(): void
    {
        $this->assertNull(SentryConfig::dsn());
        $this->assertNull(SentryConfig::cspReportUri());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidProvider(): array
    {
        return [
            'invalid string' => ['abc123'],
            'unsupported scheme' => ['ftp://abc123@o1.ingest.us.sentry.io/456'],
            'not a string or an array' => [123],
            'unknown part' => [['publicKey' => 'abc123', 'host' => 'o1.ingest.us.sentry.io', 'project_id' => '456']],
            'array part' => [['publicKey' => ['abc123'], 'host' => 'o1.ingest.us.sentry.io', 'projectId' => '456']],
            'unsupported scheme part' => [['scheme' => 'ftp', 'publicKey' => 'abc123', 'host' => 'o1.ingest.us.sentry.io', 'projectId' => '456']],
            '@ in the public key' => [['publicKey' => 'abc@evil.example.com', 'host' => 'o1.ingest.us.sentry.io', 'projectId' => '456']],
            'path in the host' => [['publicKey' => 'abc123', 'host' => 'evil.example.com/o1.ingest.us.sentry.io', 'projectId' => '456']],
            '/ in the project' => [['publicKey' => 'abc123', 'host' => 'o1.ingest.us.sentry.io', 'projectId' => '456/789']],
        ];
    }

    /**
     * @dataProvider invalidProvider
     * @param mixed $dsn The value of Sentry.dsn
     */
    public function testInvalid($dsn): void
    {
        Configure::write('Sentry.dsn', $dsn);

        $this->expectException(InvalidArgumentException::class);
        SentryConfig::dsn();
    }

    public function testCspReportUri(): void
    {
        Configure::write('Sentry', [
            'dsn' => ['publicKey' => 'abc123', 'host' => 'o1.ingest.us.sentry.io', 'projectId' => '456'],
            'options' => ['environment' => 'production', 'release' => self::RELEASE],
        ]);

        $this->assertSame(
            'https://o1.ingest.us.sentry.io/api/456/security/?sentry_key=abc123&sentry_environment=production&sentry_release=1.2.3',
            SentryConfig::cspReportUri(),
        );
    }

    public function testCspReportUriEncodesEnvironmentAndRelease(): void
    {
        Configure::write('Sentry', [
            'dsn' => self::DSN,
            'options' => ['environment' => 'staging 2', 'release' => 'app@1.2.3+abc'],
        ]);

        $this->assertSame(
            'https://o1.ingest.us.sentry.io/api/456/security/?sentry_key=abc123&sentry_environment=staging%202&sentry_release=app%401.2.3%2Babc',
            SentryConfig::cspReportUri(),
        );
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function cspReportUriOptionsProvider(): array
    {
        $base = 'https://o1.ingest.us.sentry.io/api/456/security/?sentry_key=abc123';

        return [
            'no options' => [[], $base],
            'empty options' => [['environment' => '', 'release' => null], $base],
            'environment only' => [['environment' => 'production'], $base . '&sentry_environment=production'],
            'release only' => [['release' => self::RELEASE], $base . '&sentry_release=1.2.3'],
        ];
    }

    /**
     * @dataProvider cspReportUriOptionsProvider
     * @param array<string, mixed> $options The value of Sentry.options
     * @param string $expected The expected URL
     */
    public function testCspReportUriOmitsEmptyOptions(array $options, string $expected): void
    {
        Configure::write('Sentry', [
            'dsn' => self::DSN,
            'options' => $options,
        ]);

        $this->assertSame($expected, SentryConfig::cspReportUri());
    }
}
