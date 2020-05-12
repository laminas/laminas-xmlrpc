<?php

/**
 * @see       https://github.com/laminas/laminas-xmlrpc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-xmlrpc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-xmlrpc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\XmlRpc;

use Laminas\XmlRpc\AbstractValue;
use Laminas\XmlRpc\Exception;
use Laminas\XmlRpc\Fault;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_XmlRpc
 */
class FaultTest extends TestCase
{
    /**
     * @var XmlRpc\Fault
     */
    protected $fault;

    /**
     * Setup environment
     */
    protected function setUp(): void
    {
        AbstractValue::setGenerator(null);
        $this->fault = new Fault();
    }

    /**
     * Teardown environment
     */
    protected function tearDown(): void
    {
        unset($this->fault);
    }

    /**
     * __construct() test
     */
    public function testConstructor()
    {
        $this->assertInstanceOf('Laminas\XmlRpc\Fault', $this->fault);
        $this->assertEquals(404, $this->fault->getCode());
        $this->assertEquals('Unknown Error', $this->fault->getMessage());
    }

    /**
     * get/setCode() test
     */
    public function testCode()
    {
        $this->fault->setCode('1000');
        $this->assertEquals(1000, $this->fault->getCode());
    }

    /**
     * get/setMessage() test
     */
    public function testMessage()
    {
        $this->fault->setMessage('Message');
        $this->assertEquals('Message', $this->fault->getMessage());
    }

    protected function createXml()
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $response = $dom->appendChild($dom->createElement('methodResponse'));
        $fault  = $response->appendChild($dom->createElement('fault'));
        $value  = $fault->appendChild($dom->createElement('value'));
        $struct = $value->appendChild($dom->createElement('struct'));

        $member1 = $struct->appendChild($dom->createElement('member'));
        $member1->appendChild($dom->createElement('name', 'faultCode'));
        $value1 = $member1->appendChild($dom->createElement('value'));
        $value1->appendChild($dom->createElement('int', 1000));

        $member2 = $struct->appendChild($dom->createElement('member'));
        $member2->appendChild($dom->createElement('name', 'faultString'));
        $value2 = $member2->appendChild($dom->createElement('value'));
        $value2->appendChild($dom->createElement('string', 'Error string'));

