<?php

namespace LaminasTest\XmlRpc;

use DOMDocument;
use Laminas\XmlRpc\AbstractValue;
use Laminas\XmlRpc\Fault;
use Laminas\XmlRpc\Response;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use stdClass;

use function dirname;
use function file_get_contents;
use function is_string;
use function realpath;
use function set_error_handler;
use function sprintf;

/**
 * @group      Laminas_XmlRpc
 */
class ResponseTest extends TestCase
{
    /**
     * Response object
     *
     * @var Response
     */
    protected $response;

    /** @var bool */
    protected $errorOccurred = false;

    /**
     * Setup environment
     */
    protected function setUp(): void
    {
        $this->response = new Response();
    }

    /**
     * Teardown environment
     */
    protected function tearDown(): void
    {
        unset($this->response);
    }

    /**
     * get/setReturnValue() test
     */
    public function testReturnValue()
    {
        $this->response->setReturnValue('string');
        $this->assertEquals('string', $this->response->getReturnValue());

        $this->response->setReturnValue(['one', 'two']);
        $this->assertSame(['one', 'two'], $this->response->getReturnValue());
    }

    /**
     * isFault() test
     *
     * Call as method call
     *
     * Returns: bool
     */
    public function testIsFault()
    {
        $this->assertFalse($this->response->isFault());
        $this->response->loadXml('foo');
        $this->assertTrue($this->response->isFault());
    }

    /**
     * Tests getFault() returns NULL (no fault) or the fault object
     */
    public function testGetFault()
    {
        $this->assertNull($this->response->getFault());
        $this->response->loadXml('foo');
        $this->assertInstanceOf(Fault::class, $this->response->getFault());
    }

    /**
     * loadXml() test
     *
     * Call as method call
     *
     * Expects:
     * - response:
     *
     * Returns: bool
     */
    public function testLoadXml()
    {
        $dom      = new DOMDocument('1.0', 'UTF-8');
        $response = $dom->appendChild($dom->createElement('methodResponse'));
        $params   = $response->appendChild($dom->createElement('params'));
        $param    = $params->appendChild($dom->createElement('param'));
        $value    = $param->appendChild($dom->createElement('value'));
        $value->appendChild($dom->createElement('string', 'Return value'));

        $xml = $dom->saveXml();

        $parsed = $this->response->loadXml($xml);
        $this->assertTrue($parsed, $xml);
        $this->assertEquals('Return value', $this->response->getReturnValue());
    }

    public function testLoadXmlWithInvalidValue()
    {
        $this->assertFalse($this->response->loadXml(new stdClass()));
        $this->assertTrue($this->response->isFault());
        $this->assertSame(650, $this->response->getFault()->getCode());
    }

    /**
     * @group Laminas-9039
     */
    public function testExceptionIsThrownWhenInvalidXmlIsReturnedByServer()
    {
        set_error_handler([$this, 'trackError']);
        $invalidResponse = 'foo';
        $response        = new Response();
        $this->assertFalse($this->errorOccurred);
        $this->assertFalse($response->loadXml($invalidResponse));
        $this->assertFalse($this->errorOccurred);
    }

    /**
     * @group Laminas-5404
     */
    public function testNilResponseFromXmlRpcServer()
    {
        // @codingStandardsIgnoreStart
        $rawResponse = <<<EOD
<methodResponse><params><param><value><array><data><value><struct><member><name>id</name><value><string>1</string></value></member><member><name>name</name><value><string>birdy num num!</string></value></member><member><name>description</name><value><nil/></value></member></struct></value></data></array></value></param></params></methodResponse>
EOD;
        // @codingStandardsIgnoreEnd

        $response = new Response();
        $ret      = $response->loadXml($rawResponse);

        $this->assertTrue($ret);
        $this->assertEquals([
            0 => [
                'id'          => 1,
                'name'        => 'birdy num num!',
                'description' => null,
            ],
        ], $response->getReturnValue());
    }

