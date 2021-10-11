<?php

/**
 * @see       https://github.com/laminas/laminas-xmlrpc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-xmlrpc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-xmlrpc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\XmlRpc;

use Laminas\Http\Client as HttpClient;
use Laminas\Http\Client\Adapter;
use Laminas\Http\Client\Adapter\AdapterInterface;
use Laminas\Http\Response as HttpResponse;
use Laminas\XmlRpc\AbstractValue;
use Laminas\XmlRpc\Client;
use Laminas\XmlRpc\Client\ServerIntrospection;
use Laminas\XmlRpc\Client\ServerProxy;
use Laminas\XmlRpc\Fault;
use Laminas\XmlRpc\Request;
use Laminas\XmlRpc\Response;
use Laminas\XmlRpc\Value;
use PHPUnit\Framework\TestCase;

use function count;
use function dirname;
use function file_get_contents;
use function implode;
use function strlen;
use function time;

/**
 * @group      Laminas_XmlRpc
 */
class ClientTest extends TestCase
{
    /** @var AdapterInterface */
    protected $httpAdapter;

    /** @var HttpClient */
    protected $httpClient;

    /** @var Client */
    protected $xmlrpcClient;

    protected function setUp(): void
    {
        $this->httpAdapter = new Adapter\Test();
        $this->httpClient  = new HttpClient(
            'http://foo',
            ['adapter' => $this->httpAdapter]
        );

        $this->xmlrpcClient = new Client('http://foo');
        $this->xmlrpcClient->setHttpClient($this->httpClient);
    }

    public function testGettingDefaultHttpClient()
    {
        $xmlrpcClient = new Client('http://foo');
        $httpClient   = $xmlrpcClient->getHttpClient();
        $this->assertInstanceOf(HttpClient::class, $httpClient);
        $this->assertSame($httpClient, $xmlrpcClient->getHttpClient());
    }

    public function testSettingAndGettingHttpClient()
    {
        $xmlrpcClient = new Client('http://foo');
        $httpClient   = new HttpClient('http://foo');
        $this->assertNotSame($httpClient, $xmlrpcClient->getHttpClient());

        $xmlrpcClient->setHttpClient($httpClient);
        $this->assertSame($httpClient, $xmlrpcClient->getHttpClient());
    }

    public function testSettingHttpClientViaConstructor()
    {
        $xmlrpcClient = new Client('http://foo', $this->httpClient);
        $httpClient   = $xmlrpcClient->getHttpClient();
        $this->assertSame($this->httpClient, $httpClient);
    }

    public function testLastRequestAndResponseAreInitiallyNull()
    {
        $this->assertNull($this->xmlrpcClient->getLastRequest());
        $this->assertNull($this->xmlrpcClient->getLastResponse());
    }

    public function testLastRequestAndResponseAreSetAfterRpcMethodCall()
    {
        $this->setServerResponseTo(true);
        $this->xmlrpcClient->call('foo');

        $this->assertInstanceOf(Request::class, $this->xmlrpcClient->getLastRequest());
        $this->assertInstanceOf(Response::class, $this->xmlrpcClient->getLastResponse());
    }

    public function testSuccessfulRpcMethodCallWithNoParameters()
    {
        $expectedMethod = 'foo.bar';
        $expectedReturn = 7;

        $this->setServerResponseTo($expectedReturn);
        $this->assertSame($expectedReturn, $this->xmlrpcClient->call($expectedMethod));

        $request  = $this->xmlrpcClient->getLastRequest();
        $response = $this->xmlrpcClient->getLastResponse();

        $this->assertSame($expectedMethod, $request->getMethod());
        $this->assertSame([], $request->getParams());
        $this->assertSame($expectedReturn, $response->getReturnValue());
        $this->assertFalse($response->isFault());
    }

    public function testSuccessfulRpcMethodCallWithParameters()
    {
        $expectedMethod = 'foo.bar';
        $expectedParams = [1, 'foo' => 'bar', 1.1, true];
        $expectedReturn = [7, false, 'foo' => 'bar'];

        $this->setServerResponseTo($expectedReturn);

        $actualReturn = $this->xmlrpcClient->call($expectedMethod, $expectedParams);
        $this->assertSame($expectedReturn, $actualReturn);

        $request  = $this->xmlrpcClient->getLastRequest();
        $response = $this->xmlrpcClient->getLastResponse();

        $this->assertSame($expectedMethod, $request->getMethod());
        $params = $request->getParams();
        $this->assertSame(count($expectedParams), count($params));
        $this->assertSame($expectedParams[0], $params[0]->getValue());
        $this->assertSame($expectedParams[1], $params[1]->getValue());
        $this->assertSame($expectedParams[2], $params[2]->getValue());
        $this->assertSame($expectedParams['foo'], $params['foo']->getValue());

        $this->assertSame($expectedReturn, $response->getReturnValue());
        $this->assertFalse($response->isFault());
    }

