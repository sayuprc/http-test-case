<?php

declare(strict_types=1);

namespace HttpTestCase\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use HttpTestCase\HttpTestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class HttpTestCaseTest extends HttpTestCase
{
    /**
     * URI for testing
     */
    private const BASE_URI = 'http://localhost:8080/';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        exec('php -S localhost:8080 -t bin bin/router.php > /dev/null 2>&1 &');

        usleep(100000);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        exec('pkill -f "php -S localhost:8080"');
    }

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
            ->assertJsonKey('args.nest.key 1', 'value 1');

        $this->get(self::BASE_URI . 'status/404')
            ->assertStatusCode(404);

        $this->get(self::BASE_URI . 'absolute-redirect')
            ->assertStatusCode(302);

        $response = $this->get(
            self::BASE_URI . 'redirect-to',
            [
                'query' => [
                    'url' => self::BASE_URI . 'get',
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
            ->assertJsonKey('form.nest.key 1', 'value 1');

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
            ->assertJsonKey('files.fileB\.txt', 'file contents');
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
            ->assertJsonKey('form.nest.key 1', 'value 1');

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
            ->assertJsonKey('files.fileB\.txt', 'file contents');
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
            ->assertJsonKey('form.nest.key 1', 'value 1');

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
            ->assertJsonKey('files.fileB\.txt', 'file contents');
    }
}
