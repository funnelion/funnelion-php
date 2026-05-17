# Funnelion PHP SDK

Official PHP SDK for [Funnelion](https://funnelion.ai) server-side call tracking.

Your backend calls the Funnelion resolve API, gets the visitor's assigned phone number (or email, or other channel) based on their traffic source, and renders it directly into the HTML you ship to the visitor. This bypasses ad blockers, Apple ITP cookie caps, and CSP restrictions that defeat browser-side tracking snippets.

Framework-free core. Designed to slot into plain PHP front controllers as cleanly as it slots into Laravel, Symfony, or WordPress (adapters land as they're needed).

> **Status:** `v0.1.0` — core SDK is feature-complete and tested; framework adapters (Laravel / Symfony / WordPress) are planned and not shipped yet.

## Install

```bash
composer require funnelion/sdk
```

Requires PHP 8.1+ with `ext-curl`, `ext-dom`, `ext-json` (standard on every common PHP install).

## Quick start

In your page-render handler — e.g. a Laravel controller, a Symfony action, a WordPress `template_redirect` hook, or just a plain PHP front controller:

```php
use Funnelion\Client;
use Funnelion\Config;
use Funnelion\Cookie\Session;
use Funnelion\Html\ZoneSwapper;
use Funnelion\Resolve\Request;

$client = new Client(new Config(
    siteToken: $_ENV['FUNNELION_SERVER_SIDE_TOKEN'],
));

$response = $client->resolveOrNull(new Request(
    url:       (string) ($_SERVER['REQUEST_URI'] ?? '/'),
    ip:        (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''),
    referrer:  $_SERVER['HTTP_REFERER'] ?? null,
    userAgent: $_SERVER['HTTP_USER_AGENT'] ?? null,
    visitorId: Session::readFromGlobals(),
));

if ($response !== null) {
    // Replace data-funnelion markers in your HTML with the resolved addresses.
    $html = (new ZoneSwapper())->swap($html, $response);

    // Set the cookie so subsequent page renders continue the session.
    if ($response->visitorId !== null) {
        header('Set-Cookie: '.Session::headerValue($response->visitorId), false);
    }
}

echo $html;
```

If Funnelion is unreachable, slow, rate-limited, or anything else goes wrong, `resolveOrNull()` returns `null` — your page keeps rendering its static fallback. The site never breaks because of a tracking lookup.

## Marking up your HTML

The SDK swaps content based on a single attribute, `data-funnelion="<Zone Name>"`. The element must be a **leaf** (text content only — no nested tags). Its inner text gets replaced with the resolved channel address.

```html
<!-- Phone number, plain text -->
<span data-funnelion="Header phone">+370 626 33611</span>

<!-- Phone number with a tel: link — the href gets rewritten too -->
<a href="tel:+37062633611" data-funnelion="Header phone">+370 626 33611</a>

<!-- Email zone with mailto: — same pattern -->
<a href="mailto:hi@example.com" data-funnelion="Sales email">hi@example.com</a>
```

The hardcoded value inside the element is the **fallback**: if Funnelion is unreachable, or the resolved pool is exhausted, the page renders the value you wrote. The SDK never deletes your fallback.

## Resolving without the helper

If you'd rather plug into your own templating layer (Blade, Twig, Latte, raw output), use the `Response` object directly:

```php
$response = $client->resolve(new Request(/* ... */));

$header  = $response->swapZone('Header phone');
$footer  = $response->swapZone('Footer phone');

echo '<a href="tel:'.htmlspecialchars($header->address).'">';
echo htmlspecialchars($header->address);
echo '</a>';
```

`resolve()` throws a typed `FunnelionException` subclass on any failure:

```php
use Funnelion\Exception\AuthenticationException;
use Funnelion\Exception\FunnelionException;
use Funnelion\Exception\RateLimitException;
use Funnelion\Exception\TimeoutException;

try {
    $response = $client->resolve(new Request(/* ... */));
    // render with $response
} catch (AuthenticationException) {
    error_log('Funnelion: bad token — check your config.');
    // fall back to static defaults
} catch (TimeoutException | RateLimitException) {
    // fall back silently
} catch (FunnelionException) {
    // fall back silently
}
```

Catch the base `FunnelionException` to handle all SDK-thrown errors with a single block. The typed subclasses are there if you want to distinguish.

## Cookie handling

The SDK does **not** call `setcookie()` for you — every framework has its own conventions (queued cookies in Laravel, Response headers in Symfony, hooked output in WordPress). Instead, it produces a ready-to-emit Set-Cookie header value:

```php
$value = Session::headerValue($response->visitorId);
header('Set-Cookie: '.$value, false);
```

Defaults: `HttpOnly`, `Secure`, `SameSite=Lax`, 30-day `Max-Age`, `Path=/`. All overridable per call.

> **You are responsible for visitor consent.** Setting cookies and processing visitor identifiers may require informed consent under GDPR, ePrivacy, CCPA, or other laws applicable to your visitors. Funnelion provides the mechanism; you (as data controller for your site) are responsible for obtaining and managing consent where law requires it. Funnelion does not gate, verify, or enforce consent.

## IP forwarding

Pass the **visitor's** IP, not your server's. Extract it from whatever forwarding chain reaches you:

- `CF-Connecting-IP` if you're behind Cloudflare
- `True-Client-IP` for Akamai and some other CDNs
- The leftmost address in `X-Forwarded-For` if you trust your reverse proxy
- `REMOTE_ADDR` only if no proxy is in front of you

Garbage in, garbage out — Funnelion applies IP-based filtering and geo logic to whatever you send. If you misforward and pass your own server's IP, every visitor will appear to share it.

## Configuration

```php
new Config(
    siteToken:      'srv_...',                       // required
    baseUri:        'https://dash.funnelion.ai',     // override for self-hosted
    timeoutSeconds: 0.5,                             // hard cap; tune per latency budget
    userAgent:      'my-app/1.0 funnelion-php/0.1.0' // optional override
);
```

`timeoutSeconds` is the wall clock budget for *each* call (connect + transfer). The recommended default of `0.5` enforces the "must always work" stance: if Funnelion is slow, the page renders its fallback rather than blocking the visitor.

## Pluggable HTTP transport

By default the SDK uses an internal `CurlTransport`. If you want to share your existing HTTP client across the app, implement `Funnelion\Http\Transport` and inject it:

```php
$client = new Client($config, new MyTransport());
```

A `Funnelion\Http\MockTransport` is included for testing — queue canned responses, assert the requests sent:

```php
use Funnelion\Http\HttpResponse;
use Funnelion\Http\MockTransport;

$transport = new MockTransport();
$transport->push(new HttpResponse(200, '{"swap_zones":[]}'));

$client = new Client(new Config(siteToken: 'tk'), $transport);
$client->resolve(new Request(url: 'https://example.com/', ip: '1.2.3.4'));

$sent = $transport->sentRequests[0];           // method, uri, headers, body, timeout
```

## Concepts

| Concept | What it means |
| --- | --- |
| **Site token** | Your per-Site bearer credential. Read-only-visible in the Funnelion superadmin Site form. Distinct from the public JS `site_token` embedded in the browser snippet. Rotate by overwriting the column. |
| **Swap zone** | A named slot on your page (e.g. "Header phone", "Sales email"). Funnelion picks the right address for each zone per visitor based on the routing rules you configured. |
| **`visitor_id`** | UUID returned on first resolve; round-tripped via a first-party cookie. Stitches a visitor's page renders into one session so multi-page attribution works. |
| **Pool** | A bucket of channel addresses (phone numbers, emails, …) Funnelion picks from. Static, dynamic, or source-sticky — configured in the dashboard. |

For the full design discussion of what server-side tracking bypasses and what it doesn't, see [server-side-tracking.md](https://github.com/funnelion/funnelion-php/blob/main/docs/server-side-tracking.md) in the Funnelion docs.

## Failure modes

The SDK is built around the assumption that **the customer's page must render even if Funnelion is unreachable**. The recommended deployment pattern:

- Use `resolveOrNull()` in your page-render path. Treat `null` as "render static fallbacks."
- Keep `timeoutSeconds` low (0.5s default). A slow API never blocks the visitor.
- Cache responses for short TTLs (5-30s) keyed on `(url, referrer, visitor_id)` if you have a request burst that justifies it.
- The HTML you ship always contains a valid default address inside every `data-funnelion` element. The SDK swaps over that fallback; if the swap doesn't happen, the visitor still sees a working number.

The official SDK never throws from `resolveOrNull()`, never deletes your fallback, never sets a cookie unless the resolver returned a `visitor_id`. These are load-bearing contracts.

## Development

The repo doesn't require PHP on the host — a docker-compose service wraps the test loop:

```bash
docker compose run --rm php composer install
docker compose run --rm php composer test
```

Or open an interactive shell with PHP + composer + extensions ready:

```bash
docker compose run --rm php sh
```

Tests use PHPUnit and are mock-only — they don't hit the live Funnelion API.

## Planned

- `funnelion/laravel` — service provider, middleware, Blade directive
- `funnelion/symfony` — bundle, EventListener, Twig extension
- `funnelion/wordpress` — `wp_head` hook + shortcode, WordPress.org plugin
- PSR-18 transport adapter (drop-in)
- PSR-3 logger support
- Retry policy

Each ships when there's a real consumer asking for it.

## License

[MIT](LICENSE)