    /**
     * @group Laminas-2090
     */
    public function testSuccessfullyDetectsEmptyArrayParameterAsArray()
    {
        $expectedMethod = 'foo.bar';
        $expectedParams = [[]];
        $expectedReturn = [true];

        $this->setServerResponseTo($expectedReturn);

        $actualReturn = $this->xmlrpcClient->call($expectedMethod, $expectedParams);
        $this->assertSame($expectedReturn, $actualReturn);

        $request = $this->xmlrpcClient->getLastRequest();

        $params = $request->getParams();
        $this->assertSame(count($expectedParams), count($params));
        $this->assertSame($expectedParams[0], $params[0]->getValue());
    }

    /**
     * @group Laminas-1412
     */
    public function testSuccessfulRpcMethodCallWithMixedDateParameters()
    {
        $time           = time();
        $expectedMethod = 'foo.bar';
        $expectedParams = [
            'username',
            new Value\DateTime($time),
        ];
        $expectedReturn = ['username', $time];

        $this->setServerResponseTo($expectedReturn);

        $actualReturn = $this->xmlrpcClient->call($expectedMethod, $expectedParams);
        $this->assertSame($expectedReturn, $actualReturn);

        $request  = $this->xmlrpcClient->getLastRequest();
        $response = $this->xmlrpcClient->getLastResponse();

        $this->assertSame($expectedMethod, $request->getMethod());
        $params = $request->getParams();
        $this->assertSame(count($expectedParams), count($params));
        $this->assertSame($expectedParams[0], $params[0]->getValue());
        $this->assertSame($expectedParams[1], $params[1]);
        $this->assertSame($expectedReturn, $response->getReturnValue());
        $this->assertFalse($response->isFault());
    }

    /**
     * @group Laminas-1797
     */
    public function testSuccesfulRpcMethodCallWithXmlRpcValueParameters()
    {
        $time   = time();
        $params = [
            new Value\Boolean(true),
            new Value\Integer(4),
            new Value\Text('foo'),
        ];
        $expect = [true, 4, 'foo'];

        $this->setServerResponseTo($expect);

        $result = $this->xmlrpcClient->call('foo.bar', $params);
        $this->assertSame($expect, $result);

        $request  = $this->xmlrpcClient->getLastRequest();
        $response = $this->xmlrpcClient->getLastResponse();

        $this->assertSame('foo.bar', $request->getMethod());
        $this->assertSame($params, $request->getParams());
        $this->assertSame($expect, $response->getReturnValue());
        $this->assertFalse($response->isFault());
    }

    /**
     * @group Laminas-2978
     */
    public function testSkippingSystemCallDisabledByDefault()
    {
        $this->assertFalse($this->xmlrpcClient->skipSystemLookup());
    }

    /**
     * @group Laminas-6993
     */
    public function testWhenPassingAStringAndAnIntegerIsExpectedParamIsConverted()
    {
        $this->mockIntrospector();
        $this->mockedIntrospector
             ->expects($this->exactly(2))
             ->method('getMethodSignature')
             ->with('test.method')
             ->will($this->returnValue([['parameters' => ['int']]]));

        $expect = 'test.method response';
        $this->setServerResponseTo($expect);

        $this->assertSame($expect, $this->xmlrpcClient->call('test.method', ['1']));
        $params = $this->xmlrpcClient->getLastRequest()->getParams();
        $this->assertSame(1, $params[0]->getValue());

        $this->setServerResponseTo($expect);
        $this->assertSame($expect, $this->xmlrpcClient->call('test.method', '1'));
        $params = $this->xmlrpcClient->getLastRequest()->getParams();
        $this->assertSame(1, $params[0]->getValue());
    }

