<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc;

use Laminas\XmlRpc\Generator;

abstract class AbstractTestProvider
{
    /** @return list<array{0: Generator\GeneratorInterface}> */
    public static function provideGenerators(): array
    {
        return [
            [new Generator\DomDocument()],
            [new Generator\XmlWriter()],
        ];
    }

    /** @return list<array{0: Generator\GeneratorInterface}> */
    public static function provideGeneratorsWithAlternateEncodings(): array
    {
        return [
            [new Generator\DomDocument('ISO-8859-1')],
            [new Generator\XmlWriter('ISO-8859-1')],
        ];
    }
}
