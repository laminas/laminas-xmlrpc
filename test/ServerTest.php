<?php

namespace LaminasTest\XmlRpc;

use Laminas\Server\Definition as ServerDefinition;
use Laminas\Server\Exception\InvalidArgumentException as ServerInvalidArgumentException;
use Laminas\Server\Method\Definition as MethodDefinition;
use Laminas\XmlRpc\AbstractValue;
use Laminas\XmlRpc\Exception\ExceptionInterface;
use Laminas\XmlRpc\Exception\InvalidArgumentException;
use Laminas\XmlRpc\Fault;
use Laminas\XmlRpc\Request;
use Laminas\XmlRpc\Response;
use Laminas\XmlRpc\Server;
use PHPUnit\Framework\TestCase;
use stdClass;

use function base64_encode;
use function count;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function strstr;
use function var_export;

/**
 * @group      Laminas_XmlRpc
 */
class ServerTest extends TestCase
{
    /**
     * Server object
     *
     * @var Server
     */
    protected $server;

    /**
     * Setup environment
     */
    protected function setUp(): void
    {
        $this->server = new Server();
        $this->server->setReturnResponse(true);
    }

    /**
     * Teardown environment
     */
    protected function tearDown(): void
    {
        unset($this->server);
    }

    /**
     * @param string $errno
     * @param string $errstr
     * @return bool|void
     */
    public function suppressNotFoundWarnings($errno, $errstr)
    {
        if (! strstr($errstr, 'failed')) {
            return false;
        }
    }

    /**
     * addFunction() test
     *
     * Call as method call
     *
     * Expects:
     * - function:
     * - namespace: Optional; has default;
     *
     * Returns: void
     */
    public function testAddFunction()
    {
        $this->server->addFunction('LaminasTest\\XmlRpc\\TestAsset\\testFunction', 'zsr');

        $methods = $this->server->listMethods();
        $this->assertContains('zsr.LaminasTest\\XmlRpc\\TestAsset\\testFunction', $methods, var_export($methods, 1));

        $methods = $this->server->listMethods();
        $this->assertContains('zsr.LaminasTest\\XmlRpc\\TestAsset\\testFunction', $methods);
        $this->assertNotContains(
            'zsr.LaminasTest\\XmlRpc\\TestAsset\\testFunction2',
            $methods,
            var_export($methods, 1)
        );
    }