    /**
     * @group Laminas-8074
     */
    public function testXmlRpcObjectsAreNotConverted()
    {
        $this->mockIntrospector();
        $this->mockedIntrospector
             ->expects($this->exactly(1))
             ->method('getMethodSignature')
             ->with('date.method')
             ->will($this->returnValue([['parameters' => ['dateTime.iso8601', 'string']]]));

        $expects = 'date.method response';
        $this->setServerResponseTo($expects);
        $this->assertSame(
            $expects,
            $this->xmlrpcClient->call(
                'date.method',
                [AbstractValue::getXmlRpcValue(time(), AbstractValue::XMLRPC_TYPE_DATETIME), 'foo']
            )
        );
    }

    public function testAllowsSkippingSystemCallForArrayStructLookup()
    {
        $this->xmlrpcClient->setSkipSystemLookup(true);
        $this->assertTrue($this->xmlrpcClient->skipSystemLookup());
    }

    public function testSkipsSystemCallWhenDirected()
    {
        $httpAdapter = $this->httpAdapter;
        $response    = $this->makeHttpResponseFor('foo');
        $httpAdapter->setResponse($response);
        $this->xmlrpcClient->setSkipSystemLookup(true);
        $this->assertSame('foo', $this->xmlrpcClient->call('test.method'));
    }

