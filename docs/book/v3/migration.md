# Migration to Version 3

## Removed

The following features were removed for version 3.

### Client

XMLRPC no longer provides a client. It it is now required to provide your own  `psr/http-client` supported client.

Several client suggestions:

- [Guzzle](https://github.com/guzzle/guzzle);
- [Symfony](https://github.com/symfony/http-client);

#### `setHttpClient` method removed

It is no longer possible to set a HTTP client using `setHttpClient` method.

#### `getHttpClient` method removed

It is no longer possible to retrieve a HTTP client using `getHttpClient` method.

### Laminas HTTP removed

This version no longer relies on the `laminas/laminas-http` package. That is the reason why you now need to provide your own client.
