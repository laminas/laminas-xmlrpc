<?php

/**
 * @see       https://github.com/laminas/laminas-xmlrpc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-xmlrpc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-xmlrpc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\XmlRpc;

/**
 * Class for mocking php://input
 *
 * <code>
 * class ...
 * {
 *     public function setUp()
 *     {
 *         LaminasTest\XmlRpc\PhpInputMock::mockInput('expected string');
 *     }
 *
 *     public function testReadingFromPhpInput()
 *     {
 *         $this->assertSame('expected string', file_get_contents('php://input'));
 *         $this->assertSame('php://input', LaminasTest\XmlRpc\PhpInputMock::getCurrentPath());
 *     }
 *
 *     public function tearDown()
 *     {
 *         LaminasTest\XmlRpc\PhpInputMock::restoreDefault();
 *     }
 * }
 * </code>
 */
class PhpInputMock
{
    protected static $_data;

    protected static $_returnValues = array();

    protected static $_arguments = array();

    protected $_position = 0;

    public static function mockInput($data)
    {
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', 'LaminasTest\XmlRpc\PhpInputMock');
        static::$_data = $data;
    }

    public static function restoreDefault()
    {
        // Reset static values
        static::$_returnValues = array();
        static::$_arguments = array();

        // Restore original stream wrapper
        stream_wrapper_restore('php');
    }

    public static function methodWillReturn($methodName, $returnValue)
    {
        $methodName = strtolower($methodName);
        static::$_returnValues[$methodName] = $returnValue;
    }

    public static function argumentsPassedTo($methodName)
    {
        $methodName = strtolower($methodName);
        if (isset(static::$_arguments[$methodName])) {
            return static::$_arguments[$methodName];
        }

        return;
    }

    public function stream_open()
    {
        static::$_arguments[__FUNCTION__] = func_get_args();

        if (array_key_exists(__FUNCTION__, static::$_returnValues)) {
            return static::$_returnValues[__FUNCTION__];
        }

        return true;
    }

    public function stream_eof()
    {
        static::$_arguments[__FUNCTION__] = func_get_args();

        if (array_key_exists(__FUNCTION__, static::$_returnValues)) {
            return static::$_returnValues[__FUNCTION__];
        }

        return (0 == strlen(static::$_data));
    }

    public function stream_read($count)
    {
        static::$_arguments[__FUNCTION__] = func_get_args();

        if (array_key_exists(__FUNCTION__, static::$_returnValues)) {
            return static::$_returnValues[__FUNCTION__];
        }

        // To match the behavior of php://input, we need to clear out the data
        // as it is read
        if ($count > strlen(static::$_data)) {
            $data = static::$_data;
            static::$_data = '';
        } else {
            $data = substr(static::$_data, 0, $count);
            static::$_data = substr(static::$_data, $count);
        }
        return $data;
    }

    public function stream_stat()
    {
        static::$_arguments[__FUNCTION__] = func_get_args();

        if (array_key_exists(__FUNCTION__, static::$_returnValues)) {
            return static::$_returnValues[__FUNCTION__];
        }

        return array();
    }
}