    public function testRpcMethodCallThrowsOnHttpFailure()
    {
        $status  = 404;
        $message = 'Not Found';
        $body    = 'oops';

        $response = $this->makeHttpResponseFrom($body, $status, $message);
        $this->httpAdapter->setResponse($response);

        $this->expectException(Client\Exception\HttpException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($status);
        $this->xmlrpcClient->call('foo');
    }

    public function testRpcMethodCallThrowsOnXmlRpcFault()
    {
        $code    = 9;
        $message = 'foo';

        $fault = new Fault($code, $message);
        $xml   = $fault->saveXml();

        $response = $this->makeHttpResponseFrom($xml);
        $this->httpAdapter->setResponse($response);

        $this->expectException(Client\Exception\FaultException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($code);
        $this->xmlrpcClient->call('foo');
    }

    public function testGetProxyReturnsServerProxy()
    {
        $this->assertInstanceOf(ServerProxy::class, $this->xmlrpcClient->getProxy());
    }

    public function testRpcMethodCallsThroughServerProxy()
    {
        $expectedReturn = [7, false, 'foo' => 'bar'];
        $this->setServerResponseTo($expectedReturn);

        $server = $this->xmlrpcClient->getProxy();
        $this->assertSame($expectedReturn, $server->listMethods());

        $request = $this->xmlrpcClient->getLastRequest();
        $this->assertEquals('listMethods', $request->getMethod());
    }

    public function testRpcMethodCallsThroughNestedServerProxies()
    {
        $expectedReturn = [7, false, 'foo' => 'bar'];
        $this->setServerResponseTo($expectedReturn);

        $server = $this->xmlrpcClient->getProxy('foo');
        $this->assertSame($expectedReturn, $server->bar->baz->boo());

        $request = $this->xmlrpcClient->getLastRequest();
        $this->assertEquals('foo.bar.baz.boo', $request->getMethod());
    }

    public function testClientCachesServerProxies()
    {
        $proxy = $this->xmlrpcClient->getProxy();
        $this->assertSame($proxy, $this->xmlrpcClient->getProxy());

        $proxy = $this->xmlrpcClient->getProxy('foo');
        $this->assertSame($proxy, $this->xmlrpcClient->getProxy('foo'));
    }

    public function testServerProxyCachesNestedProxies()
    {
        $proxy = $this->xmlrpcClient->getProxy();

        $foo = $proxy->foo;
        $this->assertSame($foo, $proxy->foo);

        $bar = $proxy->foo->bar;
        $this->assertSame($bar, $proxy->foo->bar);
    }

    public function testGettingDefaultIntrospector()
    {
        $xmlrpcClient = new Client('http://foo');
        $introspector = $xmlrpcClient->getIntrospector();
        $this->assertInstanceOf(ServerIntrospection::class, $introspector);
        $this->assertSame($introspector, $xmlrpcClient->getIntrospector());
    }

    public function testSettingAndGettingIntrospector()
    {
        $xmlrpcClient = new Client('http://foo');
        $introspector = new Client\ServerIntrospection($xmlrpcClient);
        $this->assertNotSame($introspector, $xmlrpcClient->getIntrospector());

        $xmlrpcClient->setIntrospector($introspector);
        $this->assertSame($introspector, $xmlrpcClient->getIntrospector());
    }

    public function testGettingMethodSignature()
    {
        $method     = 'foo';
        $signatures = [['int', 'int', 'int']];
        $this->setServerResponseTo($signatures);

        $i = $this->xmlrpcClient->getIntrospector();
        $this->assertEquals($signatures, $i->getMethodSignature($method));

        $request = $this->xmlrpcClient->getLastRequest();
        $this->assertEquals('system.methodSignature', $request->getMethod());
        $this->assertEquals([$method], $request->getParams());
    }

    public function testListingMethods()
    {
        $methods = ['foo', 'bar', 'baz'];
        $this->setServerResponseTo($methods);

        $i = $this->xmlrpcClient->getIntrospector();
        $this->assertEquals($methods, $i->listMethods());

        $request = $this->xmlrpcClient->getLastRequest();
        $this->assertEquals('system.listMethods', $request->getMethod());
        $this->assertEquals([], $request->getParams());
    }

    public function testGettingAllMethodSignaturesByLooping()
    {
        // system.listMethods() will return ['foo', 'bar']
        $methods  = ['foo', 'bar'];
        $response = $this->getServerResponseFor($methods);
        $this->httpAdapter->setResponse($response);

        // system.methodSignature('foo') will return [['int'], ['int', 'string']]
        $fooSignatures = [['int'], ['int', 'string']];
        $response      = $this->getServerResponseFor($fooSignatures);
        $this->httpAdapter->addResponse($response);

        // system.methodSignature('bar') will return [['boolean']]
        $barSignatures = [['boolean']];
        $response      = $this->getServerResponseFor($barSignatures);
        $this->httpAdapter->addResponse($response);

        $expected = [
            'foo' => $fooSignatures,
            'bar' => $barSignatures,
        ];

        $i = $this->xmlrpcClient->getIntrospector();
        $this->assertEquals($expected, $i->getSignatureForEachMethodByLooping());

        $request = $this->xmlrpcClient->getLastRequest();
        $this->assertEquals('system.methodSignature', $request->getMethod());
        $this->assertEquals(['bar'], $request->getParams());
    }

    public function testGettingAllMethodSignaturesByMulticall()
    {
        // system.listMethods() will return ['foo', 'bar']
        $whatListMethodsReturns = ['foo', 'bar'];
        $response               = $this->getServerResponseFor($whatListMethodsReturns);
        $this->httpAdapter->setResponse($response);

        // after system.listMethods(), these system.multicall() params are expected
        $multicallParams = [
            [
                'methodName' => 'system.methodSignature',
                'params'     => ['foo'],
            ],
            [
                'methodName' => 'system.methodSignature',
                'params'     => ['bar'],
            ],
        ];

        // system.multicall() will then return [fooSignatures, barSignatures]
        $fooSignatures        = [['int'], ['int', 'string']];
        $barSignatures        = [['boolean']];
        $whatMulticallReturns = [$fooSignatures, $barSignatures];
        $response             = $this->getServerResponseFor($whatMulticallReturns);
        $this->httpAdapter->addResponse($response);

        $i = $this->xmlrpcClient->getIntrospector();

        $expected = [
            'foo' => $fooSignatures,
            'bar' => $barSignatures,
        ];
        $this->assertEquals($expected, $i->getSignatureForEachMethodByMulticall());

        $request = $this->xmlrpcClient->getLastRequest();
        $this->assertEquals('system.multicall', $request->getMethod());
        $this->assertEquals([$multicallParams], $request->getParams());
    }

    public function testGettingAllMethodSignaturesByMulticallThrowsOnBadCount()
    {
        // system.listMethods() will return ['foo', 'bar']
        $whatListMethodsReturns = ['foo', 'bar'];
        $response               = $this->getServerResponseFor($whatListMethodsReturns);
        $this->httpAdapter->setResponse($response);

        // system.multicall() will then return only [fooSignatures]
        $fooSignatures        = [['int'], ['int', 'string']];
        $whatMulticallReturns = [$fooSignatures];  // error! no bar signatures!

        $response = $this->getServerResponseFor($whatMulticallReturns);
        $this->httpAdapter->addResponse($response);

        $i = $this->xmlrpcClient->getIntrospector();

        $this->expectException(Client\Exception\IntrospectException::class);
        $this->expectExceptionMessage('Bad number of signatures received from multicall');
        $i->getSignatureForEachMethodByMulticall();
    }

    public function testGettingAllMethodSignaturesByMulticallThrowsOnBadType()
    {
        // system.listMethods() will return ['foo', 'bar']
        $whatListMethodsReturns = ['foo', 'bar'];
        $response               = $this->getServerResponseFor($whatListMethodsReturns);
        $this->httpAdapter->setResponse($response);

        // system.multicall() will then return only an int
        $whatMulticallReturns = 1;  // error! no signatures?

        $response = $this->getServerResponseFor($whatMulticallReturns);
        $this->httpAdapter->addResponse($response);

        $i = $this->xmlrpcClient->getIntrospector();

        $this->expectException(Client\Exception\IntrospectException::class);
        $this->expectExceptionMessage('Multicall return is malformed.  Expected array, got integer');
        $i->getSignatureForEachMethodByMulticall();
    }

    public function testGettingAllMethodSignaturesDefaultsToMulticall()
    {
        // system.listMethods() will return ['foo', 'bar']
        $whatListMethodsReturns = ['foo', 'bar'];
        $response               = $this->getServerResponseFor($whatListMethodsReturns);
        $this->httpAdapter->setResponse($response);

        // system.multicall() will then return [fooSignatures, barSignatures]
        $fooSignatures        = [['int'], ['int', 'string']];
        $barSignatures        = [['boolean']];
        $whatMulticallReturns = [$fooSignatures, $barSignatures];
        $response             = $this->getServerResponseFor($whatMulticallReturns);
        $this->httpAdapter->addResponse($response);

        $i = $this->xmlrpcClient->getIntrospector();

        $expected = [
            'foo' => $fooSignatures,
            'bar' => $barSignatures,
        ];
        $this->assertEquals($expected, $i->getSignatureForEachMethod());

        $request = $this->xmlrpcClient->getLastRequest();
        $this->assertEquals('system.multicall', $request->getMethod());
    }

    /**
     * @group Laminas-4372
     */
    public function testSettingUriOnHttpClientIsNotOverwrittenByXmlRpcClient()
    {
        $changedUri = 'http://bar:80/';
        // Overwrite: http://foo:80
        $this->setServerResponseTo([]);
        $this->xmlrpcClient->getHttpClient()->setUri($changedUri);
        $this->xmlrpcClient->call('foo');
        $uri = $this->xmlrpcClient->getHttpClient()->getUri()->toString();

        $this->assertEquals($changedUri, $uri);
    }

    /**
     * @group Laminas-4372
     */
    public function testSettingNoHttpClientUriForcesClientToSetUri()
    {
        $baseUri           = 'http://foo:80/';
        $this->httpAdapter = new Adapter\Test();
        $this->httpClient  = new HttpClient(null, ['adapter' => $this->httpAdapter]);

        $this->xmlrpcClient = new Client($baseUri);
        $this->xmlrpcClient->setHttpClient($this->httpClient);

        $this->setServerResponseTo([]);
        $this->assertNull($this->xmlrpcClient->getHttpClient()->getRequest()->getUriString());
        $this->xmlrpcClient->call('foo');
        $uri = $this->xmlrpcClient->getHttpClient()->getUri();

        $this->assertEquals($baseUri, $uri->toString());
    }

    /**
     * @group Laminas-3288
     */
    public function testCustomHttpClientUserAgentIsNotOverridden()
    {
        $this->assertFalse(
            $this->httpClient->getHeader('user-agent'),
            'UA is null if no request was made'
        );
        $this->setServerResponseTo(true);
        $this->assertTrue($this->xmlrpcClient->call('method'));
        $this->assertSame(
            'Laminas_XmlRpc_Client',
            $this->httpClient->getHeader('user-agent'),
            'If no custom UA is set, set Laminas_XmlRpc_Client'
        );

        $expectedUserAgent = 'Laminas_XmlRpc_Client (custom)';
        $this->httpClient->setHeaders(['user-agent' => $expectedUserAgent]);

        $this->setServerResponseTo(true);
        $this->assertTrue($this->xmlrpcClient->call('method'));
        $this->assertSame($expectedUserAgent, $this->httpClient->getHeader('user-agent'));
    }

    /**
     * @group #27
     */
    public function testContentTypeIsNotReplaced()
    {
        $this->assertFalse(
            $this->httpClient->getHeader('Content-Type'),
            'Content-Type is null if no request was made'
        );

        $expectedContentType = 'text/xml; charset=utf-8';
        $this->httpClient->setHeaders(['Content-Type' => $expectedContentType]);

        $this->setServerResponseTo(true);
        $this->assertTrue($this->xmlrpcClient->call('method'));
        $this->assertSame($expectedContentType, $this->httpClient->getHeader('Content-Type'));
    }

    /**
     * @group #27
     */
    public function testAcceptIsNotReplaced()
    {
        $this->assertFalse(
            $this->httpClient->getHeader('Accept'),
            'Accept header is null if no request was made'
        );

        $expectedAccept = 'text/xml';
        $this->httpClient->setHeaders(['Accept' => $expectedAccept]);

        $this->setServerResponseTo(true);
        $this->assertTrue($this->xmlrpcClient->call('method'));
        $this->assertSame($expectedAccept, $this->httpClient->getHeader('Accept'));
    }

    /**
     * @group Laminas-8478
     */
    public function testPythonSimpleXMLRPCServerWithUnsupportedMethodSignatures()
    {
        $introspector = new Client\ServerIntrospection(
            new TestAsset\TestClient('http://localhost/')
        );

        $this->expectException(Client\Exception\IntrospectException::class);
        $this->expectExceptionMessage('Invalid signature for method "add"');
        $signature = $introspector->getMethodSignature('add');
    }

    /**
     * @group Laminas-8580
     */
    public function testCallSelectsCorrectSignatureIfMoreThanOneIsAvailable()
    {
        $this->mockIntrospector();

        $this->mockedIntrospector
             ->expects($this->exactly(2))
             ->method('getMethodSignature')
             ->with('get')
             ->will($this->returnValue([
                 ['parameters' => ['int']],
                 ['parameters' => ['array']],
             ]));

        $expectedResult = 'array';
        $this->setServerResponseTo($expectedResult);

        $this->assertSame(
            $expectedResult,
            $this->xmlrpcClient->call('get', [[1]])
        );

        $expectedResult = 'integer';
        $this->setServerResponseTo($expectedResult);

        $this->assertSame(
            $expectedResult,
            $this->xmlrpcClient->call('get', [1])
        );
    }

    /**
     * @group Laminas-1897
     */
    public function testHandlesLeadingOrTrailingWhitespaceInChunkedResponseProperly()
    {
        $baseUri           = "http://foo:80";
        $this->httpAdapter = new Adapter\Test();
        $this->httpClient  = new HttpClient(null, ['adapter' => $this->httpAdapter]);

        $respBody = file_get_contents(dirname(__FILE__) . "/_files/Laminas1897-response-chunked.txt");
        $this->httpAdapter->setResponse($respBody);

        $this->xmlrpcClient = new Client($baseUri);
        $this->xmlrpcClient->setHttpClient($this->httpClient);

        $this->assertEquals('FOO', $this->xmlrpcClient->call('foo'));
    }

    /**
     * @param mixed $nativeVars
     */
    public function setServerResponseTo($nativeVars)
    {
        $response = $this->getServerResponseFor($nativeVars);
        $this->httpAdapter->setResponse($response);
    }

    /**
     * @param mixed $nativeVars
     * @return string
     */
    public function getServerResponseFor($nativeVars)
    {
        $response = new Response();
        $response->setReturnValue($nativeVars);
        $xml = $response->saveXml();

        return $this->makeHttpResponseFrom($xml);
    }

    /**
     * @param string $data
     * @param int $status
     * @param string $message
     * @return string
     */
    public function makeHttpResponseFrom($data, $status = 200, $message = 'OK')
    {
        $headers = [
            "HTTP/1.1 $status $message",
            "Status: $status",
            'Content-Type: text/xml; charset=utf-8',
            'Content-Length: ' . strlen($data),
        ];
        return implode("\r\n", $headers) . "\r\n\r\n$data\r\n\r\n";
    }

    /**
     * @param mixed $nativeVars
     * @return HttpResponse
     */
    public function makeHttpResponseFor($nativeVars)
    {
        $response = $this->getServerResponseFor($nativeVars);
        return HttpResponse::fromString($response);
    }

    public function mockIntrospector()
    {
        $this->mockedIntrospector = $this->getMockBuilder(Client\ServerIntrospection::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $this->xmlrpcClient->setIntrospector($this->mockedIntrospector);
    }

    public function mockHttpClient()
    {
        $this->mockedHttpClient = $this->createMock(HttpClient::class);
        $this->xmlrpcClient->setHttpClient($this->mockedHttpClient);
    }
}
