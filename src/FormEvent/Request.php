<?php

declare(strict_types=1);

namespace Funnelion\FormEvent;

/**
 * Input to Funnelion\Client::formEvent(). Mirrors the POST body
 * documented for /api/v1/form-event: visitor IP forwarded from the
 * consumer's edge, visitor_id from the funnelion_session cookie,
 * and the submitted form fields.
 *
 * The visitor's IP must be the *visitor's* IP as the consumer's
 * server saw it (e.g. X-Forwarded-For first hop), not the consumer's
 * own server IP.
 */
final class Request
{
    /**
     * @param  array<string, scalar|null>  $fields  submitted form fields
     *        (keys = HTML name attributes, values = strings)
     * @param  ?string  $language  the language code of the page the
     *        form was submitted from. Same semantics + free-form
     *        vocabulary as Funnelion\Resolve\Request::$language —
     *        when supplied alongside a matching visitor_id, Funnelion
     *        also updates the session's stored language to this
     *        value (the form-submit is treated as the latest signal).
     */
    public function __construct(
        public readonly string $ip,
        public readonly array $fields,
        public readonly ?string $url = null,
        public readonly ?string $referrer = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $visitorId = null,
        public readonly ?int $formId = null,
        public readonly ?string $formActionUrl = null,
        public readonly ?string $language = null,
    ) {
        if ($ip === '') {
            throw new \InvalidArgumentException('ip must not be empty.');
        }
        if (count($fields) === 0) {
            throw new \InvalidArgumentException('fields must not be empty.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $out = [
            'ip' => $this->ip,
            'fields' => $this->fields,
        ];
        if ($this->url !== null) {
            $out['url'] = $this->url;
        }
        if ($this->referrer !== null) {
            $out['referrer'] = $this->referrer;
        }
        if ($this->userAgent !== null) {
            $out['user_agent'] = $this->userAgent;
        }
        if ($this->visitorId !== null) {
            $out['visitor_id'] = $this->visitorId;
        }
        if ($this->formId !== null) {
            $out['form_id'] = $this->formId;
        }
        if ($this->formActionUrl !== null) {
            $out['form_action_url'] = $this->formActionUrl;
        }
        if ($this->language !== null) {
            $out['language'] = $this->language;
        }

        return $out;
    }
}
