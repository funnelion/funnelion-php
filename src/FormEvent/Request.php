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
     * @param  ?string  $gaClientId  the visitor's GA4 client id (`_ga`
     *        cookie — see Cookie\GoogleAnalytics). Same semantics as
     *        Funnelion\Resolve\Request::$gaClientId; supplied here too
     *        because a form submit is frequently the first request that
     *        can see the cookie at all — gtag writes it after the page
     *        that resolve rendered had already been sent.
     * @param  ?string  $formKey  your own stable name for the form
     *        ("wp:7" for Contact Form 7 form 7, "custom:quote"). The
     *        first submission with a new key registers the form on the
     *        site's Forms screen in Funnelion. Preferred over $formId,
     *        which means a Funnelion Form id.
     * @param  ?string  $formName  the form's human title, used to name
     *        it when Funnelion registers it.
     * @param  ?string  $gaSessionId  the visitor's GA4 session id
     *        (`_ga_<stream>` cookie). Same semantics as
     *        Funnelion\Resolve\Request::$gaSessionId.
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
        public readonly ?string $gaClientId = null,
        public readonly ?string $gaSessionId = null,
        public readonly ?string $formKey = null,
        public readonly ?string $formName = null,
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
        if ($this->formKey !== null && $this->formKey !== '') {
            $out['form_key'] = $this->formKey;
        }
        if ($this->formName !== null && $this->formName !== '') {
            $out['form_name'] = $this->formName;
        }
        if ($this->formActionUrl !== null) {
            $out['form_action_url'] = $this->formActionUrl;
        }
        if ($this->language !== null) {
            $out['language'] = $this->language;
        }
        if ($this->gaClientId !== null) {
            $out['ga_client_id'] = $this->gaClientId;
        }
        if ($this->gaSessionId !== null) {
            $out['ga_session_id'] = $this->gaSessionId;
        }

        return $out;
    }
}
