**[English](./README.md) | 日本語**

# sayuprc/http-test-case

HTTP テスト用のライブラリです。

## 要求

|名前|バージョン|
|---|---|
|PHP|^8.2|
|PHPUnit|^11.0|

## インストール方法

```
composer require --dev sayuprc/http-test-case
```

## 使い方

`HttpTestCase` クラスを継承して以下のメソッドを実装してください。

- `getClient()`
- `getRequestFactory()`
- `getUriFactory()`
- `getStreamFactory()`

```php
<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use HttpTest\HttpTestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

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

### GET リクエストのテスト

```php
<?php

use HttpTest\HttpTestCase;

class SampleTest extends HttpTestCase
{
    public function testGet()
    {
        $response = $this->get('https://example.com');

        $response->assertStatusCode(200);
    }
}
```

### POST リクエストのテスト

```php
<?php

use HttpTest\HttpTestCase;

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

## ドキュメント

HttpTestCase のメソッドとアサーションについては、[こちら](./docs/ja/)を参照してください。
