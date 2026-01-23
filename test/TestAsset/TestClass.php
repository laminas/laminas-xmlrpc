<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc\TestAsset;

use function func_get_args;
use function implode;

/**
 * Docblock types are required for testing and parsing
 */
class TestClass
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(private mixed $value1 = null, private mixed $value2 = null)
    {
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * Test1
     *
     * Returns 'String: ' . $string
     * @param string $string
     * @return string
     */
    public function test1($string)
    {
        return 'String: ' . $string;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * Test2
     *
     * Returns imploded array
     * @param array $array
     * @return string
     */
    public static function test2($array)
    {
        return implode('; ', $array);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * Test3
     *
     * Should not be available...
     * @return void
     */
    protected function test3()
    {
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @param string $arg
     * @return struct|array|mixed[]
     */
    public function test4($arg): array
    {
        return ['test1' => $this->value1, 'test2' => $this->value2, 'arg' => func_get_args()];
    }

    /**
     * Test base64 encoding in request and response
     *
     * @psalm-suppress PossiblyUnusedMethod
     * @param  base64 $data
     * @return base64
     */
    public function base64($data)
    {
        return $data;
    }
}
