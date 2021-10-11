<?php

namespace LaminasTest\XmlRpc;

use Laminas\XmlRpc\Generator;

abstract class AbstractTestProvider
{
    public static function provideGenerators(): array
    {
        return [
            [new Generator\DomDocument()],
            [new Generator\XmlWriter()],
        ];
    }

    public static function provideGeneratorsWithAlternateEncodings(): array
    {
        return [
            [new Generator\DomDocument('ISO-8859-1')],
            [new Generator\XmlWriter('ISO-8859-1')],
        ];
    }
}
