# Migration to Version 3

## XmlRpc Client

Version 3 removes the dependency on [laminas/laminas-http](https://docs.laminas.dev/laminas-http/) and replaces it with [PSR-18 (HTTP Client)](https://www.php-fig.org/psr/psr-18/), along with [PSR-7 (HTTP Messages)](https://www.php-fig.org/psr/psr-7/) and [PSR-17 (HTTP Message Factories)](https://www.php-fig.org/psr/psr-17/).
This change gives greater flexibility in how HTTP requests are made, but also changes how the XML-RPC client instance is created, and requires that you provide an HTTP client via the constructor.

Previously, the following would create a laminas-http client instance implicitly, and use it for making XML-RPC requests:

```php
$client = new Laminas\XmlRpc\Client('http://time.xmlrpc.com/RPC2');
```

If you needed to customize the HTTP client, you had the following options:

```php
$httpClient = new Laminas\Http\Client(/* ... */);

// Via constructor injection:
$xmlrpc = new Laminas\XmlRpc\Client('http://time.xmlrpc.com/RPC2', $httpClient);

// Via setter method:
$xmlrpc = new Laminas\XmlRpc\Client('http://time.xmlrpc.com/RPC2');
$xmlrpc->setHttpClient($httpClient);
```

With version 3, the HTTP client **must** be a PSR-18 HTTP client implementation, and **must** be passed to the instance via the constructor, along with a `Psr\Http\Message\RequestFactoryInterface` instance and a `Psr\Http\Message\StreamFactoryInterface` instance.
The below example uses the [php-http/curl-client package](https://docs.php-http.org/en/latest/clients/curl-client.html) to provide the HTTP client, and [Laminas Diactoros](https://docs.laminas.dev/laminas-diatoros/) to provide HTTP message factories:

```php
$xmlrpc = new Laminas\XmlRpc\Client(
    'http://time.xmlrpc.com/RPC2',
    $httpClient,
    $requestFactory,
    $streamFactory
);
```

### Methods Removed

The following methods were removed from `Laminas\XmlRpc\Client` for version 3.

- `setHttpClient()`
- `getHttpClient()`

### Methods Changed

The following methods are changed in `Laminas\XmlRpc\Client` for version 3.

- `doRequest()` the `$response` parameter has been removed
