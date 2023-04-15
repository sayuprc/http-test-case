<?php

declare(strict_types=1);

namespace Sayuprc\HttpTestCase;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

abstract class HttpTestCase extends TestCase
{
    /**
     * @var ClientInterface $httpClient
     */
    private ClientInterface $httpClient;

    /**
     * @var RequestFactoryInterface $requestFactory
     */
    private RequestFactoryInterface $requestFactory;

    /**
     * @var UriFactoryInterface $uriFactory
     */
    private UriFactoryInterface $uriFactory;

    /**
     * @var StreamFactoryInterface $streamFactory
     */
    private StreamFactoryInterface $streamFactory;

    /**
     * @var RequestInterface|null $latestRequest
     */
    private ?RequestInterface $latestRequest = null;

    /**
     * @var ResponseInterface|null $latestResponse
     */
    private ?ResponseInterface $latestResponse = null;

    /**
     * @param non-empty-string $name
     *
     * @return void
     */
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->httpClient = $this->getClient();

        $this->requestFactory = $this->getRequestFactory();

        $this->uriFactory = $this->getUriFactory();

        $this->streamFactory = $this->getStreamFactory();
    }

    /**
     * Obtain the ClientInterface implementation to be used in the test
     *
     * @return ClientInterface
     */
    abstract protected function getClient(): ClientInterface;

    /**
     * Obtain the RequestFactoryInterface implementation to be used in the test
     *
     * @return RequestFactoryInterface
     */
    abstract protected function getRequestFactory(): RequestFactoryInterface;

    /**
     * Obtain the UriFactoryInterface implementation to be used in the test
     *
     * @return UriFactoryInterface
     */
    abstract protected function getUriFactory(): UriFactoryInterface;

    /**
     * Obtain the StreamFactoryInterface implementation to be used in the test
     *
     * @return StreamFactoryInterface
     */
    abstract protected function getStreamFactory(): StreamFactoryInterface;

    /**
     * Send a GET request
     *
     * @param string $uri
     * @param array  $options
     *
     * @return TestResponse
     */
    public function get(string $uri, array $options = []): TestResponse
    {
        return new TestResponse(
            $this->latestResponse = $this->httpClient->sendRequest($this->createRequest('GET', $uri, $options))
        );
    }

    /**
     * Send a POST request
     *
     * @param string $uri
     * @param array  $options
     *
     * @return TestResponse
     */
    public function post(string $uri, array $options = []): TestResponse
    {
        return new TestResponse(
            $this->latestResponse = $this->httpClient->sendRequest($this->createRequest('POST', $uri, $options))
        );
    }

    /**
     * Create an instance that implements RequestInterface
     *
     * @param string $method
     * @param string $uri
     * @param array  $options
     *
     * @return RequestInterface
     */
    private function createRequest(string $method, string $uri, array $options = []): RequestInterface
    {
        if ($this->latestRequest !== null && $this->isRedirect($this->latestResponse, $uri)) {
            $request = $this->latestRequest->withUri($this->uriFactory->createUri($uri));

            if (! empty($cookies = $this->latestResponse->getHeader('Set-Cookie'))) {
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
     * @param RequestInterface $request
     * @param array            $data
     *
     * @return RequestInterface
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
     * @param RequestInterface $request
     * @param array            $data
     *
     * @return RequestInterface
     */
    private function createJsonRequest(RequestInterface $request, array $data): RequestInterface
    {
        return $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream(json_encode($data)));
    }

    /**
     * Create multipart/form-data request
     *
     * @param RequestInterface $request
     * @param array            $data
     *
     * @throws InvalidArgumentException
     *
     * @return RequestInterface
     */
    private function createMultipartRequest(RequestInterface $request, array $data): RequestInterface
    {
        $boundary = bin2hex(random_bytes(20));

        $resource = fopen('php://temp', 'r+');

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
     * @param ResponseInterface|null $response
     * @param string                 $uri
     *
     * @return bool
     */
    private function isRedirect(?ResponseInterface $response, string $uri): bool
    {
        return $response !== null
            && str_starts_with((string)$response->getStatusCode(), '3')
            && $response->getHeaderLine('Location') === $uri;
    }
}
