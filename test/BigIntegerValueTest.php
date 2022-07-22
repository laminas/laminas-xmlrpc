<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc;

use Laminas\XmlRpc\AbstractValue;
use Laminas\XmlRpc\Generator\GeneratorInterface as Generator;
use Laminas\XmlRpc\Value\BigInteger;
use PHPUnit\Framework\TestCase;

use function extension_loaded;

use const PHP_INT_MAX;

/**
 * @group      Laminas_XmlRpc
 */
class BigIntegerValueTest extends TestCase
{
    /** @var null|bool */
    protected $useBigIntForI8Flag;

    protected function setUp(): void
    {
        $this->useBigIntForI8Flag         = AbstractValue::$USE_BIGINT_FOR_I8;
        AbstractValue::$USE_BIGINT_FOR_I8 = true;

        if (extension_loaded('gmp')) {
            $this->markTestSkipped('gmp causes test failure');
        }
        try {
            new BigInteger(0);
        } catch (Exception $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        AbstractValue::$USE_BIGINT_FOR_I8 = $this->useBigIntForI8Flag;
        $this->useBigIntForI8Flag         = null;
    }

    // BigInteger

    /**
     * @group Laminas-6445
     * @group Laminas-8623
     */
    public function testBigIntegerGetValue(): void
    {
        $bigIntegerValue = (string) (PHP_INT_MAX + 42);
        $bigInteger      = new BigInteger($bigIntegerValue);
        $this->assertSame($bigIntegerValue, $bigInteger->getValue());
    }

    /**
     * @group Laminas-6445
     */
    public function testBigIntegerGetType(): void
    {
        $bigIntegerValue = (string) (PHP_INT_MAX + 42);
        $bigInteger      = new BigInteger($bigIntegerValue);
        $this->assertSame(AbstractValue::XMLRPC_TYPE_I8, $bigInteger->getType());
    }

    /**
     * @group Laminas-6445
     */
    public function testBigIntegerGeneratedXml(): void
    {
        $bigIntegerValue = (string) (PHP_INT_MAX + 42);
        $bigInteger      = new BigInteger($bigIntegerValue);

        $this->assertEquals(
            '<value><i8>' . $bigIntegerValue . '</i8></value>',
            $bigInteger->saveXml()
        );
    }

    /**
     * @group Laminas-6445
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarschalBigIntegerFromXmlRpc(Generator $generator)
    {
        AbstractValue::setGenerator($generator);

        $bigIntegerValue = (string) (PHP_INT_MAX + 42);
        $bigInteger      = new BigInteger($bigIntegerValue);
        $bigIntegerXml   = '<value><i8>' . $bigIntegerValue . '</i8></value>';

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
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarschalBigIntegerFromApacheXmlRpc(Generator $generator)
    {
        AbstractValue::setGenerator($generator);

        $bigIntegerValue = (string) (PHP_INT_MAX + 42);
        $bigInteger      = new BigInteger($bigIntegerValue);
        $bigIntegerXml   = '<value><ex:i8 xmlns:ex="http://ws.apache.org/xmlrpc/namespaces/extensions">'
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
    public function testMarshalBigIntegerFromNative(): void
    {
        $bigIntegerValue = (string) (PHP_INT_MAX + 42);

        $value = AbstractValue::getXmlRpcValue(
            $bigIntegerValue,
            AbstractValue::XMLRPC_TYPE_I8
        );

        $this->assertEquals(AbstractValue::XMLRPC_TYPE_I8, $value->getType());
        $this->assertSame($bigIntegerValue, $value->getValue());
    }

    /**
     * @param string $xml
     * @return string
     */
    public function wrapXml($xml)
    {
        return $xml . "\n";
    }

    public function testMarshalsIntegerForI8ValueByDefaultIfSystemIs64Bit()
    {
        if ($this->useBigIntForI8Flag) {
            $this->markTestSkipped('Test only valid for 64bit systems');
        }

        AbstractValue::$USE_BIGINT_FOR_I8 = $this->useBigIntForI8Flag;
        $integerValue                     = PHP_INT_MAX;

        $value = AbstractValue::getXmlRpcValue(
            $integerValue,
            AbstractValue::XMLRPC_TYPE_I8
        );

        $this->assertEquals(AbstractValue::XMLRPC_TYPE_INTEGER, $value->getType());
        $this->assertSame($integerValue, $value->getValue());
    }
}
