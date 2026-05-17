<?php

declare(strict_types=1);

namespace Funnelion;

use Funnelion\Exception\AuthenticationException;
use Funnelion\Exception\FunnelionException;
use Funnelion\Exception\NetworkException;
use Funnelion\Exception\RateLimitException;
use Funnelion\Exception\ServerException;
use Funnelion\Exception\ValidationException;
use Funnelion\Http\CurlTransport;
use Funnelion\Http\Transport;
use Funnelion\Resolve\Request;
use Funnelion\Resolve\Response;

/**
 * Main SDK entry point. Constructed once per process with the Site's
 * server_side_token; reused for every request.
 *
 *     $client = new Funnelion\Client(new Funnelion\Config(
 *         siteToken: $_ENV['FUNNELION_SITE_TOKEN'],
 *     ));
 *
 *     $response = $client->resolveOrNull(new Funnelion\Resolve\Request(
 *         url: 'https://example.com/?utm_source=facebook',
 *         ip:  $_SERVER['REMOTE_ADDR'],
 *         visitorId: $_COOKIE['funnelion_session'] ?? null,
 *     ));
 *
 *     if ($response !== null) {
 *         $html = (new Funnelion\Html\ZoneSwapper())->swap($html, $response);
 *     }
 *
 * The Client is stateless; the Transport may not be (e.g. a connection
 * pool). Either is safe to share across requests in a long-running
 * process.
 */
final class Client
{
    public const VERSION = '0.1.0';

    public function __construct(
        private readonly Config $config,
        private readonly Transport $transport = new CurlTransport(),
    ) {}

    /**
     * Resolve the visitor's swap zones. Throws a typed FunnelionException
     * on any failure (timeout, network, auth, validation, rate limit,
     * 5xx). Use resolveOrNull() if you'd rather fall back silently.
     */
    public function resolve(Request $request): Response
    {
        $http = $this->transport->send(
            method: 'POST',
            uri: rtrim($this->config->baseUri, '/').'/api/v1/resolve',
            headers: [
                'Authorization' => 'Bearer '.$this->config->siteToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => $this->config->userAgent,
            ],
            body: (string) json_encode($request->toArray(), JSON_THROW_ON_ERROR),
            timeoutSeconds: $this->config->timeoutSeconds,
        );

        return $this->parseResponse($http->statusCode, $http->body);
    }

    /**
     * Resolve, returning null on any failure instead of throwing. The
     * "must always work" pattern documented in the README — your page
     * keeps rendering its static defaults if Funnelion is unreachable.
     *
     * Logging is deliberately out of scope; subclass the Client or wrap
     * resolve() if you want to record failures.
     */
    public function resolveOrNull(Request $request): ?Response
    {
        try {
            return $this->resolve($request);
        } catch (FunnelionException) {
            return null;
        }
    }

    private function parseResponse(int $statusCode, string $body): Response
    {
        if ($statusCode >= 200 && $statusCode < 300) {
            $decoded = $this->decodeJson($body);

            return Response::fromArray($decoded);
        }

        $decoded = $body !== '' ? $this->tryDecodeJson($body) : null;
        $errorMessage = is_array($decoded) && isset($decoded['error']) && is_string($decoded['error'])
            ? $decoded['error']
            : 'unknown_error';

        match (true) {
            $statusCode === 401 => throw new AuthenticationException(
                "Funnelion rejected the request: {$errorMessage}.",
                statusCode: 401,
            ),
            $statusCode === 422 => throw new ValidationException(
                "Funnelion rejected the payload: {$errorMessage}.",
                errors: is_array($decoded) && isset($decoded['details']) && is_array($decoded['details'])
                    ? $decoded['details']
                    : [],
            ),
            $statusCode === 429 => throw new RateLimitException(
                'Funnelion rate limit exceeded.',
                statusCode: 429,
            ),
            $statusCode >= 500 => throw new ServerException(
                "Funnelion server error (HTTP {$statusCode}).",
                statusCode: $statusCode,
            ),
            default => throw new FunnelionException(
                "Funnelion returned an unexpected HTTP status {$statusCode}: {$errorMessage}.",
                statusCode: $statusCode,
            ),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $body): array
    {
        try {
            $decoded = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new NetworkException('Funnelion returned malformed JSON.', previous: $e);
        }

        if (! is_array($decoded)) {
            throw new NetworkException('Funnelion returned a non-object JSON body.');
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tryDecodeJson(string $body): ?array
    {
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }
}
