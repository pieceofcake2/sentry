<?php

use PieceOfCake2\Sentry\Error\SentryErrorHandler;

class BootstrapTest extends CakeTestCase
{
    /**
     * The bootstrap of the plugin gives the global name of the error handler to older applications.
     */
    public function testAliasesTheErrorHandler(): void
    {
        CakePlugin::load('Sentry', ['path' => dirname(__DIR__, 3) . DS, 'bootstrap' => true]);
        // Loading it again does not declare the alias twice.
        CakePlugin::bootstrap('Sentry');

        $this->assertTrue(class_exists('SentryErrorHandler', false));
        $this->assertSame(SentryErrorHandler::class, (new ReflectionClass('SentryErrorHandler'))->getName());
        $this->assertTrue(is_callable('SentryErrorHandler::handleError'));
        $this->assertTrue(is_callable('SentryErrorHandler::handleException'));
    }
}
