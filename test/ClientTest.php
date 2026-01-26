<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc;

use Laminas\Diactoros\RequestFactory as DiactorosRequestFactory;
use Laminas\Diactoros\Response\Serializer as ResponseSerializer;
use Laminas\Diactoros\StreamFactory as DiactorosStreamFactory;
use Laminas\XmlRpc\AbstractValue;
use Laminas\XmlRpc\Client;
use Laminas\XmlRpc\Client\ServerIntrospection;
use Laminas\XmlRpc\Client\ServerProxy;
use Laminas\XmlRpc\Fault;
use Laminas\XmlRpc\Request;
use Laminas\XmlRpc\Response;
use Laminas\XmlRpc\Value;
use LaminasTest\XmlRpc\TestAsset\TestPsr18Client;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function count;
use function ctype_xdigit;
use function file_get_contents;
use function hexdec;
use function implode;
use function strlen;
use function strpos;
use function substr;
use function time;
use function trim;

#[Group('Laminas_XmlRpc')]
final class ClientTest extends TestCase
{
    /** @var TestPsr18Client */
    protected $httpClient;

    /** @var RequestFactoryInterface */
    protected $requestFactory;

    /** @var StreamFactoryInterface */
    protected $streamFactory;

    /** @var Client */
    protected $xmlrpcClient;

    private MockObject $mockedIntrospector;

    #[Override]
    protected function setUp(): void
    {
        $this->httpClient     = new TestPsr18Client();
        $this->requestFactory = new DiactorosRequestFactory();
        $this->streamFactory  = new DiactorosStreamFactory();

        $this->xmlrpcClient = new Client(
            'http://foo',
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory
        );
    }

    public function testLastRequestAndResponseAreInitiallyNull(): void
    {
        $this->assertNull($this->xmlrpcClient->getLastRequest());
        $this->assertNull($this->xmlrpcClient->getLastResponse());
    }

    public function testLastRequestAndResponseAreSetAfterRpcMethodCall(): void
    {
        $this->setServerResponseTo(true);
        $this->xmlrpcClient->call('foo');

        $this->assertInstanceOf(Request::class, $this->xmlrpcClient->getLastRequest());
        $this->assertInstanceOf(Response::class, $this->xmlrpcClient->getLastResponse());
    }

    public function testSuccessfulRpcMethodCallWithNoParameters(): void
    {
        $expectedMethod = 'foo.bar';
        $expectedReturn = 7;

        $this->setServerResponseTo($expectedReturn);
        $this->assertSame($expectedReturn, $this->xmlrpcClient->call($expectedMethod));

        $request  = $this->xmlrpcClient->getLastRequest();
        $response = $this->xmlrpcClient->getLastResponse();

        if ($request !== null) {
            $this->assertSame($expectedMethod, $request->getMethod());
            $this->assertSame([], $request->getParams());
        }

        if ($response !== null) {
            $this->assertSame($expectedReturn, $response->getReturnValue());
            $this->assertFalse($response->isFault());
        }
    }

    public function testSuccessfulRpcMethodCallWithParameters(): void
    {
        $expectedMethod = 'foo.bar';
        $expectedParams = [1, 'foo' => 'bar', 1.1, true];
        $expectedReturn = [7, false, 'foo' => 'bar'];

        $this->setServerResponseTo($expectedReturn);

        $actualReturn = $this->xmlrpcClient->call($expectedMethod, $expectedParams);
        $this->assertSame($expectedReturn, $actualReturn);

        $request  = $this->xmlrpcClient->getLastRequest();
        $response = $this->xmlrpcClient->getLastResponse();

        if ($request !== null) {
            $this->assertSame($expectedMethod, $request->getMethod());
            $params = $request->getParams();
            $this->assertSame(count($expectedParams), count($params));
            $this->assertSame($expectedParams[0], $params[0]->getValue());
            $this->assertSame($expectedParams[1], $params[1]->getValue());
            $this->assertSame($expectedParams[2], $params[2]->getValue());
            $this->assertSame($expectedParams['foo'], $params['foo']->getValue());
        }

        if ($response !== null) {
            $this->assertSame($expectedReturn, $response->getReturnValue());
            $this->assertFalse($response->isFault());
        }
    }

