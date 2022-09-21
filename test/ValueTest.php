<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc;

use DateTime;
use Laminas\XmlRpc\AbstractValue;
use Laminas\XmlRpc\Exception\InvalidArgumentException;
use Laminas\XmlRpc\Exception\ValueException;
use Laminas\XmlRpc\Generator\GeneratorInterface as Generator;
use Laminas\XmlRpc\Value;
use PHPUnit\Framework\TestCase;
use stdClass;

use function base64_encode;
use function fopen;
use function ini_get;
use function serialize;
use function strtotime;
use function trim;
use function unserialize;
use function var_dump;

use const PHP_INT_MAX;

/**
 * Test case for Value
 *
 * @group      Laminas_XmlRpc
 */
class ValueTest extends TestCase
{
    /** @var string */
    public $xmlRpcDateFormat = 'Ymd\\TH:i:s';

    public function testFactoryAutodetectsBoolean(): void
    {
        foreach ([true, false] as $native) {
            $val = AbstractValue::getXmlRpcValue($native);
            $this->assertXmlRpcType('boolean', $val);
            $this->assertEquals($native, $val->getValue());
        }
    }

    public function testMarshalTrueBooleanFromNative(): void
    {
        $native = true;
        $val    = AbstractValue::getXmlRpcValue(
            $native,
            AbstractValue::XMLRPC_TYPE_BOOLEAN
        );

        $this->assertXmlRpcType('boolean', $val);
        $this->assertSame($native, $val->getValue());
        $this->assertTrue($val->getValue());
    }

    public function testMarshalTrueIntBooleanFromNative(): void
    {
        $native = 1;
        $val    = AbstractValue::getXmlRpcValue(
            $native,
            AbstractValue::XMLRPC_TYPE_BOOLEAN
        );

        $this->assertXmlRpcType('boolean', $val);
        $this->assertSame(true, $val->getValue());
        $this->assertTrue($val->getValue());
    }

    public function testMarshalFalseBooleanFromNative(): void
    {
        $native = false;
        $val    = AbstractValue::getXmlRpcValue(
            $native,
            AbstractValue::XMLRPC_TYPE_BOOLEAN
        );

        $this->assertXmlRpcType('boolean', $val);
        $this->assertSame($native, $val->getValue());
        $this->assertFalse($val->getValue());
    }

