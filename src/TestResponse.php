<?php

declare(strict_types=1);

namespace HttpTestCase;

use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;

class TestResponse
{
    private ResponseInterface $response;

    /**
     * @return void
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * Assertion of status code
     */
    public function assertStatusCode(int $expected): static
    {
        Assert::assertSame($expected, $this->response->getStatusCode());

        return $this;
    }

    /**
     * Assertion of not same status code
     */
    public function assertNotStatusCode(int $expected): static
    {
        Assert::assertNotSame($expected, $this->response->getStatusCode());

        return $this;
    }

    /**
     * Assertion of contents of the specific response header
     *
     * @param string[] $expected
     */
    public function assertHeader(string $name, array $expected): static
    {
        Assert::assertSame($expected, $this->getHeader($name));

        return $this;
    }

    /**
     * Assertion of not same contents of the specific response header
     *
     * @param string[] $expected
     */
    public function assertNotHeader(string $name, array $expected): static
    {
        Assert::assertNotSame($expected, $this->getHeader($name));

        return $this;
    }

    /**
     * Assertion of contents of the comma-separated string of the values for a single header
     */
    public function assertHeaderLine(string $name, string $expected): static
    {
        Assert::assertSame($expected, $this->getHeaderLine($name));

        return $this;
    }

    /**
     * Assertion of not same contents of the comma-separated string of the values for a single header
     */
    public function assertNotHeaderLine(string $name, string $expected): static
    {
        Assert::assertNotSame($expected, $this->getHeaderLine($name));

        return $this;
    }

    /**
     * Assertion of contents of the location
     */
    public function assertLocation(string $expected): static
    {
        Assert::assertSame($expected, $this->getHeaderLine('Location'));

        return $this;
    }

    /**
     * Assertion of not same contents of the location
     */
    public function assertNotLocation(string $expected): static
    {
        Assert::assertNotSame($expected, $this->getHeaderLine('Location'));

        return $this;
    }

    /**
     * Assertion of contents of the content type
     */
    public function assertContentType(string $expected): static
    {
        Assert::assertSame($expected, $this->getHeaderLine('Content-Type'));

        return $this;
    }

    /**
     * Assertion of not same contents of the content type
     */
    public function assertNotContentType(string $expected): static
    {
        Assert::assertNotSame($expected, $this->getHeaderLine('Content-Type'));

        return $this;
    }

    /**
     * Assertion of contents of the response body
     */
    public function assertBody(string $expected): static
    {
        Assert::assertSame($expected, $this->getBody());

        return $this;
    }

    /**
     * Assertion of not same contents of the response body
     */
    public function assertNotBody(string $expected): static
    {
        Assert::assertNotSame($expected, $this->getBody());

        return $this;
    }

    /**
     * Assertion of body contains a needle
     */
    public function assertBodyContains(string $needle): static
    {
        Assert::assertStringContainsString($needle, $this->getBody());

        return $this;
    }

    /**
     * Assertion of body not contains a needle
     */
    public function assertNotBodyContains(string $needle): static
    {
        Assert::assertStringNotContainsString($needle, $this->getBody());

        return $this;
    }

    /**
     * Assertion of json string
     */
    public function assertJson(array|string $expected): static
    {
        if (is_string($expected)) {
            $expected = json_decode($expected, true);
        }

        Assert::assertSame($expected, json_decode($this->getBody(), true));

        return $this;
    }

    /**
     * Assertion of not same json string
     */
    public function assertNotJson(array|string $expected): static
    {
        if (is_string($expected)) {
            $expected = json_decode($expected, true);
        }

        Assert::assertNotSame($expected, json_decode($this->getBody(), true));

        return $this;
    }

    /**
     * Assertion of contents of a json string
     */
    public function assertJsonKey(string $key, mixed $expected): static
    {
        $jsonArray = json_decode($this->getBody(), true);

        if (! $jsonArray) {
            Assert::fail('Failed to parse json string');
        } else {
            $keys = explode('.', $key);

            $actual = $jsonArray[$keys[0]];

            unset($keys[0]);

            foreach ($keys as $key) {
                $actual = $actual[$key];
            }

            Assert::assertSame($expected, $actual);
        }

        return $this;
    }

    /**
     * Assertion of not same contents of a json string
     */
    public function assertNotJsonKey(string $key, mixed $expected): static
    {
        $jsonArray = json_decode($this->getBody(), true);

        if (! $jsonArray) {
            Assert::fail('Failed to parse json string');
        } else {
            $keys = explode('.', $key);

            $actual = $jsonArray[$keys[0]];

            unset($keys[0]);

            foreach ($keys as $key) {
                $actual = $actual[$key];
            }

            Assert::assertNotSame($expected, $actual);
        }

        return $this;
    }

    /**
     * Retrieves a comma-separated string of the values for a single header
     */
    public function getHeaderLine(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * @return string[]
     */
    public function getHeader(string $name): array
    {
        return $this->response->getHeader($name);
    }

    /**
     * Retrieves a response body
     */
    public function getBody(): string
    {
        return $this->response->getBody()->__toString();
    }

    /**
     * Dump the contents of the response header
     */
    public function dumpHeaders(): void
    {
        var_dump($this->response->getHeaders());
    }

    /**
     * Dump the contents of the specific response header
     */
    public function dumpHeader(string $name): void
    {
        var_dump($this->getHeader($name));
    }

    /**
     * Dump the contents of the response body
     */
    public function dumpBody(): void
    {
        var_dump($this->response->getBody()->__toString());
    }
}
