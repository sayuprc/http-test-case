# sayuprc/http-test-case

A library for HTTP testing.

## Requirements

|name|version|
|---|---|
|PHP|^8.1|
|PHPUnit|^10.0|

## Installation

```
composer require --dev sayuprc/http-test-case
```

## Usage

Extend the `HttpTestCase` class and implement the following methods:

- `getClient()`
- `getRequestFactory()`
- `getUriFactory()`
- `getStreamFactory()`

```php
<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Sayuprc\HttpTestCase\HttpTestCase;

class SampleTest extends HttpTestCase
{
    protected function getClient(): ClientInterface
    {
        return new Client();
    }

    protected function getRequestFactory(): RequestFactoryInterface
    {
        return new HttpFactory();
    }

    protected function getUriFactory(): UriFactoryInterface
    {
        return new HttpFactory();
    }

    protected function getStreamFactory(): StreamFactoryInterface
    {
        return new HttpFactory();
    }
}
```

### Testing a GET Request

```php
<?php

use Sayuprc\HttpTestCase\HttpTestCase;

class SampleTest extends HttpTestCase
{
    public function testGet()
    {
        $response = $this->get('https://example.com');

        $response->assertStatusCode(200);
    }
}
```

### Testing a POST Request

```php
<?php

use Sayuprc\HttpTestCase\HttpTestCase;

class SampleTest extends HttpTestCase
{
    public function testPost()
    {
        $response = $this->post(
            'https://example.com',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'key' => 'value',
                ],
            ]
        );

        $response->assertStatusCode(200);
    }
}
```

## Documents

Please refer to the [documentation](./docs/) for the methods and assertions of HttpTestCase.