    public function testMarshalFalseIntBooleanFromNative(): void
    {
        $native = 0;
        $val    = AbstractValue::getXmlRpcValue(
            $native,
            AbstractValue::XMLRPC_TYPE_BOOLEAN
        );

        echo $val->getValue();

        $this->assertXmlRpcType('boolean', $val);
        $this->assertSame(false, $val->getValue());
        $this->assertFalse($val->getValue());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshalTrueBooleanFromXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $xml = '<value><boolean>true</boolean></value>';
        $val = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('boolean', $val);
        $this->assertEquals('boolean', $val->getType());
        $this->assertSame(true, $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshalTrueIntBooleanFromXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $xml = '<value><boolean>1</boolean></value>';
        $val = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('boolean', $val);
        $this->assertEquals('boolean', $val->getType());
        $this->assertSame(true, $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshalFalseBooleanFromXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $xml = '<value><boolean>false</boolean></value>';
        $val = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('boolean', $val);
        $this->assertEquals('boolean', $val->getType());
        $this->assertSame(false, $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshalFalseIntBooleanFromXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $xml = '<value><boolean>0</boolean></value>';
        $val = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('boolean', $val);
        $this->assertEquals('boolean', $val->getType());
        $this->assertSame(false, $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());
    }

    public function testFactoryAutodetectsInteger(): void
    {
        $val = AbstractValue::getXmlRpcValue(1);
        $this->assertXmlRpcType('integer', $val);
    }

    public function testMarshalIntegerFromNative(): void
    {
        $native = 1;
        $types  = [
            AbstractValue::XMLRPC_TYPE_I4,
            AbstractValue::XMLRPC_TYPE_INTEGER,
        ];

        foreach ($types as $type) {
            $val = AbstractValue::getXmlRpcValue($native, $type);
            $this->assertXmlRpcType('integer', $val);
            $this->assertSame($native, $val->getValue());
        }
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshalIntegerFromXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);

        $native = 1;
        $xmls   = [
            "<value><int>$native</int></value>",
            "<value><i4>$native</i4></value>",
        ];

        foreach ($xmls as $xml) {
            $val = AbstractValue::getXmlRpcValue(
                $xml,
                AbstractValue::XML_STRING
            );
            $this->assertXmlRpcType('integer', $val);
            $this->assertEquals('int', $val->getType());
            $this->assertSame($native, $val->getValue());
            $this->assertEquals($this->wrapXml($xml), $val->saveXml());
        }
    }

    /**
     * @group Laminas-3310
     */
    public function testMarshalI4FromOverlongNativeThrowsException(): void
    {
        $this->expectException(ValueException::class);
        $this->expectExceptionMessage('Overlong integer given');
        $x = AbstractValue::getXmlRpcValue(PHP_INT_MAX + 5000, AbstractValue::XMLRPC_TYPE_I4);
        var_dump($x);
    }

    /**
     * @group Laminas-3310
     */
    public function testMarshalIntegerFromOverlongNativeThrowsException(): void
    {
        $this->expectException(ValueException::class);
        $this->expectExceptionMessage('Overlong integer given');
        AbstractValue::getXmlRpcValue(PHP_INT_MAX + 5000, AbstractValue::XMLRPC_TYPE_INTEGER);
    }

    public function testFactoryAutodetectsFloat(): void
    {
        $val = AbstractValue::getXmlRpcValue((float) 1);
        $this->assertXmlRpcType('double', $val);
    }

    public function testMarshalDoubleFromNative(): void
    {
        $native = 1.1;
        $val    = AbstractValue::getXmlRpcValue(
            $native,
            AbstractValue::XMLRPC_TYPE_DOUBLE
        );

        $this->assertXmlRpcType('double', $val);
        $this->assertSame($native, $val->getValue());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshalDoubleFromXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $native = 1.1;
        $xml    = "<value><double>$native</double></value>";
        $val    = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('double', $val);
        $this->assertEquals('double', $val->getType());
        $this->assertSame($native, $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());
    }

    /**
     * @group Laminas-7712
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshallingDoubleWithHigherPrecisionFromNative(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        if (ini_get('precision') < 7) {
            $this->markTestSkipped('precision is too low');
        }

        $native = 0.1234567;
        $value  = AbstractValue::getXmlRpcValue($native, AbstractValue::XMLRPC_TYPE_DOUBLE);
        $this->assertXmlRpcType('double', $value);
        $this->assertSame($native, $value->getValue());
        $this->assertSame('<value><double>0.1234567</double></value>', trim($value->saveXml()));
    }

    /**
     * @group Laminas-7712
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshallingDoubleWithHigherPrecisionFromNativeWithTrailingZeros(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        if (ini_get('precision') < 7) {
            $this->markTestSkipped('precision is too low');
        }
        $native = 0.1;
        $value  = AbstractValue::getXmlRpcValue($native, AbstractValue::XMLRPC_TYPE_DOUBLE);
        $this->assertXmlRpcType('double', $value);
        $this->assertSame($native, $value->getValue());
        $this->assertSame('<value><double>0.1</double></value>', trim($value->saveXml()));
    }

    public function testFactoryAutodetectsString(): void
    {
        $val = AbstractValue::getXmlRpcValue('');
        $this->assertXmlRpcType('string', $val);
    }

    public function testMarshalStringFromNative(): void
    {
        $native = 'foo';
        $val    = AbstractValue::getXmlRpcValue(
            $native,
            AbstractValue::XMLRPC_TYPE_STRING
        );

        $this->assertXmlRpcType('string', $val);
        $this->assertSame($native, $val->getValue());
    }

    public function testFactoryAutodetectsStringAndSetsValueInArray(): void
    {
        $val = AbstractValue::getXmlRpcValue(
            '<value><array><data>'
            . '<value><i4>8</i4></value>'
            . '<value>a</value>'
            . '<value>false</value>'
            . '</data></array></value>',
            AbstractValue::XML_STRING
        );
        $this->assertXmlRpcType('array', $val);
        $a = $val->getValue();
        $this->assertSame(8, $a[0]);
        $this->assertSame('a', $a[1]);
        $this->assertSame('false', $a[2]);
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshalStringFromXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $native = 'foo<>';
        $xml    = "<value><string>foo&lt;&gt;</string></value>";
        $val    = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('string', $val);
        $this->assertEquals('string', $val->getType());
        $this->assertSame($native, $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshalStringFromDefault(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $native = 'foo<br/>bar';
        $xml    = "<string>foo&lt;br/&gt;bar</string>";
        $val    = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('string', $val);
        $this->assertEquals('string', $val->getType());
        $this->assertSame($native, $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());
    }

    public function testFactoryAutodetectsNil(): void
    {
        $val = AbstractValue::getXmlRpcValue(null);
        $this->assertXmlRpcType('nil', $val);
    }

    public function testMarshalNilFromNative(): void
    {
        $native = null;
        $types  = [
            AbstractValue::XMLRPC_TYPE_NIL,
            AbstractValue::XMLRPC_TYPE_APACHENIL,
        ];
        foreach ($types as $type) {
            $value = AbstractValue::getXmlRpcValue($native, $type);

            $this->assertXmlRpcType('nil', $value);
            $this->assertSame($native, $value->getValue());
        }
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshalNilFromXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $xmls = [
            '<value><nil/></value>',
            '<value><ex:nil xmlns:ex="http://ws.apache.org/xmlrpc/namespaces/extensions"/></value>',
        ];

        foreach ($xmls as $xml) {
            $val = AbstractValue::getXmlRpcValue(
                $xml,
                AbstractValue::XML_STRING
            );
            $this->assertXmlRpcType('nil', $val);
            $this->assertEquals('nil', $val->getType());
            $this->assertSame(null, $val->getValue());
            $this->assertEquals($this->wrapXml($xml), $val->saveXml());
        }
    }

    public function testFactoryAutodetectsArray(): void
    {
        $val = AbstractValue::getXmlRpcValue([0, 'foo']);
        $this->assertXmlRpcType('array', $val);
    }

    public function testMarshalArrayFromNative(): void
    {
        $native = [0, 1];
        $val    = AbstractValue::getXmlRpcValue(
            $native,
            AbstractValue::XMLRPC_TYPE_ARRAY
        );

        $this->assertXmlRpcType('array', $val);
        $this->assertSame($native, $val->getValue());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshalArrayFromXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $native = [0, 1];
        $xml    = '<value><array><data><value><int>0</int></value>'
             . '<value><int>1</int></value></data></array></value>';

        $val = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('array', $val);
        $this->assertEquals('array', $val->getType());
        $this->assertSame($native, $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testEmptyXmlRpcArrayResultsInEmptyArray(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $native = [];
        $xml    = '<value><array><data/></array></value>';

        $val = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('array', $val);
        $this->assertEquals('array', $val->getType());
        $this->assertSame($native, $val->getValue());

        $value = AbstractValue::getXmlRpcValue($xml, AbstractValue::XML_STRING);
        $this->assertXmlRpcType('array', $value);
        $this->assertEquals('array', $value->getType());
        $this->assertSame($native, $value->getValue());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testArrayMustContainDataElement(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $native = [];
        $xml    = '<value><array/></value>';

        $this->expectException(ValueException::class);
        $this->expectExceptionMessage(
            'Invalid XML for XML-RPC native array type: ARRAY tag must contain DATA tag'
        );
        $val = AbstractValue::getXmlRpcValue($xml, AbstractValue::XML_STRING);
    }

    public function testFactoryAutodetectsStruct(): void
    {
        $val = AbstractValue::getXmlRpcValue(['foo' => 0]);
        $this->assertXmlRpcType('struct', $val);
    }

    public function testFactoryAutodetectsStructFromObject(): void
    {
        $val = AbstractValue::getXmlRpcValue((object) ['foo' => 0]);
        $this->assertXmlRpcType('struct', $val);
    }

    public function testMarshalStructFromNative(): void
    {
        $native = ['foo' => 0];
        $val    = AbstractValue::getXmlRpcValue(
            $native,
            AbstractValue::XMLRPC_TYPE_STRUCT
        );

        $this->assertXmlRpcType('struct', $val);
        $this->assertSame($native, $val->getValue());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshalStructFromXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $native = ['foo' => 0, 'bar' => 'foo<>bar'];
        $xml    = '<value><struct><member><name>foo</name><value><int>0</int>'
             . '</value></member><member><name>bar</name><value><string>'
             . 'foo&lt;&gt;bar</string></value></member></struct></value>';

        $val = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('struct', $val);
        $this->assertEquals('struct', $val->getType());
        $this->assertSame($native, $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshallingNestedStructFromXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $native = ['foo' => ['bar' => '<br/>']];
        $xml    = '<value><struct><member><name>foo</name><value><struct><member>'
             . '<name>bar</name><value><string>&lt;br/&gt;</string></value>'
             . '</member></struct></value></member></struct></value>';

        $val = AbstractValue::getXmlRpcValue($xml, AbstractValue::XML_STRING);

        $this->assertXmlRpcType('struct', $val);
        $this->assertEquals('struct', $val->getType());
        $this->assertSame($native, $val->getValue());
        $this->assertSame($this->wrapXml($xml), $val->saveXml());

        $val = AbstractValue::getXmlRpcValue($native);
        $this->assertSame(trim($xml), trim($val->saveXml()));
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshallingStructWithMemberWithoutValue(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $native = ['foo' => 0, 'bar' => 1];
        $xml    = '<value><struct>'
             . '<member><name>foo</name><value><int>0</int></value></member>'
             . '<member><name>foo</name><bar/></member>'
             . '<member><name>bar</name><value><int>1</int></value></member>'
             . '</struct></value>';

        $val = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('struct', $val);
        $this->assertEquals('struct', $val->getType());
        $this->assertSame($native, $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshallingStructWithMemberWithoutName(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $native = ['foo' => 0, 'bar' => 1];
        $xml    = '<value><struct>'
             . '<member><name>foo</name><value><int>0</int></value></member>'
             . '<member><value><string>foo</string></value></member>'
             . '<member><name>bar</name><value><int>1</int></value></member>'
             . '</struct></value>';

        $val = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('struct', $val);
        $this->assertEquals('struct', $val->getType());
        $this->assertSame($native, $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());
    }

    /**
     * @group Laminas-7639
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshalStructFromXmlRpcWithEntities(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $native = ['&nbsp;' => 0];
        $xml    = '<value><struct><member><name>&amp;nbsp;</name><value><int>0</int>'
             . '</value></member></struct></value>';
        $val    = AbstractValue::getXmlRpcValue($xml, AbstractValue::XML_STRING);
        $this->assertXmlRpcType('struct', $val);
        $this->assertSame($native, $val->getValue());
        $this->assertSame($this->wrapXml($xml), $val->saveXml());
    }

    /**
     * @group Laminas-3947
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshallingStructsWithEmptyValueFromXmlRpcShouldRetainKeys(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $native = ['foo' => ''];
        $xml    = '<value><struct><member><name>foo</name>'
             . '<value><string/></value></member></struct></value>';

        $val = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('struct', $val);
        $this->assertEquals('struct', $val->getType());
        $this->assertSame($native, $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshallingStructWithMultibyteValueFromXmlRpcRetainsMultibyteValue(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $native  = ['foo' => 'ß'];
        $xmlDecl = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml     = '<value><struct><member><name>foo</name><value><string>ß</string></value></member></struct></value>';

        $val = AbstractValue::getXmlRpcValue(
            $xmlDecl . $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('struct', $val);
        $this->assertEquals('struct', $val->getType());
        $this->assertSame($native, $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());

        $val = AbstractValue::getXmlRpcValue($native, AbstractValue::XMLRPC_TYPE_STRUCT);
        $this->assertSame($native, $val->getValue());
        $this->assertSame(trim($xml), trim($val->saveXml()));
    }

    public function testMarshalDateTimeFromNativeString(): void
    {
        $native = '1997-07-16T19:20+01:00';
        $val    = AbstractValue::getXmlRpcValue(
            $native,
            AbstractValue::XMLRPC_TYPE_DATETIME
        );

        $this->assertXmlRpcType('dateTime', $val);

        $expected = new DateTime($native);
        $this->assertSame($expected->format($this->xmlRpcDateFormat), $val->getValue());
    }

    public function testMarshalDateTimeFromNativeStringProducesIsoOutput(): void
    {
        $native = '1997-07-16T19:20+01:00';
        $val    = AbstractValue::getXmlRpcValue(
            $native,
            AbstractValue::XMLRPC_TYPE_DATETIME
        );

        $this->assertXmlRpcType('dateTime', $val);

        $expected = new DateTime($native);
        $received = $val->getValue();
        $this->assertEquals($expected->format($this->xmlRpcDateFormat), $received);
    }

    public function testMarshalDateTimeFromInvalidString(): void
    {
        $this->expectException(ValueException::class);
        $this->expectExceptionMessage('The timezone could not be found in the database');
        AbstractValue::getXmlRpcValue('foobarbaz', AbstractValue::XMLRPC_TYPE_DATETIME);
    }

    public function testMarshalDateTimeFromNativeInteger(): void
    {
        $native = strtotime('1997-07-16T19:20+01:00');
        $val    = AbstractValue::getXmlRpcValue(
            $native,
            AbstractValue::XMLRPC_TYPE_DATETIME
        );

        $this->assertXmlRpcType('dateTime', $val);
        $this->assertSame($native, strtotime($val->getValue()));
    }

    /**
     * @group Laminas-11588
     */
    public function testMarshalDateTimeBeyondUnixEpochFromNativeStringPassedToConstructor(): void
    {
        $native   = '2040-01-01T00:00:00';
        $value    = new Value\DateTime($native);
        $expected = new DateTime($native);
        $this->assertSame($expected->format($this->xmlRpcDateFormat), $value->getValue());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshalDateTimeFromXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $iso8601 = '1997-07-16T19:20+01:00';
        $xml     = "<value><dateTime.iso8601>$iso8601</dateTime.iso8601></value>";

        $val = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('dateTime', $val);
        $this->assertEquals('dateTime.iso8601', $val->getType());
        $expected = new DateTime($iso8601);
        $this->assertSame($expected->format($this->xmlRpcDateFormat), $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     * @group Laminas-4249
     */
    public function testMarshalDateTimeFromFromDateTime(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $dateString = '20390418T13:14:15';
        $date       = new DateTime($dateString);
        $dateString = '20390418T13:14:15';
        $xml        = "<value><dateTime.iso8601>$dateString</dateTime.iso8601></value>";

        $val = AbstractValue::getXmlRpcValue($date, AbstractValue::XMLRPC_TYPE_DATETIME);
        $this->assertXmlRpcType('dateTime', $val);
        $this->assertEquals('dateTime.iso8601', $val->getType());
        $this->assertSame($dateString, $val->getValue());
        $this->assertEquals(trim($xml), trim($val->saveXml()));
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     * @group Laminas-4249
     */
    public function testMarshalDateTimeFromDateTimeAndAutodetectingType(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $dateString = '20390418T13:14:15';
        $date       = new DateTime($dateString);
        $xml        = "<value><dateTime.iso8601>$dateString</dateTime.iso8601></value>";

        $val = AbstractValue::getXmlRpcValue($date, AbstractValue::AUTO_DETECT_TYPE);
        $this->assertXmlRpcType('dateTime', $val);
        $this->assertEquals('dateTime.iso8601', $val->getType());
        $this->assertSame($dateString, $val->getValue());
        $this->assertEquals(trim($xml), trim($val->saveXml()));
    }

    /**
     * @group Laminas-10776
     */
    public function testGetValueDatetime(): void
    {
        $expectedValue = '20100101T00:00:00';
        $phpDatetime   = new DateTime('20100101T00:00:00');
        $phpDateNative = '20100101T00:00:00';

        $xmlRpcValueDateTime = new Value\DateTime($phpDatetime);
        $this->assertEquals($expectedValue, $xmlRpcValueDateTime->getValue());

        $xmlRpcValueDateTime = new Value\DateTime($phpDateNative);
        $this->assertEquals($expectedValue, $xmlRpcValueDateTime->getValue());
    }

    public function testMarshalBase64FromString()
    {
        $native = 'foo';
        $val    = AbstractValue::getXmlRpcValue(
            $native,
            AbstractValue::XMLRPC_TYPE_BASE64
        );

        $this->assertXmlRpcType('base64', $val);
        $this->assertSame($native, $val->getValue());
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testMarshalBase64FromXmlRpc(Generator $generator): void
    {
        AbstractValue::setGenerator($generator);
        $native = 'foo';
        $xml    = '<value><base64>' . base64_encode($native) . '</base64></value>';

        $val = AbstractValue::getXmlRpcValue(
            $xml,
            AbstractValue::XML_STRING
        );

        $this->assertXmlRpcType('base64', $val);
        $this->assertEquals('base64', $val->getType());
        $this->assertSame($native, $val->getValue());
        $this->assertEquals($this->wrapXml($xml), $val->saveXml());
    }

    public function testXmlRpcValueBase64GeneratedXmlContainsBase64EncodedText(): void
    {
        $native = 'foo';
        $val    = AbstractValue::getXmlRpcValue(
            $native,
            AbstractValue::XMLRPC_TYPE_BASE64
        );

        $this->assertXmlRpcType('base64', $val);
        $xml     = $val->saveXml();
        $encoded = base64_encode($native);
        $this->assertStringContainsString($encoded, $xml);
    }

    /**
     * @group Laminas-3862
     */
    public function testMarshalSerializedObjectAsBase64(): void
    {
        $o = new TestAsset\SerializableTestClass();
        $o->setProperty('foobar');
        $serialized = serialize($o);
        $val        = AbstractValue::getXmlRpcValue(
            $serialized,
            AbstractValue::XMLRPC_TYPE_BASE64
        );

        $this->assertXmlRpcType('base64', $val);
        $o2 = unserialize($val->getValue());
        $this->assertSame('foobar', $o2->getProperty());
    }

    public function testChangingExceptionResetsGeneratorObject(): void
    {
        $generator = AbstractValue::getGenerator();
        AbstractValue::setEncoding('UTF-8');
        $this->assertNotSame($generator, AbstractValue::getGenerator());
        $this->assertEquals($generator, AbstractValue::getGenerator());

        $generator = AbstractValue::getGenerator();
        AbstractValue::setEncoding('ISO-8859-1');
        $this->assertNotSame($generator, AbstractValue::getGenerator());
        $this->assertNotEquals($generator, AbstractValue::getGenerator());
    }

    public function testFactoryThrowsWhenInvalidTypeSpecified(): void
    {
        $this->expectException(ValueException::class);
        $this->expectExceptionMessage('Given type is not a Laminas\XmlRpc\AbstractValue constant');
        /** @psalm-suppress InvalidArgument */
        AbstractValue::getXmlRpcValue('', 'bad type here');
    }

    public function testPassingXmlRpcObjectReturnsTheSameObject(): void
    {
        $xmlRpcValue = new Value\Text('foo');
        $this->assertSame($xmlRpcValue, AbstractValue::getXmlRpcValue($xmlRpcValue));
    }

    public function testGetXmlRpcTypeByValue(): void
    {
        $this->assertSame(
            AbstractValue::XMLRPC_TYPE_NIL,
            AbstractValue::getXmlRpcTypeByValue(new Value\Nil())
        );

        $this->assertEquals(
            AbstractValue::XMLRPC_TYPE_DATETIME,
            AbstractValue::getXmlRpcTypeByValue(new DateTime())
        );

        $this->assertEquals(
            AbstractValue::XMLRPC_TYPE_STRUCT,
            AbstractValue::getXmlRpcTypeByValue(['foo' => 'bar'])
        );

        $object      = new stdClass();
        $object->foo = 'bar';

        $this->assertEquals(
            AbstractValue::XMLRPC_TYPE_STRUCT,
            AbstractValue::getXmlRpcTypeByValue($object)
        );

        $this->assertEquals(
            AbstractValue::XMLRPC_TYPE_ARRAY,
            AbstractValue::getXmlRpcTypeByValue(new stdClass())
        );

        $this->assertEquals(
            AbstractValue::XMLRPC_TYPE_ARRAY,
            AbstractValue::getXmlRpcTypeByValue([1, 3, 3, 7])
        );

        $this->assertEquals(
            AbstractValue::XMLRPC_TYPE_INTEGER,
            AbstractValue::getXmlRpcTypeByValue(42)
        );

        $this->assertEquals(
            AbstractValue::XMLRPC_TYPE_DOUBLE,
            AbstractValue::getXmlRpcTypeByValue(13.37)
        );

        $this->assertEquals(
            AbstractValue::XMLRPC_TYPE_BOOLEAN,
            AbstractValue::getXmlRpcTypeByValue(true)
        );

        $this->assertEquals(
            AbstractValue::XMLRPC_TYPE_BOOLEAN,
            AbstractValue::getXmlRpcTypeByValue(false)
        );

        $this->assertEquals(
            AbstractValue::XMLRPC_TYPE_NIL,
            AbstractValue::getXmlRpcTypeByValue(null)
        );

        $this->assertEquals(
            AbstractValue::XMLRPC_TYPE_STRING,
            AbstractValue::getXmlRpcTypeByValue('Laminas')
        );
    }

    public function testGetXmlRpcTypeByValueThrowsExceptionOnInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AbstractValue::getXmlRpcTypeByValue(fopen(__FILE__, 'r'));
    }

    /**
     * @param mixed $object
     */
    public function assertXmlRpcType(string $type, $object): void
    {
        switch ($type) {
            case 'array':
                self::assertInstanceOf(Value\ArrayValue::class, $object);
                break;
            case 'string':
                self::assertInstanceOf(Value\Text::class, $object);
                break;
            case 'boolean':
                self::assertInstanceOf(Value\Boolean::class, $object);
                break;
            case 'integer':
                self::assertInstanceOf(Value\Integer::class, $object);
                break;
            case 'double':
                self::assertInstanceOf(Value\Double::class, $object);
                break;
            case 'nil':
                self::assertInstanceOf(Value\Nil::class, $object);
                break;
            case 'struct':
                self::assertInstanceOf(Value\Struct::class, $object);
                break;
            case 'dateTime':
                self::assertInstanceOf(Value\DateTime::class, $object);
                break;
            case 'base64':
                self::assertInstanceOf(Value\Base64::class, $object);
                break;
            default:
                // nothing to do
                break;
        }
    }

    public function wrapXml(string $xml): string
    {
        return $xml . "\n";
    }
}
