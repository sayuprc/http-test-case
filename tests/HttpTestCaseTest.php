<?php

declare(strict_types=1);

namespace HttpTestCase\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use HttpTestCase\HttpTestCase;

class HttpTestCaseTest extends HttpTestCase
{
    /**
     * URI for testing
     */
    private const BASE_URI = 'https://httpbin.org/';

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

    /**
     * Testing GET requests
     */
    public function testGet(): void
    {
        $this->get(
            self::BASE_URI . 'get',
            [
                'query' => [
                    'key' => 'value',
                    'nest' => [
                        'key 1' => 'value 1',
                    ],
                ],
            ]
        )->assertStatusCode(200)
            ->assertJsonKey('args.key', 'value')
            ->assertJsonKey('args.nest[key 1]', 'value 1');

        $this->get(self::BASE_URI . 'status/404')
            ->assertStatusCode(404);

        $this->get(self::BASE_URI . 'absolute-redirect/1')
            ->assertStatusCode(302);

        $response = $this->get(
            self::BASE_URI . 'redirect-to',
            [
                'query' => [
                    'url' => self::BASE_URI . 'get',
                    'status_code' => 200,
                ],
            ]
        )->assertStatusCode(302)
            ->assertLocation(self::BASE_URI . 'get');

        $this->get($response->getHeaderLine('location'))
            ->assertStatusCode(200);
    }

    /**
     * Testing POST requests
     */
    public function testPost(): void
    {
        $this->post(
            self::BASE_URI . 'post',
            [
                'data' => [
                    'key' => 'value',
                    'nest' => [
                        'key 1' => 'value 1',
                    ],
                ],
            ]
        )->assertStatusCode(200)
            ->assertJsonKey('form.key', 'value')
            ->assertJsonKey('form.nest[key 1]', 'value 1');

        $this->post(self::BASE_URI . 'status/404')
            ->assertStatusCode(404);

        $this->post(
            self::BASE_URI . 'post',
            [
                'multipart' => [
                    [
                        'name' => 'hoge',
                        'contents' => 'hoge value',
                    ],
                    [
                        'name' => 'file A',
                        'filename' => 'fileA.txt',
                        'contents' => fopen(__DIR__ . '/../README.md', 'r'),
                    ],
                    [
                        'name' => 'file B',
                        'filename' => 'fileB.txt',
                        'contents' => 'file contents',
                    ],
                ],
            ]
        )->assertJsonKey('form.hoge', 'hoge value')
            ->assertJsonKey('files.file B', 'file contents');
    }

    /**
     * Testing PUT requests
     */
    public function testPut(): void
    {
        $this->put(
            self::BASE_URI . 'put',
            [
                'data' => [
                    'key' => 'value',
                    'nest' => [
                        'key 1' => 'value 1',
                    ],
                ],
            ]
        )->assertStatusCode(200)
            ->assertJsonKey('form.key', 'value')
            ->assertJsonKey('form.nest[key 1]', 'value 1');

        $this->put(self::BASE_URI . 'status/404')
            ->assertStatusCode(404);

        $this->put(
            self::BASE_URI . 'put',
            [
                'multipart' => [
                    [
                        'name' => 'hoge',
                        'contents' => 'hoge value',
                    ],
                    [
                        'name' => 'file A',
                        'filename' => 'fileA.txt',
                        'contents' => fopen(__DIR__ . '/../README.md', 'r'),
                    ],
                    [
                        'name' => 'file B',
                        'filename' => 'fileB.txt',
                        'contents' => 'file contents',
                    ],
                ],
            ]
        )->assertJsonKey('form.hoge', 'hoge value')
            ->assertJsonKey('files.file B', 'file contents');
    }

    /**
     * Testing DELETE requests
     */
    public function testDelete(): void
    {
        $this->delete(
            self::BASE_URI . 'delete',
            [
                'data' => [
                    'key' => 'value',
                    'nest' => [
                        'key 1' => 'value 1',
                    ],
                ],
            ]
        )->assertStatusCode(200)
            ->assertJsonKey('form.key', 'value')
            ->assertJsonKey('form.nest[key 1]', 'value 1');

        $this->delete(self::BASE_URI . 'status/404')
            ->assertStatusCode(404);

        $this->delete(
            self::BASE_URI . 'delete',
            [
                'multipart' => [
                    [
                        'name' => 'hoge',
                        'contents' => 'hoge value',
                    ],
                    [
                        'name' => 'file A',
                        'filename' => 'fileA.txt',
                        'contents' => fopen(__DIR__ . '/../README.md', 'r'),
                    ],
                    [
                        'name' => 'file B',
                        'filename' => 'fileB.txt',
                        'contents' => 'file contents',
                    ],
                ],
            ]
        )->assertJsonKey('form.hoge', 'hoge value')
            ->assertJsonKey('files.file B', 'file contents');
    }
}
