<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc\TestAsset;

use function func_get_args;
use function implode;

class TestClass
{
    public function __construct(private mixed $value1 = null, private mixed $value2 = null)
    {
    }

    /**
     * Test1
     *
     * Returns 'String: ' . $string
     */
    public function test1(string $string): string
    {
        return 'String: ' . (string) $string;
    }

    /**
     * Test2
     *
     * Returns imploded array
     */
    public static function test2(array $array): string
    {
        return implode('; ', (array) $array);
    }

    /**
     * Test3
     *
     * Should not be available...
     *
     * @return void
     */
    protected function test3(): void
    {
    }

    /**
     * @return struct
     */
    public function test4(string $arg): array
    {
        return ['test1' => $this->value1, 'test2' => $this->value2, 'arg' => func_get_args()];
    }

    /**
     * Test base64 encoding in request and response
     *
     * @param  base64 $data
     * @return base64
     */
    public function base64($data)
    {
        return $data;
    }
}
