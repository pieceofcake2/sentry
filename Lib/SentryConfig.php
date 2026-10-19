<?php

use Sentry\Dsn;

/**
 * Reads the `Sentry` configuration of the application.
 *
 * `Sentry.dsn` is either a DSN string or its parts:
 *
 * ```
 * 'dsn' => [
 *     'publicKey' => 'abc123',
 *     'host' => 'o123.ingest.us.sentry.io',
 *     'projectId' => '456',
 * ],
 * ```
 */
class SentryConfig
{
    /**
     * The parts of a DSN that must not be empty.
     *
     * @var list<string>
     */
    public const REQUIRED_DSN_PARTS = ['publicKey', 'host', 'projectId'];

    /**
     * The parts of a DSN that may be given.
     *
     * @var list<string>
     */
    public const DSN_PARTS = ['scheme', 'publicKey', 'host', 'projectId'];

    /**
     * The DSN strings that the Sentry SDK treats as "no DSN".
     *
     * @var list<string>
     */
    private const EMPTY_DSN_VALUES = ['', 'false', '(false)', 'empty', '(empty)', 'null', '(null)'];

    /**
     * Returns the DSN of `Sentry.dsn`, or null when Sentry is not configured.
     *
     * Sentry is not configured when `Sentry.dsn` is not set, is empty or `false`, or when one of
     * `publicKey`, `host` and `projectId` of its parts is empty (for example, an environment
     * variable that is not set in development).
     *
     * @return \Sentry\Dsn|null
     * @throws \InvalidArgumentException When `Sentry.dsn` is not a valid DSN.
     */
    public static function dsn(): ?Dsn
    {
        $dsn = Configure::read('Sentry.dsn');
        if ($dsn === null || $dsn === false) {
            return null;
        }
        if (is_string($dsn)) {
            if (in_array(strtolower(trim($dsn)), self::EMPTY_DSN_VALUES, true)) {
                return null;
            }

            return Dsn::createFromString($dsn);
        }
        if (is_array($dsn)) {
            return self::dsnFromParts($dsn);
        }

        throw new InvalidArgumentException(sprintf(
            'Sentry.dsn must be a string or an array of its parts, %s given.',
            get_debug_type($dsn),
        ));
    }

    /**
     * Returns the URL that Content Security Policy violations are reported to (the security
     * endpoint of the Sentry project), or null when Sentry is not configured.
     *
     * `sentry_environment` and `sentry_release` are added from `Sentry.options.environment` and
     * `Sentry.options.release` when they are not empty.
     *
     * @return string|null
     * @throws \InvalidArgumentException When `Sentry.dsn` is not a valid DSN.
     */
    public static function cspReportUri(): ?string
    {
        $dsn = self::dsn();
        if ($dsn === null) {
            return null;
        }

        $query = array_filter([
            'sentry_environment' => (string)Configure::read('Sentry.options.environment'),
            'sentry_release' => (string)Configure::read('Sentry.options.release'),
        ], static fn(string $value): bool => $value !== '');

        $url = $dsn->getCspReportEndpointUrl();
        if ($query !== []) {
            $url .= '&' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }

    /**
     * Builds a DSN from its parts, or returns null when a required part is empty.
     *
     * @param array<mixed> $parts `scheme` (optional, `https` by default), `publicKey`, `host` and `projectId`.
     * @return \Sentry\Dsn|null
     * @throws \InvalidArgumentException When a part is unknown or not a string, or the DSN is invalid.
     */
    private static function dsnFromParts(array $parts): ?Dsn
    {
        $unknown = array_diff(array_keys($parts), self::DSN_PARTS);
        if ($unknown !== []) {
            throw new InvalidArgumentException(sprintf(
                'Sentry.dsn has unknown parts: %s. The parts are %s.',
                implode(', ', $unknown),
                implode(', ', self::DSN_PARTS),
            ));
        }
        foreach ($parts as $name => $value) {
            if ($value !== null && !is_string($value) && !is_int($value)) {
                throw new InvalidArgumentException(sprintf(
                    'Sentry.dsn.%s must be a string, %s given.',
                    $name,
                    get_debug_type($value),
                ));
            }
        }
        foreach (self::REQUIRED_DSN_PARTS as $name) {
            if ((string)($parts[$name] ?? '') === '') {
                return null;
            }
        }

        // These characters would move the rest of the DSN into another part of the URL.
        $reserved = ['publicKey' => '@/?#:', 'host' => '@/?#', 'projectId' => '@/?#:'];
        foreach ($reserved as $name => $characters) {
            if (strpbrk((string)$parts[$name], $characters) !== false) {
                throw new InvalidArgumentException(sprintf(
                    'Sentry.dsn.%s must not contain any of "%s".',
                    $name,
                    $characters,
                ));
            }
        }

        return Dsn::createFromString(sprintf(
            '%s://%s@%s/%s',
            (string)($parts['scheme'] ?? '') ?: 'https',
            $parts['publicKey'],
            $parts['host'],
            $parts['projectId'],
        ));
    }
}