        return $dom->saveXml();
    }

    protected function createNonStandardXml()
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $response = $dom->appendChild($dom->createElement('methodResponse'));
        $fault  = $response->appendChild($dom->createElement('fault'));
        $value  = $fault->appendChild($dom->createElement('value'));
        $struct = $value->appendChild($dom->createElement('struct'));

        $member1 = $struct->appendChild($dom->createElement('member'));
        $member1->appendChild($dom->createElement('name', 'faultCode'));
        $value1 = $member1->appendChild($dom->createElement('value'));
        $value1->appendChild($dom->createElement('int', 1000));

        $member2 = $struct->appendChild($dom->createElement('member'));
        $member2->appendChild($dom->createElement('name', 'faultString'));
        $value2 = $member2->appendChild($dom->createElement('value', 'Error string'));

        return $dom->saveXml();
    }

    /**
     * loadXml() test
     */
    public function testLoadXml()
    {
        $xml = $this->createXml();

        $parsed = $this->fault->loadXml($xml);
        $this->assertTrue($parsed, $xml);

        $this->assertEquals(1000, $this->fault->getCode());
        $this->assertEquals('Error string', $this->fault->getMessage());

        $this->assertFalse($this->fault->loadXml('<wellformedButInvalid/>'));

        $this->fault->loadXml('<methodResponse><fault><value><struct>'
            . '<member><name>faultString</name><value><string>str</string></value></member>'
            . '</struct></value></fault></methodResponse>');
        $this->assertSame(404, $this->fault->getCode(), 'If no fault code is given, use 404 as a default');

        $this->fault->loadXml('<methodResponse><fault><value><struct>'
            . '<member><name>faultCode</name><value><int>610</int></value></member>'
            . '</struct></value></fault></methodResponse>');
        $this->assertSame(
            'Invalid method class',
            $this->fault->getMessage(),
            'If empty fault string is given, resolve the code'
        );

        $this->fault->loadXml('<methodResponse><fault><value><struct>'
                . '<member><name>faultCode</name><value><int>1234</int></value></member>'
                . '</struct></value></fault></methodResponse>');
        $this->assertSame(
            'Unknown Error',
            $this->fault->getMessage(),
            'If code resolval failed, use "Unknown Error"'
        );
    }

    public function testLoadXmlThrowsExceptionOnInvalidInput()
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to parse XML fault');
        $parsed = $this->fault->loadXml('foo');
    }

    public function testLoadXmlThrowsExceptionOnInvalidInput2()
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid fault structure');
        $this->assertFalse($this->fault->loadXml('<methodResponse><fault/></methodResponse>'));
    }

    public function testLoadXmlThrowsExceptionOnInvalidInput3()
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid fault structure');
        $this->fault->loadXml('<methodResponse><fault/></methodResponse>');
    }

    public function testLoadXmlThrowsExceptionOnInvalidInput4()
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fault code and string required');
        $this->fault->loadXml('<methodResponse><fault><value><struct/></value></fault></methodResponse>');
    }

    /**
     * Laminas\XmlRpc\Fault::isFault() test
     */
    public function testIsFault()
    {
        $xml = $this->createXml();

        $this->assertTrue(Fault::isFault($xml), $xml);
        $this->assertFalse(Fault::isFault('foo'));
        $this->assertFalse(Fault::isFault(['foo']));
    }

    /**
     * helper for saveXml() and __toString() tests
     *
     * @param string $xml
     * @return void
     */
    protected function assertXmlFault($xml)
    {
        $sx = new \SimpleXMLElement($xml);

        $this->assertNotFalse($sx->fault, $xml);
        $this->assertNotFalse($sx->fault->value, $xml);
        $this->assertNotFalse($sx->fault->value->struct, $xml);
        $count = 0;
        foreach ($sx->fault->value->struct->member as $member) {
            $count++;
            $this->assertNotFalse($member->name, $xml);
            $this->assertNotFalse($member->value, $xml);
            if ('faultCode' == (string) $member->name) {
                $this->assertNotFalse($member->value->int, $xml);
                $this->assertEquals(1000, (int) $member->value->int, $xml);
            }
            if ('faultString' == (string) $member->name) {
                $this->assertNotFalse($member->value->string, $xml);
                $this->assertEquals('Fault message', (string) $member->value->string, $xml);
            }
        }

        $this->assertEquals(2, $count, $xml);
    }

    /**
     * saveXml() test
     */
    public function testSaveXML()
    {
        $this->fault->setCode(1000);
        $this->fault->setMessage('Fault message');
        $xml = $this->fault->saveXml();
        $this->assertXmlFault($xml);
    }

    /**
     * __toString() test
     */
    public function testCanCastFaultToString()
    {
        $this->fault->setCode(1000);
        $this->fault->setMessage('Fault message');
        $xml = $this->fault->__toString();
        $this->assertXmlFault($xml);
    }

    /**
     * Test encoding settings
     */
    public function testSetGetEncoding()
    {
        $this->assertEquals('UTF-8', $this->fault->getEncoding());
        $this->assertEquals('UTF-8', AbstractValue::getGenerator()->getEncoding());
        $this->fault->setEncoding('ISO-8859-1');
        $this->assertEquals('ISO-8859-1', $this->fault->getEncoding());
        $this->assertEquals('ISO-8859-1', AbstractValue::getGenerator()->getEncoding());
    }

    public function testUnknownErrorIsUsedIfUnknownErrorCodeEndEmptyMessageIsPassed()
    {
        $fault = new Fault(1234);
        $this->assertSame(1234, $fault->getCode());
        $this->assertSame('Unknown Error', $fault->getMessage());
    }

    public function testFaultStringWithoutStringTypeDeclaration()
    {
        $xml = $this->createNonStandardXml();

        $parsed = $this->fault->loadXml($xml);
        $this->assertTrue($parsed, $xml);
        $this->assertEquals('Error string', $this->fault->getMessage());
    }
}
