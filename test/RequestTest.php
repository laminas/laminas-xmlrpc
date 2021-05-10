<?php

namespace LaminasTest\XmlRpc;

use Laminas\XmlRpc\AbstractValue;
use Laminas\XmlRpc\Request;
use Laminas\XmlRpc\Value;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_XmlRpc
 */
class RequestTest extends TestCase
{
    /**
     * \Laminas\XmlRpc\Request object
     * @var \Laminas\XmlRpc\Request
     */
    protected $request;

    /**
     * Setup environment
     */
    protected function setUp(): void
    {
        $this->request = new Request();
    }

    /**
     * Teardown environment
     */
    protected function tearDown(): void
    {
        unset($this->request);
    }

    /**
     * get/setMethod() test
     */
    public function testMethod()
    {
        $this->assertTrue($this->request->setMethod('testMethod'));
        $this->assertTrue($this->request->setMethod('testMethod9'));
        $this->assertTrue($this->request->setMethod('test.Method'));
        $this->assertTrue($this->request->setMethod('test_method'));
        $this->assertTrue($this->request->setMethod('test:method'));
        $this->assertTrue($this->request->setMethod('test/method'));
        $this->assertFalse($this->request->setMethod('testMethod-bogus'));

        $this->assertEquals('test/method', $this->request->getMethod());
    }


    /**
     * __construct() test
     */
    public function testConstructorOptionallySetsMethodAndParams()
    {
        $r = new Request();
        $this->assertEquals('', $r->getMethod());
        $this->assertEquals([], $r->getParams());

        $method = 'foo.bar';
        $params = ['baz', 1, ['foo' => 'bar']];
        $r = new Request($method, $params);
        $this->assertEquals($method, $r->getMethod());
        $this->assertEquals($params, $r->getParams());
    }


    /**
     * addParam()/getParams() test
     */
    public function testAddParam()
    {
        $this->request->addParam('string1');
        $params = $this->request->getParams();
        $this->assertEquals(1, count($params));
        $this->assertEquals('string1', $params[0]);

        $this->request->addParam('string2');
        $params = $this->request->getParams();
        $this->assertSame(2, count($params));
        $this->assertSame('string1', $params[0]);
        $this->assertSame('string2', $params[1]);

        $this->request->addParam(new Value\Text('foo'));
        $params = $this->request->getParams();
        $this->assertSame(3, count($params));
        $this->assertSame('string1', $params[0]);
        $this->assertSame('string2', $params[1]);
        $this->assertSame('foo', $params[2]->getValue());
    }

    public function testAddDateParamGeneratesCorrectXml()
    {
        $time = time();
        $this->request->addParam($time, AbstractValue::XMLRPC_TYPE_DATETIME);
        $this->request->setMethod('foo.bar');
        $xml = $this->request->saveXml();
        $sxl = new \SimpleXMLElement($xml);
        $param = $sxl->params->param->value;
        $type  = 'dateTime.iso8601';
        $this->assertTrue(isset($param->{$type}), var_export($param, 1));
        $this->assertEquals($time, strtotime((string) $param->{$type}));
    }

    /**
     * setParams()/getParams() test
     */
    public function testSetParams()
    {
        $params = [
            'string1',
            true,
            ['one', 'two']
        ];
        $this->request->setParams($params);
        $returned = $this->request->getParams();
        $this->assertSame($params, $returned);

        $params = [
            'string2',
            ['two', 'one']
        ];
        $this->request->setParams($params);
        $returned = $this->request->getParams();
        $this->assertSame($params, $returned);

        $params = [['value' => 'foobar']];
        $this->request->setParams($params);
        $this->assertSame(['foobar'], $this->request->getParams());
        $this->assertSame(['string'], $this->request->getTypes());

        $null = new Value\Nil();
        $this->request->setParams('foo', 1, $null);
        $this->assertSame(['foo', 1, $null], $this->request->getParams());
        $this->assertSame(['string', 'int', 'nil'], $this->request->getTypes());

        $this->assertNull($this->request->setParams(), 'Call without argument returns null');
    }

    /**
     * loadXml() test
     */
    public function testLoadXml()
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $mCall = $dom->appendChild($dom->createElement('methodCall'));
        $mName = $mCall->appendChild($dom->createElement('methodName', 'do.Something'));
        $params = $mCall->appendChild($dom->createElement('params'));
        $param1 = $params->appendChild($dom->createElement('param'));
        $value1 = $param1->appendChild($dom->createElement('value'));
        $value1->appendChild($dom->createElement('string', 'string1'));

        $param2 = $params->appendChild($dom->createElement('param'));
        $value2 = $param2->appendChild($dom->createElement('value'));
        $value2->appendChild($dom->createElement('boolean', 1));


        $xml = $dom->saveXml();


        $parsed = $this->request->loadXml($xml);
        $this->assertTrue($parsed, $xml);

        $this->assertEquals('do.Something', $this->request->getMethod());
        $test = ['string1', true];
        $params = $this->request->getParams();
        $this->assertSame($test, $params);

