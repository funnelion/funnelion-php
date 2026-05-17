# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] — 2026-05-18

Initial public release.

### Added

- `Funnelion\Client` with `resolve()` (throws typed exceptions) and `resolveOrNull()` (the "must always work" pattern).
- `Funnelion\Config` value object: `siteToken`, `baseUri`, `timeoutSeconds`, `userAgent`.
- `Funnelion\Resolve\Request`, `Response`, `SwapZone` value objects mirroring the public `POST /api/v1/resolve` shape.
- `Funnelion\Html\ZoneSwapper` — replaces `data-funnelion="<Zone Name>"` markers in HTML with resolved addresses; rewrites `tel:` and `mailto:` hrefs on `<a>` elements.
- `Funnelion\Cookie\Session` — builds Set-Cookie header values and reads from `$_COOKIE`.
- `Funnelion\Http\Transport` interface, `CurlTransport` default, `MockTransport` for tests.
- Typed exception hierarchy (`FunnelionException` base + `TimeoutException`, `NetworkException`, `AuthenticationException`, `ValidationException`, `RateLimitException`, `ServerException`).
- Docker-based dev workflow (no PHP required on the host).
