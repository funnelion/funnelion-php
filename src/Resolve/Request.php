<?php

declare(strict_types=1);

namespace Funnelion\Resolve;

/**
 * Input to Funnelion\Client::resolve(). Mirrors the POST body documented
 * in docs/api-v1-resolve.md.
 *
 * `$ip` must be the *visitor's* IP as the consumer's server determined
 * it (e.g. from X-Forwarded-For / CF-Connecting-IP), not the consumer's
 * own server IP.
 */
final class Request
{
    public function __construct(
        public readonly string $url,
        public readonly string $ip,
        public readonly ?string $referrer = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $visitorId = null,
    ) {
        if ($url === '') {
            throw new \InvalidArgumentException('url must not be empty.');
        }
        if ($ip === '') {
            throw new \InvalidArgumentException('ip must not be empty.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $out = [
            'url' => $this->url,
            'ip' => $this->ip,
        ];
        if ($this->referrer !== null) {
            $out['referrer'] = $this->referrer;
        }
        if ($this->userAgent !== null) {
            $out['user_agent'] = $this->userAgent;
        }
        if ($this->visitorId !== null) {
            $out['visitor_id'] = $this->visitorId;
        }

        return $out;
    }
}
