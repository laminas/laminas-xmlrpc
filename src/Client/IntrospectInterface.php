<?php

namespace Laminas\XmlRpc\Client;

interface IntrospectInterface
{
    public function getSignatureForEachMethod(): array;

    public function getSignatureForEachMethodByMulticall(array|null $methods = null): array;

    public function getSignatureForEachMethodByLooping(array|null $methods = null): array;

    public function getMethodSignature(string $method): array;

    public function listMethods(): array;
}
