<?php

namespace LaminasTest\XmlRpc;

use Laminas\XmlRpc\Generator;

abstract class TestProvider
{
    public static function provideGenerators()
    {
        return [
            [new Generator\DomDocument()],
            [new Generator\XmlWriter()],
        ];
    }

    public static function provideGeneratorsWithAlternateEncodings()
    {
        return [
            [new Generator\DomDocument('ISO-8859-1')],
            [new Generator\XmlWriter('ISO-8859-1')],
        ];
    }
}
