<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc;

use Laminas\Math\BigInteger\BigInteger as MathBigInteger;
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

    /** @var string */
    protected $bigIntValue;

    protected function setUp(): void
    {
        if (! extension_loaded('gmp') && ! extension_loaded('bcmath')) {
            $this->markTestSkipped('BigInteger requires gmp or bcmath extension');
        }

        $this->useBigIntForI8Flag         = AbstractValue::$USE_BIGINT_FOR_I8;
        AbstractValue::$USE_BIGINT_FOR_I8 = true;
        $this->bigIntValue                = MathBigInteger::factory()
            ->add((string) PHP_INT_MAX, '42');
    }

    protected function tearDown(): void
    {
        AbstractValue::$USE_BIGINT_FOR_I8 = $this->useBigIntForI8Flag;
        $this->useBigIntForI8Flag         = null;
    }

    // BigInteger

    public function testBigIntegerGetValue(): void
    {
        $bigInteger = new BigInteger($this->bigIntValue);
        $this->assertSame($this->bigIntValue, $bigInteger->getValue());
    }

    /**
     * @group Laminas-6445
     */
    public function testBigIntegerGetType(): void
    {
        $bigInteger = new BigInteger($this->bigIntValue);
        $this->assertSame(AbstractValue::XMLRPC_TYPE_I8, $bigInteger->getType());
    }

    /**
     * @group Laminas-6445
     */
    public function testBigIntegerGeneratedXml(): void
    {
        $bigInteger = new BigInteger($this->bigIntValue);

        $this->assertEquals(
            '<value><i8>' . $this->bigIntValue . '</i8></value>',
            $bigInteger->saveXml()
        );
    }

    /**
     * @group Laminas-6445
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshallBigIntegerFromXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);

        $bigIntegerXml = '<value><i8>' . $this->bigIntValue . '</i8></value>';

        $value = AbstractValue::getXmlRpcValue(
            $bigIntegerXml,
            AbstractValue::XML_STRING
        );

        $this->assertSame($this->bigIntValue, $value->getValue());
        $this->assertEquals(AbstractValue::XMLRPC_TYPE_I8, $value->getType());
        $this->assertEquals($this->wrapXml($bigIntegerXml), $value->saveXml());
    }

    /**
     * @group Laminas-6445
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshallBigIntegerFromApacheXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);

        $bigIntegerXml = '<value><ex:i8 xmlns:ex="http://ws.apache.org/xmlrpc/namespaces/extensions">'
            . $this->bigIntValue
            . '</ex:i8></value>';

        $value = AbstractValue::getXmlRpcValue(
            $bigIntegerXml,
            AbstractValue::XML_STRING
        );

        $this->assertSame($this->bigIntValue, $value->getValue());
        $this->assertEquals(AbstractValue::XMLRPC_TYPE_I8, $value->getType());
        $this->assertEquals($this->wrapXml($bigIntegerXml), $value->saveXml());
    }

    /**
     * @group Laminas-6445
     */
    public function testMarshalBigIntegerFromNative(): void
    {
        $value = AbstractValue::getXmlRpcValue(
            $this->bigIntValue,
            AbstractValue::XMLRPC_TYPE_I8
        );

        $this->assertEquals(AbstractValue::XMLRPC_TYPE_I8, $value->getType());
        $this->assertSame($this->bigIntValue, $value->getValue());
    }

    public function wrapXml(string $xml): string
    {
        return $xml . "\n";
    }

    public function testMarshalsIntegerForI8ValueByDefaultIfSystemIs64Bit(): void
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
