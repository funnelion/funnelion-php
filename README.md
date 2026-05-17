# Funnelion PHP SDK

Official PHP SDK for [Funnelion](https://funnelion.ai) server-side call tracking.

> **Status:** In development. Public API is being finalised; the first stable release will be tagged once it ships. ⭐ the repo to be notified when v1 lands.

## What it does

Your backend calls the Funnelion resolve API, gets the visitor's assigned phone number based on their traffic source, and renders it directly into the HTML you ship to the visitor. This bypasses ad blockers, Apple ITP cookie caps, and CSP restrictions that defeat browser-side tracking snippets.

## Planned packages

The repo is structured as a monorepo. Once released:

| Package | Composer name | Status |
| --- | --- | --- |
| Core SDK (framework-free) | `funnelion/sdk` | In design |
| Laravel adapter | `funnelion/laravel` | Planned |
| Symfony adapter | `funnelion/symfony` | Planned |
| WordPress plugin | (WordPress.org) | Planned |

```bash
composer require funnelion/sdk
```

## Documentation

Full docs land alongside the v1 release. See the [`docs/`](docs/) directory.

## Consent

You are responsible for visitor consent. Setting cookies and processing visitor identifiers may require informed consent under GDPR, ePrivacy, CCPA, or other laws applicable to your visitors. Funnelion provides the mechanism; you (as data controller for your site) are responsible for obtaining and managing consent where law requires it. Funnelion does not gate, verify, or enforce consent.

## License

[MIT](LICENSE)
