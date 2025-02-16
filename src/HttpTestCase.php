<?php

declare(strict_types=1);

namespace HttpTest;

use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Random\RandomException;
use RuntimeException;

abstract class HttpTestCase extends TestCase
{
    private ClientInterface $httpClient;

    private RequestFactoryInterface $requestFactory;

    private UriFactoryInterface $uriFactory;

    private StreamFactoryInterface $streamFactory;

    private ?RequestInterface $latestRequest = null;

    private ?ResponseInterface $latestResponse = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->getClient();

        $this->requestFactory = $this->getRequestFactory();

        $this->uriFactory = $this->getUriFactory();

        $this->streamFactory = $this->getStreamFactory();
    }

    /**
     * Obtain the ClientInterface implementation to be used in the test
     */
    abstract protected function getClient(): ClientInterface;

    /**
     * Obtain the RequestFactoryInterface implementation to be used in the test
     */
    abstract protected function getRequestFactory(): RequestFactoryInterface;

    /**
     * Obtain the UriFactoryInterface implementation to be used in the test
     */
    abstract protected function getUriFactory(): UriFactoryInterface;

    /**
     * Obtain the StreamFactoryInterface implementation to be used in the test
     */
    abstract protected function getStreamFactory(): StreamFactoryInterface;

    /**
     * Send a GET request
     *
     * @param non-empty-string $uri
     * @param array{
     *     query?: array<mixed>|object,
     *     headers?: array<string, string>,
     *     data?: array<mixed>,
     *     json?: array<mixed>,
     *     multipart?: array<array{name?: string, contents?: string, filename?: string, content-type?: string}>
     * } $options
     */
    public function get(string $uri, array $options = []): TestResponse
    {
        return $this->sendRequest('GET', $uri, $options);
    }

    /**
     * Send a HEAD request
     *
     * @param non-empty-string $uri
     * @param array{
     *     query?: array<mixed>|object,
     *     headers?: array<string, string>,
     *     data?: array<mixed>,
     *     json?: array<mixed>,
     *     multipart?: array<array{name?: string, contents?: string, filename?: string, content-type?: string}>
     * } $options
     */
    public function head(string $uri, array $options = []): TestResponse
    {
        return $this->sendRequest('HEAD', $uri, $options);
    }

    /**
     * Send a POST request
     *
     * @param non-empty-string $uri
     * @param array{
     *     query?: array<mixed>|object,
     *     headers?: array<string, string>,
     *     data?: array<mixed>,
     *     json?: array<mixed>,
     *     multipart?: array<array{name?: string, contents?: string, filename?: string, content-type?: string}>
     * } $options
     */
    public function post(string $uri, array $options = []): TestResponse
    {
        return $this->sendRequest('POST', $uri, $options);
    }

    /**
     * Send a PUT request
     *
     * @param non-empty-string $uri
     * @param array{
     *     query?: array<mixed>|object,
     *     headers?: array<string, string>,
     *     data?: array<mixed>,
     *     json?: array<mixed>,
     *     multipart?: array<array{name?: string, contents?: string, filename?: string, content-type?: string}>
     * } $options
     */
    public function put(string $uri, array $options = []): TestResponse
    {
        return $this->sendRequest('PUT', $uri, $options);
    }

    /**
     * Send a DELETE request
     *
     * @param non-empty-string $uri
     * @param array{
     *     query?: array<mixed>|object,
     *     headers?: array<string, string>,
     *     data?: array<mixed>,
     *     json?: array<mixed>,
     *     multipart?: array<array{name?: string, contents?: string, filename?: string, content-type?: string}>
     * } $options
     */
    public function delete(string $uri, array $options = []): TestResponse
    {
        return $this->sendRequest('DELETE', $uri, $options);
    }

    /**
     * Send a OPTIONS request
     *
     * @param non-empty-string $uri
     * @param array{
     *     query?: array<mixed>|object,
     *     headers?: array<string, string>,
     *     data?: array<mixed>,
     *     json?: array<mixed>,
     *     multipart?: array<array{name?: string, contents?: string, filename?: string, content-type?: string}>
     * } $options
     */
    public function options(string $uri, array $options = []): TestResponse
    {
        return $this->sendRequest('OPTIONS', $uri, $options);
    }

    /**
     * Send a PATCH request
     *
     * @param non-empty-string $uri
     * @param array{
     *     query?: array<mixed>|object,
     *     headers?: array<string, string>,
     *     data?: array<mixed>,
     *     json?: array<mixed>,
     *     multipart?: array<array{name?: string, contents?: string, filename?: string, content-type?: string}>
     * } $options
     */
    public function patch(string $uri, array $options = []): TestResponse
    {
        return $this->sendRequest('PATCH', $uri, $options);
    }

    /**
     * Send a request
     *
     * @param 'GET'|'HEAD'|'POST'|'PUT'|'DELETE'|'OPTIONS'|'PATCH' $method
     * @param non-empty-string                                     $uri
     * @param array{
     *     query?: array<mixed>|object,
     *     headers?: array<string, string>,
     *     data?: array<mixed>,
     *     json?: array<mixed>,
     *     multipart?: array<array{name?: string, contents?: string, filename?: string, content-type?: string}>
     * } $options
     */
    public function sendRequest(string $method, string $uri, array $options = []): TestResponse
    {
        return new TestResponse(
            $this->latestResponse = $this->httpClient->sendRequest($this->createRequest($method, $uri, $options))
        );
    }

    /**
     * Create an instance that implements RequestInterface
     *
     * @param 'GET'|'HEAD'|'POST'|'PUT'|'DELETE'|'OPTIONS'|'PATCH' $method
     * @param non-empty-string                                     $uri
     * @param array{
     *     query?: array<mixed>|object,
     *     headers?: array<string, string>,
     *     data?: array<mixed>,
     *     json?: array<mixed>,
     *     multipart?: array<array{name?: string, contents?: string, filename?: string, content-type?: string}>
     * } $options
     */
    private function createRequest(string $method, string $uri, array $options = []): RequestInterface
    {
        if ($this->latestRequest !== null && $this->isRedirect($this->latestResponse, $uri)) {
            $request = $this->latestRequest->withUri($this->uriFactory->createUri($uri));

            if (! empty($cookies = $this->latestResponse?->getHeader('Set-Cookie'))) {
                $request = $request->withHeader('Cookie', $cookies);
            }

            $this->latestRequest = null;
            $this->latestResponse = null;
        } else {
            if (isset($options['query'])) {
                $uri .= sprintf('?%s', http_build_query($options['query'], '', '&'));
            }

            $request = $this->requestFactory->createRequest($method, $uri);

            foreach ($options['headers'] ?? [] as $name => $value) {
                $request = $request->withHeader($name, $value);
            }

            if (isset($options['data'])) {
                $request = $this->createFormRequest($request, $options['data']);
            }

            if (isset($options['json'])) {
                $request = $this->createJsonRequest($request, $options['json']);
            }

            if (isset($options['multipart'])) {
                $request = $this->createMultipartRequest($request, $options['multipart']);
            }
        }

        return $this->latestRequest = $request;
    }

    /**
     * Create application/x-www-form-urlencoded request
     *
     * @param array<mixed> $data
     */
    private function createFormRequest(RequestInterface $request, array $data): RequestInterface
    {
        return $request
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->streamFactory->createStream(http_build_query($data, '', '&')));
    }

    /**
     * Create application/json request
     *
     * @param array<mixed> $data
     *
     * @throws JsonException
     */
    private function createJsonRequest(RequestInterface $request, array $data): RequestInterface
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        // Checks JSON strings for correctness
        json_decode($json, flags: JSON_THROW_ON_ERROR);

        return $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($json));
    }

    /**
     * Create multipart/form-data request
     *
     * @param array<array{
     *     name?: string,
     *     contents?: string,
     *     filename?: string,
     *     content-type?: string
     * }> $data
     *
     * @throws InvalidArgumentException|RuntimeException|RandomException
     */
    private function createMultipartRequest(RequestInterface $request, array $data): RequestInterface
    {
        $boundary = bin2hex(random_bytes(20));

        $resource = fopen('php://temp', 'r+');

        if (! $resource) {
            throw new RuntimeException("Failed to open temporary memory stream using 'php://temp'.");
        }

        foreach ($data as $item) {
            if (! isset($item['name']) || ! isset($item['contents'])) {
                throw new InvalidArgumentException("'name' and 'contents' are required.");
            }

            fwrite(
                $resource,
                sprintf(
                    "--%s\r\nContent-Disposition: form-data; name=\"%s\"",
                    $boundary,
                    $item['name']
                )
            );

            if (isset($item['filename'])) {
                fwrite($resource, sprintf('; filename="%s"', $item['filename']));

                $contentType = $item['content-type'] ?? 'application/octet-stream';
            } else {
                $contentType = $item['content-type'] ?? 'text/plain';
            }

            fwrite($resource, sprintf("\r\nContent-Type: %s", $contentType));

            if (is_resource($item['contents'])) {
                $contents = stream_get_contents($item['contents'], null, 0);

                fclose($item['contents']);
            } else {
                $contents = $item['contents'];
            }

            fwrite($resource, sprintf("\r\n\r\n%s\r\n", $contents));
        }

        fwrite($resource, sprintf("--%s--\r\n", $boundary));

        return $request
            ->withHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary)
            ->withBody($this->streamFactory->createStreamFromResource($resource));
    }

    /**
     * The response is about redirect
     *
     * @param non-empty-string $uri
     */
    private function isRedirect(?ResponseInterface $response, string $uri): bool
    {
        return $response !== null
            && str_starts_with((string)$response->getStatusCode(), '3')
            && $response->getHeaderLine('Location') === $uri;
    }
}
