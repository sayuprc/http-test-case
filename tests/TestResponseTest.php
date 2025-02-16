<?php

declare(strict_types=1);

namespace HttpTestCase\Tests;

use GuzzleHttp\Psr7\Response;
use HttpTestCase\TestResponse;
use PHPUnit\Framework\TestCase;

class TestResponseTest extends TestCase
{
    /**
     * Testing assertions
     */
    public function testAssertions(): void
    {
        $response = new TestResponse(
            new Response(
                200,
                [
                    'Host' => 'test',
                    'Content-Type' => 'application/json',
                    'line' => [
                        'a',
                        'b',
                        'c',
                        'd',
                    ],
                    'Location' => 'https://example.com',
                ],
                '{"name": "hoge", "birthday": {"year": 2023, "month": 3, "day": 30}, "include.dot": "dot.value"}'
            )
        );

        $response
            ->assertStatusCode(200)
            ->assertNotStatusCode(404)
            ->assertHeader('host', ['test'])
            ->assertNotHeader('host', ['hoge'])
            ->assertHeaderLine('line', 'a, b, c, d')
            ->assertNotHeaderLine('line', 'value')
            ->assertLocation('https://example.com')
            ->assertNotLocation('https://localhost')
            ->assertContentType('application/json')
            ->assertNotContentType('text/plain')
            ->assertBody('{"name": "hoge", "birthday": {"year": 2023, "month": 3, "day": 30}, "include.dot": "dot.value"}')
            ->assertNotBody('body')
            ->assertBodyContains('day')
            ->assertNotBodyContains('not body')
            ->assertJson('{"name": "hoge", "birthday": {"year": 2023, "month": 3, "day": 30}, "include.dot": "dot.value"}')
            ->assertJson(['name' => 'hoge', 'birthday' => ['year' => 2023, 'month' => 3, 'day' => 30], 'include.dot' => 'dot.value'])
            ->assertNotJson(['name' => 'hoge', 'birthday' => ['year' => 2023]])
            ->assertJsonKey('birthday.year', 2023)
            ->assertJsonKey('birthday', ['year' => 2023, 'month' => 3, 'day' => 30])
            ->assertNotJsonKey('birthday', ['month' => 3, 'day' => 30])
            ->assertJsonKey('include\.dot', 'dot.value');
    }
}
