<?php // @codingStandardsIgnoreFile
/**
 * @link      http://github.com/zendframework/zend-xmlrpc for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\XmlRpc;

/**
 * Class for mocking php://input
 *
 * <code>
 * class ...
 * {
 *     public function setUp()
 *     {
 *         ZendTest\XmlRpc\PhpInputMock::mockInput('expected string');
 *     }
 *
 *     public function testReadingFromPhpInput()
 *     {
 *         $this->assertSame('expected string', file_get_contents('php://input'));
 *         $this->assertSame('php://input', ZendTest\XmlRpc\PhpInputMock::getCurrentPath());
 *     }
 *
 *     public function tearDown()
 *     {
 *         ZendTest\XmlRpc\PhpInputMock::restoreDefault();
 *     }
 * }
 * </code>
 */
class PhpInputMock
{
    protected static $data;

    protected static $returnValues = [];

    protected static $arguments = [];

    protected $position = 0;

    public static function mockInput($data)
    {
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', 'ZendTest\XmlRpc\PhpInputMock');
        static::$data = $data;
    }

    public static function restoreDefault()
    {
        // Reset static values
        static::$returnValues = [];
        static::$arguments = [];

        // Restore original stream wrapper
        stream_wrapper_restore('php');
    }

    public static function methodWillReturn($methodName, $returnValue)
    {
        $methodName = strtolower($methodName);
        static::$returnValues[$methodName] = $returnValue;
    }

    public static function argumentsPassedTo($methodName)
    {
        $methodName = strtolower($methodName);
        if (isset(static::$arguments[$methodName])) {
            return static::$arguments[$methodName];
        }

        return;
    }

    public function stream_open()
    {
        static::$arguments[__FUNCTION__] = func_get_args();

        if (array_key_exists(__FUNCTION__, static::$returnValues)) {
            return static::$returnValues[__FUNCTION__];
        }

        return true;
    }

    public function stream_eof()
    {
        static::$arguments[__FUNCTION__] = func_get_args();

        if (array_key_exists(__FUNCTION__, static::$returnValues)) {
            return static::$returnValues[__FUNCTION__];
        }

        return (0 == strlen(static::$data));
    }

    public function stream_read($count)
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

    public function stream_stat()
    {
        static::$arguments[__FUNCTION__] = func_get_args();

        if (array_key_exists(__FUNCTION__, static::$returnValues)) {
            return static::$returnValues[__FUNCTION__];
        }

        return [];
    }
}
