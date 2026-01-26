<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc\TestAsset;

use Override;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

use function array_shift;
use function count;

final class TestPsr18Client implements ClientInterface
{
    /** @var list<ResponseInterface> */
    private array $responses = [];

    public function addResponse(ResponseInterface $response): void
    {
        $this->responses[] = $response;
    }

    public function setResponse(ResponseInterface $response): void
    {
        $this->responses = [$response];
    }

    #[Override]
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->responses === []) {
            throw new RuntimeException('No response configured in TestPsr18Client');
        }

        if (count($this->responses) === 1) {
            // Re-use the single response, like the old Test adapter.
            $response = $this->responses[0];
        } else {
            // Multiple responses: behave like a queue.
            $response = array_shift($this->responses);
        }

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        return $response;
    }
}
