<?php

declare(strict_types=1);

namespace Funnelion\Tests\Resolve;

use Funnelion\Resolve\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testToArrayOmitsNullOptionalFields(): void
    {
        $request = new Request(url: 'https://example.com/', ip: '1.2.3.4');

        $this->assertSame(['url' => 'https://example.com/', 'ip' => '1.2.3.4'], $request->toArray());
    }

    public function testToArrayIncludesOptionalFieldsWhenPresent(): void
    {
        $request = new Request(
            url: 'https://example.com/',
            ip: '1.2.3.4',
            referrer: 'https://google.com/',
            userAgent: 'UA',
            visitorId: 'uuid',
            language: 'en',
        );

        $this->assertSame([
            'url' => 'https://example.com/',
            'ip' => '1.2.3.4',
            'referrer' => 'https://google.com/',
            'user_agent' => 'UA',
            'visitor_id' => 'uuid',
            'language' => 'en',
        ], $request->toArray());
    }

    public function testLanguageOmittedFromArrayWhenNull(): void
    {
        $request = new Request(url: 'https://example.com/', ip: '1.2.3.4');
        $this->assertArrayNotHasKey('language', $request->toArray());
    }

    public function testEmptyUrlIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Request(url: '', ip: '1.2.3.4');
    }

    public function testEmptyIpIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Request(url: 'https://x/', ip: '');
    }
}
