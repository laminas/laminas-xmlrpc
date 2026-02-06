<?php // @codingStandardsIgnoreFile

namespace LaminasTest\XmlRpc;

/**
 * Class for mocking php://input
 *
 * <code>
 * class ...
 * {
 *     protected function setUp(): void
 *     {
 *         LaminasTest\XmlRpc\PhpInputMock::mockInput('expected string');
 *     }
 *
 *     public function testReadingFromPhpInput(): void
 *     {
 *         $this->assertSame('expected string', file_get_contents('php://input'));
 *         $this->assertSame('php://input', LaminasTest\XmlRpc\PhpInputMock::getCurrentPath());
 *     }
 *
 *     protected function tearDown(): void
 *     {
 *         LaminasTest\XmlRpc\PhpInputMock::restoreDefault();
 *     }
 * }
 * </code>
 */
final class PhpInputMock
{
    protected static mixed $data;

    protected static array $returnValues = [];

    protected static array $arguments = [];

    protected int $position = 0;

    public static function mockInput(mixed $data): void
    {
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', \LaminasTest\XmlRpc\PhpInputMock::class);
        static::$data = $data;
    }

    public static function restoreDefault(): void
    {
        // Reset static values
        static::$returnValues = [];
        static::$arguments = [];

        // Restore original stream wrapper
        stream_wrapper_restore('php');
    }

    public static function methodWillReturn(string $methodName, bool $returnValue): void
    {
        $methodName = strtolower($methodName);
        static::$returnValues[$methodName] = $returnValue;
    }

    public static function argumentsPassedTo(string $methodName)
    {
        $methodName = strtolower($methodName);
        if (isset(static::$arguments[$methodName])) {
            return static::$arguments[$methodName];
        }

        return;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function stream_open(): bool
    {
        static::$arguments[__FUNCTION__] = func_get_args();

        if (array_key_exists(__FUNCTION__, static::$returnValues)) {
            return static::$returnValues[__FUNCTION__];
        }

        return true;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function stream_eof()
    {
        static::$arguments[__FUNCTION__] = func_get_args();

        if (array_key_exists(__FUNCTION__, static::$returnValues)) {
            return static::$returnValues[__FUNCTION__];
        }

        return (0 === strlen(static::$data));
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function stream_read($count): mixed
    {
        static::$arguments[__FUNCTION__] = func_get_args();

        if (array_key_exists(__FUNCTION__, static::$returnValues)) {
            return static::$returnValues[__FUNCTION__];
        }

        // To match the behavior of php://input, we need to clear out the data
        // as it is read
        if ($count > strlen(static::$data)) {
            $data = static::$data;
            static::$data = '';
        } else {
            $data = substr(static::$data, 0, $count);
            static::$data = substr(static::$data, $count);
        }
        return $data;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function stream_stat(): array
    {
        static::$arguments[__FUNCTION__] = func_get_args();

        if (array_key_exists(__FUNCTION__, static::$returnValues)) {
            return static::$returnValues[__FUNCTION__];
        }

        return [];
    }
}