    /**
     * helper for saveXml() and __toString() tests
     *
     * @param string $xml
     * @return void
     */
    protected function assertXmlResponse($xml)
    {
        $sx = new SimpleXMLElement($xml);

        $this->assertNotFalse($sx->params);
        $this->assertNotFalse($sx->params->param);
        $this->assertNotFalse($sx->params->param->value);
        $this->assertNotFalse($sx->params->param->value->string);
        $this->assertEquals('return value', (string) $sx->params->param->value->string);
    }

    /**
     * saveXml() test
     */
    public function testSaveXML()
    {
        $this->response->setReturnValue('return value');
        $xml = $this->response->saveXml();
        $this->assertXmlResponse($xml);
    }

    /**
     * __toString() test
     */
    public function testCanCastResponseToString()
    {
        $this->response->setReturnValue('return value');
        $xml = $this->response->__toString();
        $this->assertXmlResponse($xml);
    }

    /**
     * Test encoding settings
     */
    public function testSetGetEncoding()
    {
        $this->assertEquals('UTF-8', $this->response->getEncoding());
        $this->assertEquals('UTF-8', AbstractValue::getGenerator()->getEncoding());
        $this->assertSame($this->response, $this->response->setEncoding('ISO-8859-1'));
        $this->assertEquals('ISO-8859-1', $this->response->getEncoding());
        $this->assertEquals('ISO-8859-1', AbstractValue::getGenerator()->getEncoding());
    }

    public function testLoadXmlCreatesFaultWithMissingNodes()
    {
        $sxl = new SimpleXMLElement(
            '<?xml version="1.0"?><methodResponse><params><param>foo</param></params></methodResponse>'
        );

        $this->assertFalse($this->response->loadXml($sxl->asXML()));
        $this->assertTrue($this->response->isFault());
        $fault = $this->response->getFault();
        $this->assertEquals(653, $fault->getCode());
    }

    public function testLoadXmlCreatesFaultWithMissingNodes2()
    {
        $sxl = new SimpleXMLElement('<?xml version="1.0"?><methodResponse><params>foo</params></methodResponse>');

        $this->assertFalse($this->response->loadXml($sxl->asXML()));
        $this->assertTrue($this->response->isFault());
        $fault = $this->response->getFault();
        $this->assertEquals(653, $fault->getCode());
    }

    public function testLoadXmlThrowsExceptionWithMissingNodes3()
    {
        $sxl = new SimpleXMLElement('<?xml version="1.0"?><methodResponse><bar>foo</bar></methodResponse>');

        $this->assertFalse($this->response->loadXml($sxl->asXML()));
        $this->assertTrue($this->response->isFault());
        $fault = $this->response->getFault();
        $this->assertEquals(652, $fault->getCode());
    }

    /**
     * @param mixed $error
     */
    public function trackError($error): void
    {
        $this->errorOccurred = true;
    }

    /**
     * @group Laminas-12293
     */
    public function testDoesNotAllowExternalEntities()
    {
        $payload = file_get_contents(dirname(__FILE__) . '/_files/Laminas12293-response.xml');
        $payload = sprintf($payload, 'file://' . realpath(dirname(__FILE__) . '/_files/Laminas12293-payload.txt'));
        $this->response->loadXml($payload);
        $value = $this->response->getReturnValue();
        $this->assertEmpty($value);
        if (is_string($value)) {
            $this->assertNotContains('Local file inclusion', $value);
        }
    }

    public function testShouldDisallowsDoctypeInRequestXmlAndReturnFalseOnLoading()
    {
        $payload = file_get_contents(dirname(__FILE__) . '/_files/Laminas12293-response.xml');
        $payload = sprintf($payload, 'file://' . realpath(dirname(__FILE__) . '/_files/Laminas12293-payload.txt'));
        $this->assertFalse($this->response->loadXml($payload));
    }
}
