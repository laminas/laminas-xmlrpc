<?php

/**
 * @see       https://github.com/laminas/laminas-xmlrpc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-xmlrpc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-xmlrpc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\XmlRpc;

use Laminas\XmlRpc\AbstractValue;
use Laminas\XmlRpc\Generator\GeneratorInterface as Generator;
use Laminas\XmlRpc\Value\BigInteger;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_XmlRpc
 */
class BigIntegerValueTest extends TestCase
{
    public function setUp()
    {
        if (extension_loaded('gmp')) {
            $this->markTestSkipped('gmp causes test failure');
        }
        try {
            $XmlRpcBigInteger = new BigInteger(0);
        } catch (\Laminas\Math\Exception $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    // BigInteger

    /**
     * @group Laminas-6445
     * @group Laminas-8623
     */
    public function testBigIntegerGetValue()
    {
        $bigIntegerValue = (string)(PHP_INT_MAX + 42);
        $bigInteger = new BigInteger($bigIntegerValue);
        $this->assertSame($bigIntegerValue, $bigInteger->getValue());
    }

    /**
     * @group Laminas-6445
     */
    public function testBigIntegerGetType()
    {
        $bigIntegerValue = (string)(PHP_INT_MAX + 42);
        $bigInteger = new BigInteger($bigIntegerValue);
        $this->assertSame(AbstractValue::XMLRPC_TYPE_I8, $bigInteger->getType());
    }

    /**
     * @group Laminas-6445
     */
    public function testBigIntegerGeneratedXml()
    {
        $bigIntegerValue = (string)(PHP_INT_MAX + 42);
        $bigInteger = new BigInteger($bigIntegerValue);

        $this->assertEquals(
            '<value><i8>' . $bigIntegerValue . '</i8></value>',
            $bigInteger->saveXml()
        );
    }

    /**
     * @group Laminas-6445
     * @dataProvider \LaminasTest\XmlRpc\TestProvider::provideGenerators
     */
    public function testMarschalBigIntegerFromXmlRpc(Generator $generator)
    {
        AbstractValue::setGenerator($generator);

        $bigIntegerValue = (string)(PHP_INT_MAX + 42);
        $bigInteger = new BigInteger($bigIntegerValue);
        $bigIntegerXml = '<value><i8>' . $bigIntegerValue . '</i8></value>';

        $value = AbstractValue::getXmlRpcValue(
            $bigIntegerXml,
            AbstractValue::XML_STRING
        );

        $this->assertSame($bigIntegerValue, $value->getValue());
        $this->assertEquals(AbstractValue::XMLRPC_TYPE_I8, $value->getType());
        $this->assertEquals($this->wrapXml($bigIntegerXml), $value->saveXml());
    }

    /**
     * @group Laminas-6445
     * @dataProvider \LaminasTest\XmlRpc\TestProvider::provideGenerators
     */
    public function testMarschalBigIntegerFromApacheXmlRpc(Generator $generator)
    {
        AbstractValue::setGenerator($generator);

        $bigIntegerValue = (string)(PHP_INT_MAX + 42);
        $bigInteger = new BigInteger($bigIntegerValue);
        $bigIntegerXml = '<value><ex:i8 xmlns:ex="http://ws.apache.org/xmlrpc/namespaces/extensions">'
            . $bigIntegerValue
            . '</ex:i8></value>';

        $value = AbstractValue::getXmlRpcValue(
            $bigIntegerXml,
            AbstractValue::XML_STRING
        );

        $this->assertSame($bigIntegerValue, $value->getValue());
        $this->assertEquals(AbstractValue::XMLRPC_TYPE_I8, $value->getType());
        $this->assertEquals($this->wrapXml($bigIntegerXml), $value->saveXml());
    }

    /**
     * @group Laminas-6445
     */
    public function testMarshalBigIntegerFromNative()
    {
        $bigIntegerValue = (string)(PHP_INT_MAX + 42);

        $value = AbstractValue::getXmlRpcValue(
            $bigIntegerValue,
            AbstractValue::XMLRPC_TYPE_I8
        );

        $this->assertEquals(AbstractValue::XMLRPC_TYPE_I8, $value->getType());
        $this->assertSame($bigIntegerValue, $value->getValue());
    }

    // Custom Assertions and Helper Methods

    public function wrapXml($xml)
    {
        return $xml . "\n";
    }
}
