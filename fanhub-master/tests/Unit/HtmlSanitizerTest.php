<?php

namespace Tests\Unit;

use App\Rules\SafeUrl;
use App\Services\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_it_preserves_rich_text_but_removes_active_content(): void
    {
        $html = (new HtmlSanitizer)->clean('<h2>Heading</h2><p><strong>Text</strong><a href="javascript:alert(1)" onclick="bad()">Link</a></p><svg onload="bad()"></svg><iframe src="https://example.com"></iframe><img src="/storage/a.png" onerror="bad()">');
        $this->assertStringContainsString('<h2>Heading</h2>', $html);
        $this->assertStringContainsString('<strong>Text</strong>', $html);
        foreach (['javascript:', 'onclick', 'onerror', '<svg', '<iframe'] as $unsafe) {
            $this->assertStringNotContainsString($unsafe, $html);
        }
    }

    public function test_urls_disallow_script_data_protocol_relative_and_traversal_paths(): void
    {
        foreach (['javascript:alert(1)', 'data:text/html,test', '//evil.example/path', '/%2e%2e/file', "https://example.com/\nscript", 'https://user:pass@example.com/file'] as $url) {
            $this->assertFalse(SafeUrl::allowed($url));
        }
        $this->assertTrue(SafeUrl::allowed('https://example.com/image.png'));
        $this->assertTrue(SafeUrl::allowed('/storage/admin/image/a.png'));
    }
}