    public function testAddFunctionThrowsExceptionOnInvalidInput()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to attach function; invalid');
        $this->server->addFunction('nosuchfunction');
    }

    /**
     * getReturnResponse() default value
     */
    public function testEmitResponseByDefault()
    {
        $server = new Server();

        $this->assertFalse($server->getReturnResponse());
    }

    /**
     * get/loadFunctions() test
     */
    public function testFunctions()
    {
        $expected = $this->server->listMethods();

        $functions = $this->server->getFunctions();
        $server    = new Server();
        $server->loadFunctions($functions);
        $actual = $server->listMethods();

        $this->assertSame($expected, $actual);
    }

    /**
     * setClass() test
     */
    public function testSetClass()
    {
        $this->server->setClass(TestAsset\TestClass::class, 'test');
        $methods = $this->server->listMethods();
        $this->assertContains('test.test1', $methods);
        $this->assertContains('test.test2', $methods);
        $this->assertNotContains('test.test3', $methods);
        $this->assertNotContains('test.__construct', $methods);
    }

    /**
     * @group Laminas-6526
     */
    public function testSettingClassWithArguments()
    {
        $this->server->setClass(TestAsset\TestClass::class, 'test', 'argv-argument');
        $this->assertTrue($this->server->sendArgumentsToAllMethods());
        $request = new Request();
        $request->setMethod('test.test4');
        $response = $this->server->handle($request);
        $this->assertNotInstanceOf(Fault::class, $response);
        $this->assertSame([
            'test1' => 'argv-argument',
            'test2' => null,
            'arg'   => ['argv-argument'],
        ], $response->getReturnValue());
    }

    public function testSettingClassWithArgumentsOnlyPassingToConstructor()
    {
        $this->server->setClass(TestAsset\TestClass::class, 'test', 'a1', 'a2');
        $this->server->sendArgumentsToAllMethods(false);
        $this->assertFalse($this->server->sendArgumentsToAllMethods());

        $request = new Request();
        $request->setMethod('test.test4');
        $request->setParams(['foo']);
        $response = $this->server->handle($request);
        $this->assertNotInstanceOf(Fault::class, $response);
        $this->assertSame(['test1' => 'a1', 'test2' => 'a2', 'arg' => ['foo']], $response->getReturnValue());
    }

    /**
     * fault() test
     */
    public function testFault()
    {
        $fault = $this->server->fault('This is a fault', 411);
        $this->assertInstanceOf(\Laminas\XmlRpc\Server\Fault::class, $fault);
        $this->assertEquals(411, $fault->getCode());
        $this->assertEquals('This is a fault', $fault->getMessage());

        $fault = $this->server->fault(new Server\Exception\RuntimeException('Exception fault', 511));
        $this->assertInstanceOf(\Laminas\XmlRpc\Server\Fault::class, $fault);
        $this->assertEquals(511, $fault->getCode());
        $this->assertEquals('Exception fault', $fault->getMessage());
    }

    /**
     * handle() test - default behavior should be to not return the response
     */
    public function testHandle()
    {
        $request = new Request();
        $request->setMethod('system.listMethods');
        $this->server->setReturnResponse(false);
        ob_start();
        $response = $this->server->handle($request);
        $output   = ob_get_contents();
        ob_end_clean();
        $this->server->setReturnResponse(true);

        $this->assertFalse(isset($response));
        $response = $this->server->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame($response->__toString(), $output);
        $return = $response->getReturnValue();
        $this->assertIsArray($return);
        $this->assertContains('system.multicall', $return);
    }

    /**
     * handle() test
     *
     * Call as method call
     *
     * Expects:
     * - request: Optional;
     *
     * Returns: Laminas_XmlRpc_Response|Laminas_XmlRpc_Fault
     */
    public function testHandleWithReturnResponse()
    {
        $request = new Request();
        $request->setMethod('system.listMethods');
        $response = $this->server->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $return = $response->getReturnValue();
        $this->assertIsArray($return);
        $this->assertContains('system.multicall', $return);
    }

    /**
     * Test that only calling methods using a valid parameter signature works
     */
    public function testHandle2()
    {
        $request = new Request();
        $request->setMethod('system.methodHelp');
        $response = $this->server->handle($request);

        $this->assertInstanceOf(Fault::class, $response);
        $this->assertEquals(623, $response->getCode());
    }

    public function testCallingInvalidMethod()
    {
        $request = new Request();
        $request->setMethod('invalid');
        $response = $this->server->handle($request);
        $this->assertInstanceOf(Fault::class, $response);
        $this->assertSame('Method "invalid" does not exist', $response->getMessage());
        $this->assertSame(620, $response->getCode());
    }

    /**
     * setResponseClass() test
     *
     * Call as method call
     *
     * Expects:
     * - class:
     *
     * Returns: bool
     */
    public function testSetResponseClass()
    {
        $this->assertTrue($this->server->setResponseClass(TestAsset\TestResponse::class));
        $request = new Request();
        $request->setMethod('system.listMethods');
        $response = $this->server->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf(TestAsset\TestResponse::class, $response);
    }

    /**
     * listMethods() test
     *
     * Call as method call
     *
     * Returns: array
     */
    public function testListMethods()
    {
        $methods = $this->server->listMethods();
        $this->assertIsArray($methods);
        $this->assertContains('system.listMethods', $methods);
        $this->assertContains('system.methodHelp', $methods);
        $this->assertContains('system.methodSignature', $methods);
        $this->assertContains('system.multicall', $methods);
    }

    /**
     * methodHelp() test
     *
     * Call as method call
     *
     * Expects:
     * - method:
     *
     * Returns: string
     */
    public function testMethodHelp()
    {
        $help = $this->server->methodHelp('system.methodHelp', 'system.listMethods');
        $this->assertStringContainsString('Display help message for an XMLRPC method', $help);

        $this->expectException(ExceptionInterface::class);
        $this->expectExceptionMessage('Method "foo" does not exist');
        $this->server->methodHelp('foo');
    }

    /**
     * methodSignature() test
     *
     * Call as method call
     *
     * Expects:
     * - method:
     *
     * Returns: array
     */
    public function testMethodSignature()
    {
        $sig = $this->server->methodSignature('system.methodSignature');
        $this->assertIsArray($sig);
        $this->assertEquals(1, count($sig), var_export($sig, 1));

        $this->expectException(ExceptionInterface::class);
        $this->expectExceptionMessage('Method "foo" does not exist');
        $this->server->methodSignature('foo');
    }

    /**
     * multicall() test
     *
     * Call as method call
     *
     * Expects:
     * - methods:
     *
     * Returns: array
     */
    public function testMulticall()
    {
        $struct  = [
            [
                'methodName' => 'system.listMethods',
                'params'     => [],
            ],
            [
                'methodName' => 'system.methodHelp',
                'params'     => ['system.multicall'],
            ],
        ];
        $request = new Request();
        $request->setMethod('system.multicall');
        $request->addParam($struct);
        $response = $this->server->handle($request);

        $this->assertInstanceOf(
            Response::class,
            $response,
            $response->__toString() . "\n\n" . $request->__toString()
        );
        $returns = $response->getReturnValue();
        $this->assertIsArray($returns);
        $this->assertEquals(2, count($returns), var_export($returns, 1));
        $this->assertIsArray($returns[0], var_export($returns[0], 1));
        $this->assertIsString($returns[1], var_export($returns[1], 1));
    }

    /**
     * @group Laminas-5635
     */
    public function testMulticallHandlesFaults()
    {
        $struct  = [
            [
                'methodName' => 'system.listMethods',
                'params'     => [],
            ],
            [
                'methodName' => 'undefined',
                'params'     => [],
            ],
        ];
        $request = new Request();
        $request->setMethod('system.multicall');
        $request->addParam($struct);
        $response = $this->server->handle($request);

        $this->assertInstanceOf(
            Response::class,
            $response,
            $response->__toString() . "\n\n" . $request->__toString()
        );
        $returns = $response->getReturnValue();
        $this->assertIsArray($returns);
        $this->assertEquals(2, count($returns), var_export($returns, 1));
        $this->assertIsArray($returns[0], var_export($returns[0], 1));
        $this->assertSame([
            'faultCode'   => 620,
            'faultString' => 'Method "undefined" does not exist',
        ], $returns[1], var_export($returns[1], 1));
    }

    /**
     * Test get/setEncoding()
     */
    public function testGetSetEncoding()
    {
        $this->assertEquals('UTF-8', $this->server->getEncoding());
        $this->assertEquals('UTF-8', AbstractValue::getGenerator()->getEncoding());
        $this->assertSame($this->server, $this->server->setEncoding('ISO-8859-1'));
        $this->assertEquals('ISO-8859-1', $this->server->getEncoding());
        $this->assertEquals('ISO-8859-1', AbstractValue::getGenerator()->getEncoding());
    }

    /**
     * Test request/response encoding
     */
    public function testRequestResponseEncoding()
    {
        $response = $this->server->handle();
        $request  = $this->server->getRequest();

        $this->assertEquals('UTF-8', $request->getEncoding());
        $this->assertEquals('UTF-8', $response->getEncoding());
    }

    /**
     * Test request/response encoding (alternate encoding)
     */
    public function testRequestResponseEncoding2()
    {
        $this->server->setEncoding('ISO-8859-1');
        $response = $this->server->handle();
        $request  = $this->server->getRequest();

        $this->assertEquals('ISO-8859-1', $request->getEncoding());
        $this->assertEquals('ISO-8859-1', $response->getEncoding());
    }

    public function testAddFunctionWithExtraArgs()
    {
        $this->server->addFunction('LaminasTest\\XmlRpc\\TestAsset\\testFunction', 'test', 'arg1');
        $methods = $this->server->listMethods();
        $this->assertContains('test.LaminasTest\\XmlRpc\\TestAsset\\testFunction', $methods);
    }

    public function testAddFunctionThrowsExceptionWithBadData()
    {
        $o = new stdClass();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to attach function; invalid');
        $this->server->addFunction($o);
    }

    public function testLoadFunctionsThrowsExceptionWithBadData()
    {
        $o = new stdClass();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unable to load server definition; must be an array or Laminas\Server\Definition, received stdClass'
        );
        $this->server->loadFunctions($o);
    }

    public function testLoadFunctionsThrowsExceptionsWithBadData2()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unable to load server definition; must be an array or Laminas\Server\Definition, received string'
        );
        $this->server->loadFunctions('foo');
    }

    public function testLoadFunctionsThrowsExceptionsWithBadData3()
    {
        $o = new stdClass();
        $o = [$o];

        $this->expectException(ServerInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid method provided');
        $this->server->loadFunctions($o);
    }

    public function testLoadFunctionsReadsMethodsFromServerDefinitionObjects()
    {
        $mockedMethod     = $this->getMockBuilder(MethodDefinition::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $mockedDefinition = $this->getMockBuilder(ServerDefinition::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $mockedDefinition
            ->expects($this->once())
            ->method('getMethods')
            ->will($this->returnValue(['bar' => $mockedMethod]));
        $this->server->loadFunctions($mockedDefinition);
    }

    public function testSetClassThrowsExceptionWithInvalidClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid method class');
        $this->server->setClass('mybogusclass');
    }

    public function testSetRequestUsingString()
    {
        $this->server->setRequest(TestAsset\TestRequest::class);
        $req = $this->server->getRequest();
        $this->assertInstanceOf(TestAsset\TestRequest::class, $req);
    }

    /**
     * ////////@outputBuffering enabled
     */
    public function testSetRequestThrowsExceptionOnBadClassName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid request object');
        $this->server->setRequest('LaminasTest\\XmlRpc\\TestRequest2');
    }

    public function testSetRequestThrowsExceptionOnBadObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid request object');
        $this->server->setRequest($this);
    }

    public function testHandleObjectMethod()
    {
        $this->server->setClass(TestAsset\TestClass::class);
        $request = new Request();
        $request->setMethod('test1');
        $request->addParam('value');
        $response = $this->server->handle($request);
        $this->assertNotInstanceOf(Fault::class, $response);
        $this->assertEquals('String: value', $response->getReturnValue());
    }

    public function testHandleClassStaticMethod()
    {
        $this->server->setClass(TestAsset\TestClass::class);
        $request = new Request();
        $request->setMethod('test2');
        $request->addParam(['value1', 'value2']);
        $response = $this->server->handle($request);
        $this->assertNotInstanceOf(Fault::class, $response);
        $this->assertEquals('value1; value2', $response->getReturnValue());
    }

    public function testHandleFunction()
    {
        $this->server->addFunction('LaminasTest\\XmlRpc\\TestAsset\\testFunction');
        $request = new Request();
        $request->setMethod('LaminasTest\\XmlRpc\\TestAsset\\testFunction');
        $request->setParams([['value1'], 'key']);
        $response = $this->server->handle($request);
        $this->assertNotInstanceOf(Fault::class, $response);
        $this->assertEquals('key: value1', $response->getReturnValue());
    }

    public function testMulticallReturnsFaultsWithBadData()
    {
        // bad method array
        $try      = [
            'system.listMethods',
            [
                'name' => 'system.listMethods',
            ],
            [
                'methodName' => 'system.listMethods',
            ],
            [
                'methodName' => 'system.listMethods',
                'params'     => '',
            ],
            [
                'methodName' => 'system.multicall',
                'params'     => [],
            ],
        ];
        $returned = $this->server->multicall($try);
        $this->assertIsArray($returned);
        $this->assertEquals(5, count($returned));

        $response = $returned[0];
        $this->assertIsArray($response);
        $this->assertTrue(isset($response['faultCode']));
        $this->assertEquals(601, $response['faultCode']);

        $response = $returned[1];
        $this->assertIsArray($response);
        $this->assertTrue(isset($response['faultCode']));
        $this->assertEquals(602, $response['faultCode']);

        $response = $returned[2];
        $this->assertIsArray($response);
        $this->assertTrue(isset($response['faultCode']));
        $this->assertEquals(603, $response['faultCode']);

        $response = $returned[3];
        $this->assertIsArray($response);
        $this->assertTrue(isset($response['faultCode']));
        $this->assertEquals(604, $response['faultCode']);

        $response = $returned[4];
        $this->assertIsArray($response);
        $this->assertTrue(isset($response['faultCode']));
        $this->assertEquals(605, $response['faultCode']);
    }

    /**
     * @group Laminas-2872
     */
    public function testCanMarshalBase64Requests()
    {
        $this->server->setClass(TestAsset\TestClass::class, 'test');
        $data    = base64_encode('this is the payload');
        $param   = ['type' => 'base64', 'value' => $data];
        $request = new Request('test.base64', [$param]);

        $response = $this->server->handle($request);
        $this->assertNotInstanceOf(Fault::class, $response);
        $this->assertEquals($data, $response->getReturnValue());
    }

    /**
     * @group Laminas-6034
     */
    public function testPrototypeReturnValueMustReflectDocBlock()
    {
        $server = new Server();
        $server->setClass(TestAsset\TestClass::class);
        $table  = $server->getDispatchTable();
        $method = $table->getMethod('test1');
        foreach ($method->getPrototypes() as $prototype) {
            $this->assertNotEquals('void', $prototype->getReturnType(), var_export($prototype, 1));
        }
    }

    public function testCallingUnregisteredMethod()
    {
        $this->expectException(ExceptionInterface::class);
        $this->expectExceptionMessage('Unknown instance method called on server: foobarbaz');
        $this->server->foobarbaz();
    }

    public function testSetPersistenceDoesNothing()
    {
        $this->assertNull($this->server->setPersistence('foo'));
        $this->assertNull($this->server->setPersistence('whatever'));
    }

    public function testPassingInvalidRequestClassThrowsException()
    {
        $this->expectException(ExceptionInterface::class);
        $this->expectExceptionMessage('Invalid request class');
        $this->server->setRequest('stdClass');
    }

    public function testPassingInvalidResponseClassThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid response class');
        $this->server->setResponseClass('stdClass');
    }

    public function testCreatingFaultWithEmptyMessageResultsInUnknownError()
    {
        $fault = $this->server->fault('', 123);
        $this->assertSame('Unknown Error', $fault->getMessage());
        $this->assertSame(123, $fault->getCode());
    }
}
