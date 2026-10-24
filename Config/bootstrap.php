<?php

/**
 * Backward compatibility for applications that use the global class name of the error handler of 1.x
 * (`'SentryErrorHandler::handleError'`).
 *
 * It is loaded only with `CakePlugin::load('Sentry', ['bootstrap' => true])`. New applications use
 * `PieceOfCake2\Sentry\Error\SentryErrorHandler` and do not need it.
 */

use PieceOfCake2\Sentry\Error\SentryErrorHandler;

if (!class_exists('SentryErrorHandler', false)) {
    class_alias(SentryErrorHandler::class, 'SentryErrorHandler');
}
