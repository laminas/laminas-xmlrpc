<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc;

use Laminas\XmlRpc\Generator\GeneratorInterface as Generator;
use LaminasTest\XmlRpc\AbstractTestProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

use function trim;

#[Group('Laminas_XmlRpc')]
class GeneratorTest extends TestCase
{
    #[DataProviderExternal(AbstractTestProvider::class, 'provideGenerators')]
    public function testCreatingSingleElement(Generator $generator)
    {
        $generator->openElement('element');
        $generator->closeElement('element');
        $this->assertXml('<element/>', $generator);
    }

    #[DataProviderExternal(AbstractTestProvider::class, 'provideGenerators')]
    public function testCreatingSingleElementWithValue(Generator $generator)
    {
        $generator->openElement('element', 'value');
        $generator->closeElement('element');
        $this->assertXml('<element>value</element>', $generator);
    }

    #[DataProviderExternal(AbstractTestProvider::class, 'provideGenerators')]
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

    #[DataProviderExternal(AbstractTestProvider::class, 'provideGenerators')]
    public function testFlushingGeneratorFlushesEverything(Generator $generator)
    {
        $generator->openElement('test')->closeElement('test');
        $this->assertXml('<test/>', $generator);
        $this->assertStringContainsString('<test/>', $generator->flush());
        $this->assertSame('', (string) $generator);
    }

    #[DataProviderExternal(AbstractTestProvider::class, 'provideGenerators')]
    public function testSpecialCharsAreEncoded(Generator $generator)
    {
        $generator->openElement('element', '<>&"\'€')->closeElement('element');
        $variant1 = '<element>&lt;&gt;&amp;"\'€</element>';
        $variant2 = '<element>&lt;&gt;&amp;&quot;\'€</element>';
        try {
            $this->assertXml($variant1, $generator);
        } catch (ExpectationFailedException) {
            $this->assertXml($variant2, $generator);
        }
    }

    #[DataProviderExternal(AbstractTestProvider::class, 'provideGeneratorsWithAlternateEncodings')]
    public function testDifferentEncodings(Generator $generator)
    {
        $generator->openElement('element', '€')->closeElement('element');
        $this->assertXml('<element>&#8364;</element>', $generator);
    }

    #[DataProviderExternal(AbstractTestProvider::class, 'provideGenerators')]
    public function testFluentInterfacesProvided(Generator $generator)
    {
        $this->assertSame($generator, $generator->openElement('foo'));
        $this->assertSame($generator, $generator->closeElement('foo'));
    }

    public function assertXml(string $expected, Generator $actual)
    {
        $expected = trim($expected);
        $this->assertSame($expected, trim((string) $actual));
        $xmlDecl = '<?xml version="1.0" encoding="' . $actual->getEncoding() . '"?>' . "\n";
        $this->assertSame($xmlDecl . $expected, trim($actual->saveXml()));
    }
}