        $parsed = $this->request->loadXml('foo');
        $this->assertFalse($parsed, 'Parsed non-XML string?');
    }

    public function testPassingInvalidTypeToLoadXml()
    {
        $this->assertFalse($this->request->loadXml(new \stdClass()));
        $this->assertTrue($this->request->isFault());
        $this->assertSame(635, $this->request->getFault()->getCode());
        $this->assertSame('Invalid XML provided to request', $this->request->getFault()->getMessage());
    }

    public function testLoadingXmlWithoutMethodNameElement()
    {
        $this->assertFalse($this->request->loadXml('<empty/>'));
        $this->assertTrue($this->request->isFault());
        $this->assertSame(632, $this->request->getFault()->getCode());
        $this->assertSame(
            "Invalid request, no method passed; request must contain a 'methodName' tag",
            $this->request->getFault()->getMessage()
        );
    }

    public function testLoadingXmlWithInvalidParams()
    {
        $this->assertFalse($this->request->loadXml(
            '<methodCall>'
            . '<methodName>foo</methodName>'
            . '<params><param/><param/><param><foo/></param></params>'
            . '</methodCall>'
        ));
        $this->assertTrue($this->request->isFault());
        $this->assertSame(633, $this->request->getFault()->getCode());
        $this->assertSame(
            'Param must contain a value',
            $this->request->getFault()->getMessage()
        );
    }

    public function testExceptionWhileLoadingXmlParamValueIsHandled()
    {
        $this->assertFalse($this->request->loadXml(
            '<methodCall>'
            . '<methodName>foo</methodName>'
            . '<params><param><value><foo/></value></param></params>'
            . '</methodCall>'
        ));
        $this->assertTrue($this->request->isFault());
        $this->assertSame(636, $this->request->getFault()->getCode());
        $this->assertSame(
            'Error creating xmlrpc value',
            $this->request->getFault()->getMessage()
        );
    }

    /**
     * isFault() test
     */
    public function testIsFault()
    {
        $this->assertFalse($this->request->isFault());
        $this->request->loadXml('foo');
        $this->assertTrue($this->request->isFault());
    }

    /**
     * getFault() test
     */
    public function testGetFault()
    {
        $fault = $this->request->getFault();
        $this->assertNull($fault);
        $this->request->loadXml('foo');
        $fault = $this->request->getFault();
        $this->assertInstanceOf('Laminas\XmlRpc\Fault', $fault);
    }

    /**
     * helper for saveXml() and __toString() tests
     *
     * @param string $xml
     * @return void
     */
    protected function assertXmlRequest($xml, $argv)
    {
        $sx = new \SimpleXMLElement($xml);

        $result = $sx->xpath('//methodName');
        $count = count($result);
        $this->assertEquals(1, $count, $xml);

        $result = $sx->xpath('//params');
        $count = count($result);
        $this->assertEquals(1, $count, $xml);

        $methodName = (string) $sx->methodName;
        $params = [
            (string) $sx->params->param[0]->value->string,
            (bool) $sx->params->param[1]->value->boolean
        ];

        $this->assertEquals('do.Something', $methodName);
        $this->assertSame($argv, $params, $xml);
    }

    /**
     * testSaveXML() test
     */
    public function testSaveXML()
    {
        $argv = ['string', true];
        $this->request->setMethod('do.Something');
        $this->request->setParams($argv);
        $xml = $this->request->saveXml();
        $this->assertXmlRequest($xml, $argv);
    }

    /**
     * __toString() test
     */
    public function testCastToString()
    {
        $argv = ['string', true];
        $this->request->setMethod('do.Something');
        $this->request->setParams($argv);
        $xml = $this->request->__toString();
        $this->assertXmlRequest($xml, $argv);
    }

    /**
     * Test encoding settings
     */
    public function testSetGetEncoding()
    {
        $this->assertEquals('UTF-8', $this->request->getEncoding());
        $this->assertEquals('UTF-8', AbstractValue::getGenerator()->getEncoding());
        $this->assertSame($this->request, $this->request->setEncoding('ISO-8859-1'));
        $this->assertEquals('ISO-8859-1', $this->request->getEncoding());
        $this->assertEquals('ISO-8859-1', AbstractValue::getGenerator()->getEncoding());
    }

    /**
     * @group Laminas-12293
     *
     * Test should remain, but is defunct since DOCTYPE presence should return FALSE
     * from loadXml()
     */
    public function testDoesNotAllowExternalEntities()
    {
        $payload = file_get_contents(dirname(__FILE__) . '/_files/Laminas12293-request.xml');
        $payload = sprintf($payload, 'file://' . realpath(dirname(__FILE__) . '/_files/Laminas12293-payload.txt'));
        $this->request->loadXml($payload);
        $method = $this->request->getMethod();
        $this->assertEmpty($method);
        if (is_string($method)) {
            $this->assertNotContains('Local file inclusion', $method);
        }
    }

    public function testShouldDisallowsDoctypeInRequestXmlAndReturnFalseOnLoading()
    {
        $payload = file_get_contents(dirname(__FILE__) . '/_files/Laminas12293-request.xml');
        $payload = sprintf($payload, 'file://' . realpath(dirname(__FILE__) . '/_files/Laminas12293-payload.txt'));
        $this->assertFalse($this->request->loadXml($payload));
    }
}