    #[Group('Laminas-2090')]
    public function testSuccessfullyDetectsEmptyArrayParameterAsArray(): void
    {
        $expectedMethod = 'foo.bar';
        $expectedParams = [[]];
        $expectedReturn = [true];

        $this->setServerResponseTo($expectedReturn);

        $actualReturn = $this->xmlrpcClient->call($expectedMethod, $expectedParams);
        $this->assertSame($expectedReturn, $actualReturn);

        $request = $this->xmlrpcClient->getLastRequest();

        if ($request !== null) {
            $params = $request->getParams();
            $this->assertSame(count($expectedParams), count($params));
            $this->assertSame($expectedParams[0], $params[0]->getValue());
        }
    }

    #[Group('Laminas-1412')]
    public function testSuccessfulRpcMethodCallWithMixedDateParameters(): void
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

        if ($request !== null) {
            $this->assertSame($expectedMethod, $request->getMethod());
            $params = $request->getParams();
            $this->assertSame(count($expectedParams), count($params));
            $this->assertSame($expectedParams[0], $params[0]->getValue());
            $this->assertSame($expectedParams[1], $params[1]);
        }

        if ($response !== null) {
            $this->assertSame($expectedReturn, $response->getReturnValue());
            $this->assertFalse($response->isFault());
        }
    }

    #[Group('Laminas-1797')]
    public function testSuccessfulRpcMethodCallWithXmlRpcValueParameters(): void
    {
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

        if ($request !== null) {
            $this->assertSame('foo.bar', $request->getMethod());
            $this->assertSame($params, $request->getParams());
        }

        if ($response !== null) {
            $this->assertSame($expect, $response->getReturnValue());
            $this->assertFalse($response->isFault());
        }
    }

    #[Group('Laminas-2978')]
    public function testSkippingSystemCallDisabledByDefault(): void
    {
        $this->assertFalse($this->xmlrpcClient->skipSystemLookup());
    }

    #[Group('Laminas-6993')]
    public function testWhenPassingAStringAndAnIntegerIsExpectedParamIsConverted(): void
    {
        $this->mockIntrospector();
        $this->mockedIntrospector
            ->expects($this->exactly(2))
            ->method('getMethodSignature')
            ->with('test.method')
            ->willReturn([['parameters' => ['int']]]);

        $expect = 'test.method response';
        $this->setServerResponseTo($expect);

        $this->assertSame($expect, $this->xmlrpcClient->call('test.method', ['1']));
        $request = $this->xmlrpcClient->getLastRequest();
        if ($request !== null) {
            $params = $request->getParams();
            $this->assertSame(1, $params[0]->getValue());
        }

        $this->setServerResponseTo($expect);
        $this->assertSame($expect, $this->xmlrpcClient->call('test.method', '1'));
        $request = $this->xmlrpcClient->getLastRequest();
        if ($request !== null) {
            $params = $request->getParams();
            $this->assertSame(1, $params[0]->getValue());
        }
    }

    #[Group('Laminas-8074')]
    public function testXmlRpcObjectsAreNotConverted(): void
    {
        $this->mockIntrospector();
        $this->mockedIntrospector
            ->expects($this->exactly(1))
            ->method('getMethodSignature')
            ->with('date.method')
            ->willReturn([['parameters' => ['dateTime.iso8601', 'string']]]);

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

    public function testAllowsSkippingSystemCallForArrayStructLookup(): void
    {
        $this->xmlrpcClient->setSkipSystemLookup(true);
        $this->assertTrue($this->xmlrpcClient->skipSystemLookup());
    }

    public function testSkipsSystemCallWhenDirected(): void
    {
        $response = $this->makeHttpResponseFor('foo');
        $this->httpClient->addResponse($response);

        $this->xmlrpcClient->setSkipSystemLookup(true);
        $this->assertSame('foo', $this->xmlrpcClient->call('test.method'));
    }

    public function testRpcMethodCallThrowsOnHttpFailure(): void
    {
        $status  = 404;
        $message = 'Not Found';
        $body    = 'oops';

        $response = $this->makeHttpResponseFrom($body, $status, $message);
        $this->httpClient->addResponse($response);

        $this->expectException(Client\Exception\HttpException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($status);
        $this->xmlrpcClient->call('foo');
    }

    public function testRpcMethodCallThrowsOnXmlRpcFault(): void
    {
        $code    = 9;
        $message = 'foo';

        $fault = new Fault($code, $message);
        $xml   = $fault->saveXml();

        $response = $this->makeHttpResponseFrom($xml);
        $this->httpClient->addResponse($response);

        $this->expectException(Client\Exception\FaultException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($code);
        $this->xmlrpcClient->call('foo');
    }

    public function testGetProxyReturnsServerProxy(): void
    {
        $this->assertInstanceOf(ServerProxy::class, $this->xmlrpcClient->getProxy());
    }

    public function testRpcMethodCallsThroughServerProxy(): void
    {
        $expectedReturn = [7, false, 'foo' => 'bar'];
        $this->setServerResponseTo($expectedReturn);

        $server = $this->xmlrpcClient->getProxy();
        $this->assertSame($expectedReturn, $server->listMethods());

        $request = $this->xmlrpcClient->getLastRequest();
        $this->assertEquals('listMethods', $request?->getMethod());
    }

    public function testRpcMethodCallsThroughNestedServerProxies(): void
    {
        $expectedReturn = [7, false, 'foo' => 'bar'];
        $this->setServerResponseTo($expectedReturn);

        $server = $this->xmlrpcClient->getProxy('foo');
        $this->assertSame($expectedReturn, $server->bar->baz->boo());

        $request = $this->xmlrpcClient->getLastRequest();
        $this->assertEquals('foo.bar.baz.boo', $request?->getMethod());
    }

    public function testClientCachesServerProxies(): void
    {
        $proxy = $this->xmlrpcClient->getProxy();
        $this->assertSame($proxy, $this->xmlrpcClient->getProxy());

        $proxy = $this->xmlrpcClient->getProxy('foo');
        $this->assertSame($proxy, $this->xmlrpcClient->getProxy('foo'));
    }

    public function testServerProxyCachesNestedProxies(): void
    {
        $proxy = $this->xmlrpcClient->getProxy();

        $foo = $proxy->foo;
        $this->assertSame($foo, $proxy->foo);

        $bar = $proxy->foo->bar;
        $this->assertSame($bar, $proxy->foo->bar);
    }

    public function testGettingDefaultIntrospector(): void
    {
        $httpClient     = new TestPsr18Client();
        $requestFactory = new DiactorosRequestFactory();
        $streamFactory  = new DiactorosStreamFactory();

        $xmlrpcClient = new Client(
            'http://foo',
            $httpClient,
            $requestFactory,
            $streamFactory
        );

        $introspector = $xmlrpcClient->getIntrospector();
        $this->assertInstanceOf(ServerIntrospection::class, $introspector);
        $this->assertSame($introspector, $xmlrpcClient->getIntrospector());
    }

    public function testSettingAndGettingIntrospector(): void
    {
        $httpClient     = new TestPsr18Client();
        $requestFactory = new DiactorosRequestFactory();
        $streamFactory  = new DiactorosStreamFactory();

        $xmlrpcClient = new Client(
            'http://foo',
            $httpClient,
            $requestFactory,
            $streamFactory
        );
        $introspector = new Client\ServerIntrospection($xmlrpcClient);
        $this->assertNotSame($introspector, $xmlrpcClient->getIntrospector());

        $xmlrpcClient->setIntrospector($introspector);
        $this->assertSame($introspector, $xmlrpcClient->getIntrospector());
    }

    public function testGettingMethodSignature(): void
    {
        $method     = 'foo';
        $signatures = [['int', 'int', 'int']];
        $this->setServerResponseTo($signatures);

        $i = $this->xmlrpcClient->getIntrospector();
        $this->assertEquals($signatures, $i->getMethodSignature($method));

        $request = $this->xmlrpcClient->getLastRequest();
        $this->assertEquals('system.methodSignature', $request?->getMethod());
        $this->assertEquals([$method], $request?->getParams());
    }

    public function testListingMethods(): void
    {
        $methods = ['foo', 'bar', 'baz'];
        $this->setServerResponseTo($methods);

        $i = $this->xmlrpcClient->getIntrospector();
        $this->assertEquals($methods, $i->listMethods());

        $request = $this->xmlrpcClient->getLastRequest();
        $this->assertEquals('system.listMethods', $request?->getMethod());
        $this->assertEquals([], $request?->getParams());
    }

    public function testGettingAllMethodSignaturesByLooping(): void
    {
        // system.listMethods() will return ['foo', 'bar']
        $methods  = ['foo', 'bar'];
        $response = $this->getServerResponseFor($methods);
        $this->httpClient->addResponse($response);

        // system.methodSignature('foo') will return [['int'], ['int', 'string']]
        $fooSignatures = [['int'], ['int', 'string']];
        $response      = $this->getServerResponseFor($fooSignatures);
        $this->httpClient->addResponse($response);

        // system.methodSignature('bar') will return [['boolean']]
        $barSignatures = [['boolean']];
        $response      = $this->getServerResponseFor($barSignatures);
        $this->httpClient->addResponse($response);

        $expected = [
            'foo' => $fooSignatures,
            'bar' => $barSignatures,
        ];

        $i = $this->xmlrpcClient->getIntrospector();
        $this->assertEquals($expected, $i->getSignatureForEachMethodByLooping());

        $request = $this->xmlrpcClient->getLastRequest();
        $this->assertEquals('system.methodSignature', $request?->getMethod());
        $this->assertEquals(['bar'], $request?->getParams());
    }

    public function testGettingAllMethodSignaturesByMulticall(): void
    {
        // system.listMethods() will return ['foo', 'bar']
        $whatListMethodsReturns = ['foo', 'bar'];
        $response               = $this->getServerResponseFor($whatListMethodsReturns);
        $this->httpClient->addResponse($response);

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
        $this->httpClient->addResponse($response);

        $i = $this->xmlrpcClient->getIntrospector();

        $expected = [
            'foo' => $fooSignatures,
            'bar' => $barSignatures,
        ];
        $this->assertEquals($expected, $i->getSignatureForEachMethodByMulticall());

        $request = $this->xmlrpcClient->getLastRequest();
        $this->assertEquals('system.multicall', $request?->getMethod());
        $this->assertEquals([$multicallParams], $request?->getParams());
    }

    public function testGettingAllMethodSignaturesByMulticallThrowsOnBadCount(): void
    {
        // system.listMethods() will return ['foo', 'bar']
        $whatListMethodsReturns = ['foo', 'bar'];
        $response               = $this->getServerResponseFor($whatListMethodsReturns);
        $this->httpClient->addResponse($response);

        // system.multicall() will then return only [fooSignatures]
        $fooSignatures = [['int'], ['int', 'string']];

        $whatMulticallReturns = [$fooSignatures];  // error! no bar signatures!

        $response = $this->getServerResponseFor($whatMulticallReturns);
        $this->httpClient->addResponse($response);

        $i = $this->xmlrpcClient->getIntrospector();

        $this->expectException(Client\Exception\IntrospectException::class);
        $this->expectExceptionMessage('Bad number of signatures received from multicall');
        $i->getSignatureForEachMethodByMulticall();
    }

    public function testGettingAllMethodSignaturesByMulticallThrowsOnBadType(): void
    {
        // system.listMethods() will return ['foo', 'bar']
        $whatListMethodsReturns = ['foo', 'bar'];
        $response               = $this->getServerResponseFor($whatListMethodsReturns);
        $this->httpClient->addResponse($response);

        // system.multicall() will then return only an int
        $whatMulticallReturns = 1;  // error! no signatures?

        $response = $this->getServerResponseFor($whatMulticallReturns);
        $this->httpClient->addResponse($response);

        $i = $this->xmlrpcClient->getIntrospector();

        $this->expectException(Client\Exception\IntrospectException::class);
        $this->expectExceptionMessage('Multicall return is malformed.  Expected array, got integer');
        $i->getSignatureForEachMethodByMulticall();
    }

    public function testGettingAllMethodSignaturesDefaultsToMulticall(): void
    {
        // system.listMethods() will return ['foo', 'bar']
        $whatListMethodsReturns = ['foo', 'bar'];
        $response               = $this->getServerResponseFor($whatListMethodsReturns);
        $this->httpClient->addResponse($response);

        // system.multicall() will then return [fooSignatures, barSignatures]
        $fooSignatures        = [['int'], ['int', 'string']];
        $barSignatures        = [['boolean']];
        $whatMulticallReturns = [$fooSignatures, $barSignatures];
        $response             = $this->getServerResponseFor($whatMulticallReturns);
        $this->httpClient->addResponse($response);

        $i = $this->xmlrpcClient->getIntrospector();

        $expected = [
            'foo' => $fooSignatures,
            'bar' => $barSignatures,
        ];
        $this->assertEquals($expected, $i->getSignatureForEachMethod());

        $request = $this->xmlrpcClient->getLastRequest();
        $this->assertEquals('system.multicall', $request?->getMethod());
    }

    #[Group('Laminas-8478')]
    public function testPythonSimpleXMLRPCServerWithUnsupportedMethodSignatures(): void
    {
        $httpClient = new TestPsr18Client();
        $client     = new Client(
            'http://localhost/',
            $httpClient,
            $this->requestFactory,
            $this->streamFactory
        );

        $introspector = new Client\ServerIntrospection($client);

        $malformedSignatures = 1;
        $response            = $this->getServerResponseFor($malformedSignatures);

        $httpClient->setResponse($response);

        $this->expectException(Client\Exception\IntrospectException::class);
        $this->expectExceptionMessage('Invalid signature for method "add"');

        $introspector->getMethodSignature('add');
    }

    #[Group('Laminas-8580')]
    public function testCallSelectsCorrectSignatureIfMoreThanOneIsAvailable(): void
    {
        $this->mockIntrospector();

        $this->mockedIntrospector
            ->expects($this->exactly(2))
            ->method('getMethodSignature')
            ->with('get')
            ->willReturn([
                ['parameters' => ['int']],
                ['parameters' => ['array']],
            ]);

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

    #[Group('Laminas-1897')]
    public function testHandlesLeadingOrTrailingWhitespaceInChunkedResponseProperly(): void
    {
        $baseUri            = "http://foo:80";
        $this->httpClient   = new TestPsr18Client();
        $this->xmlrpcClient = new Client(
            $baseUri,
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory
        );
        $this->xmlrpcClient->setSkipSystemLookup(true);

        $raw         = file_get_contents(__DIR__ . "/_files/Laminas1897-response-chunked.txt");
        $decodedBody = $this->decodeChunkedBody($raw);
        $response    = new \Laminas\Diactoros\Response(
            $this->streamFactory->createStream($decodedBody),
            200,
            ['Content-Type' => 'text/xml; charset=utf-8']
        );
        $this->httpClient->setResponse($response);

        $this->assertEquals('FOO', $this->xmlrpcClient->call('foo'));
    }

    private function decodeChunkedBody(string $raw): string
    {
        // Split headers and body
        $position = strpos($raw, "\r\n\r\n");
        if ($position === false) {
            // Not an HTTP message as expected; just return raw
            return $raw;
        }

        $chunked = substr($raw, $position + 4); // skip header + \r\n\r\n
        $decoded = '';

        while ($chunked !== '') {
            // Find the next chunk length line
            $newlinePosition = strpos($chunked, "\r\n");
            if ($newlinePosition === false) {
                break;
            }

            $lenHex = substr($chunked, 0, $newlinePosition);
            $lenHex = trim($lenHex);

            // Safety: empty or invalid hex -> stop
            if ($lenHex === '' || ! ctype_xdigit($lenHex)) {
                break;
            }

            $length  = hexdec($lenHex);
            $chunked = substr($chunked, $newlinePosition + 2); // skip "len\r\n"

            if ($length === 0) {
                // End of chunks
                break;
            }

            // Take exactly $length bytes as the chunk payload
            $decoded .= substr($chunked, 0, $length);

            // Skip the chunk data + trailing CRLF
            $chunked = substr($chunked, $length + 2);
        }

        return $decoded;
    }

    /**
     * @param mixed $nativeVars
     */
    public function setServerResponseTo($nativeVars): void
    {
        $response = $this->getServerResponseFor($nativeVars);
        $this->httpClient->setResponse($response);
    }

    /**
     * @param ((string|string[])[]|string)[]|int|string|bool $nativeVars
     */
    public function getServerResponseFor(array|int|string|bool $nativeVars): ResponseInterface
    {
        $response = new Response();
        $response->setReturnValue($nativeVars);
        $xml = $response->saveXml();

        return $this->makeHttpResponseFrom($xml);
    }

    public function makeHttpResponseFrom(string $data, int $status = 200, string $message = 'OK'): ResponseInterface
    {
        $headers = [
            "HTTP/1.1 $status $message",
            "Status: $status",
            'Content-Type: text/xml; charset=utf-8',
            'Content-Length: ' . strlen($data),
        ];

        $raw = implode("\r\n", $headers) . "\r\n\r\n" . $data . "\r\n";

        return ResponseSerializer::fromString($raw);
    }

    public function makeHttpResponseFor(mixed $nativeVars): ResponseInterface
    {
        return $this->getServerResponseFor($nativeVars);
    }

    public function mockIntrospector(): void
    {
        $this->mockedIntrospector = $this->createMock(Client\ServerIntrospection::class);
        $this->xmlrpcClient->setIntrospector($this->mockedIntrospector);
    }
}
