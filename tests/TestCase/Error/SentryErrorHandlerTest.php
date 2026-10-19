<?php

use PieceOfCake2\Sentry\Error\SentryErrorHandler;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;

class SentryErrorHandlerTestRenderer
{
    public static array $rendered = [];

    public function __construct(private Throwable $exception)
    {
    }

    public function render(): void
    {
        static::$rendered[] = $this->exception;
    }
}

class SentryErrorHandlerTest extends CakeTestCase
{
    private const DSN = 'https://public@sentry.example.com/1';

    private const MESSAGE = 'Something happened';

    private HubInterface $originalHub;

    /**
     * @var \Sentry\State\HubInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $hub;

    public function setUp(): void
    {
        parent::setUp();
        $this->originalHub = SentrySdk::getCurrentHub();
        $this->setInitialized(false);

        Configure::write('Sentry', [
            'dsn' => self::DSN,
            'options' => [
                'environment' => 'testing',
                'release' => '1.2.3',
            ],
            'ignoredExceptions' => [NotFoundException::class],
        ]);
        Configure::write('Exception', [
            'handler' => [SentryErrorHandler::class, 'handleException'],
            'renderer' => 'SentryErrorHandlerTestRenderer',
            'log' => false,
        ]);
        SentryErrorHandlerTestRenderer::$rendered = [];
    }

    public function tearDown(): void
    {
        SentrySdk::setCurrentHub($this->originalHub);
        $this->setInitialized(true);
        Configure::delete('Sentry');
        parent::tearDown();
    }

    private function setInitialized(bool $initialized): void
    {
        $property = new ReflectionProperty(SentryErrorHandler::class, 'initialized');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        $property->setValue(null, $initialized);
    }

    /**
     * Initialize the handler, then replace the Sentry hub with a mock.
     */
    private function mockHub(): void
    {
        SentryErrorHandler::init();
        $this->hub = $this->createMock(HubInterface::class);
        SentrySdk::setCurrentHub($this->hub);
    }

    public function testInit(): void
    {
        SentryErrorHandler::init();

        $options = SentrySdk::getCurrentHub()->getClient()->getOptions();
        $this->assertSame(self::DSN, (string)$options->getDsn());
        $this->assertSame('testing', $options->getEnvironment());
        $this->assertSame('1.2.3', $options->getRelease());
    }

    public function testInitWithDsnParts(): void
    {
        Configure::write('Sentry.dsn', [
            'publicKey' => 'public',
            'host' => 'sentry.example.com',
            'projectId' => '1',
        ]);

        SentryErrorHandler::init();

        $options = SentrySdk::getCurrentHub()->getClient()->getOptions();
        $this->assertSame(self::DSN, (string)$options->getDsn());
    }

    public function testInitWithoutDsn(): void
    {
        Configure::write('Sentry.dsn', [
            'publicKey' => '',
            'host' => 'sentry.example.com',
            'projectId' => '1',
        ]);

        SentryErrorHandler::init();

        $this->assertNull(SentrySdk::getCurrentHub()->getClient()->getOptions()->getDsn());
    }

    public function testInitOnlyOnce(): void
    {
        SentryErrorHandler::init();
        $hub = SentrySdk::getCurrentHub();

        Configure::write('Sentry.options.environment', 'other');
        SentryErrorHandler::init();

        $this->assertSame($hub, SentrySdk::getCurrentHub());
        $this->assertSame('testing', SentrySdk::getCurrentHub()->getClient()->getOptions()->getEnvironment());
    }

    public function testInitWithoutOptions(): void
    {
        Configure::write('Sentry', ['dsn' => self::DSN]);
        $this->mockHub();
        $exception = new NotFoundException();
        $this->hub->expects($this->once())
            ->method('captureException')
            ->with($this->identicalTo($exception));

        SentryErrorHandler::handleException($exception);
    }

    public function testHandleException(): void
    {
        $this->mockHub();
        $exception = new InternalErrorException('Something went wrong');
        $this->hub->expects($this->once())
            ->method('captureException')
            ->with($this->identicalTo($exception));

        SentryErrorHandler::handleException($exception);

        $this->assertSame([$exception], SentryErrorHandlerTestRenderer::$rendered);
    }

    public function testHandleExceptionWithError(): void
    {
        $this->mockHub();
        $error = new TypeError('Wrong type');
        $this->hub->expects($this->once())
            ->method('captureException')
            ->with($this->identicalTo($error));

        SentryErrorHandler::handleException($error);

        $this->assertSame([$error], SentryErrorHandlerTestRenderer::$rendered);
    }

    public function testHandleIgnoredException(): void
    {
        $this->mockHub();
        $this->hub->expects($this->never())->method('captureException');

        SentryErrorHandler::handleException(new NotFoundException());

        $this->assertCount(1, SentryErrorHandlerTestRenderer::$rendered);
    }

    public function testHandleExceptionIgnoresSubclasses(): void
    {
        Configure::write('Sentry.ignoredExceptions', [HttpException::class]);
        $this->mockHub();
        $this->hub->expects($this->never())->method('captureException');

        SentryErrorHandler::handleException(new ForbiddenException());
    }

    public function testHandleError(): void
    {
        Configure::write('debug', 0);
        $this->mockHub();
        $this->hub->expects($this->once())
            ->method('captureException')
            ->with($this->callback(function ($exception) {
                return $exception instanceof ErrorException
                    && $exception->getMessage() === self::MESSAGE
                    && $exception->getSeverity() === E_USER_WARNING
                    && $exception->getFile() === __FILE__
                    && $exception->getLine() === 123;
            }));

        $this->assertTrue(SentryErrorHandler::handleError(E_USER_WARNING, self::MESSAGE, __FILE__, 123));
    }

    public function testHandleErrorDelegatesToCakePhp(): void
    {
        Configure::write('debug', 2);
        $format = Debugger::outputAs();
        Debugger::outputAs('txt');
        $this->mockHub();
        $this->hub->expects($this->once())->method('captureException');

        ob_start();
        try {
            SentryErrorHandler::handleError(E_USER_NOTICE, self::MESSAGE, __FILE__, 123);
        } finally {
            $output = ob_get_clean();
            Debugger::outputAs($format);
        }

        $this->assertStringContainsString(self::MESSAGE, $output);
    }

    public function testHandleErrorNotReportedWhenSuppressed(): void
    {
        $this->mockHub();
        $this->hub->expects($this->never())->method('captureException');

        $level = error_reporting(0);
        try {
            $this->assertTrue(SentryErrorHandler::handleError(E_USER_WARNING, 'Suppressed', __FILE__, 123));
        } finally {
            error_reporting($level);
        }
    }

    public function testHandleShutdownWithoutFatalError(): void
    {
        $this->mockHub();
        $this->hub->expects($this->never())->method('captureException');

        error_clear_last();
        SentryErrorHandler::handleShutdown();
    }
}
