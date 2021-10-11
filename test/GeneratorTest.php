<?php

namespace LaminasTest\XmlRpc;

use Laminas\XmlRpc\Generator\GeneratorInterface as Generator;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

use function trim;

/**
 * @group      Laminas_XmlRpc
 */
class GeneratorTest extends TestCase
{
    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testCreatingSingleElement(Generator $generator)
    {
        $generator->openElement('element');
        $generator->closeElement('element');
        $this->assertXml('<element/>', $generator);
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testCreatingSingleElementWithValue(Generator $generator)
    {
        $generator->openElement('element', 'value');
        $generator->closeElement('element');
        $this->assertXml('<element>value</element>', $generator);
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testCreatingComplexXmlDocument(Generator $generator)
    {
        $generator->openElement('root');
        $generator->openElement('children');
        $generator->openElement('child', 'child1')->closeElement('child');
        $generator->openElement('child', 'child2')->closeElement('child');
        $generator->closeElement('children');
        $generator->closeElement('root');
        $this->assertXml(
            '<root><children><child>child1</child><child>child2</child></children></root>',
            $generator
        );
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testFlushingGeneratorFlushesEverything(Generator $generator)
    {
        $generator->openElement('test')->closeElement('test');
        $this->assertXml('<test/>', $generator);
        $this->assertStringContainsString('<test/>', $generator->flush());
        $this->assertSame('', (string) $generator);
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testSpecialCharsAreEncoded(Generator $generator)
    {
        $generator->openElement('element', '<>&"\'€')->closeElement('element');
        $variant1 = '<element>&lt;&gt;&amp;"\'€</element>';
        $variant2 = '<element>&lt;&gt;&amp;&quot;\'€</element>';
        try {
            $this->assertXml($variant1, $generator);
        } catch (ExpectationFailedException $e) {
            $this->assertXml($variant2, $generator);
        }
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGeneratorsWithAlternateEncodings
     */
    public function testDifferentEncodings(Generator $generator)
    {
        $generator->openElement('element', '€')->closeElement('element');
        $this->assertXml('<element>&#8364;</element>', $generator);
    }

    /**
     * @dataProvider \LaminasTest\XmlRpc\AbstractTestProvider::provideGenerators
     */
    public function testFluentInterfacesProvided(Generator $generator)
    {
        $this->assertSame($generator, $generator->openElement('foo'));
        $this->assertSame($generator, $generator->closeElement('foo'));
    }

    public function assertXml(string $expected, Generator $actual)
    {
        $expected = trim($expected);
        $this->assertSame($expected, trim($actual));
        $xmlDecl = '<?xml version="1.0" encoding="' . $actual->getEncoding() . '"?>' . "\n";
        $this->assertSame($xmlDecl . $expected, trim($actual->saveXml()));
    }
}
