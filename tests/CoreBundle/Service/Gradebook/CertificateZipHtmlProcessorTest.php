<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Service\Gradebook\CertificateZipHtmlProcessor;
use PHPUnit\Framework\TestCase;

final class CertificateZipHtmlProcessorTest extends TestCase
{
    public function testRewritesLegacyAbsoluteImageReferencesUsingTheUploadedImage(): void
    {
        $processor = new CertificateZipHtmlProcessor();
        $html = '<html><body><img src="http://www.chamilo11.dev/app/upload/users/1/1/my_files/header.png">'
            .'((user_firstname))</body></html>';
        $result = $processor->prepareHtml($html, 'certificado/index.html', [
            'certificado/header.png' => ['mime' => 'image/png', 'content' => 'png-content'],
        ]);

        self::assertStringContainsString('data:image/png;base64,'.base64_encode('png-content'), $result);
        self::assertStringNotContainsString('chamilo11.dev', $result);
        self::assertStringContainsString('((user_firstname))', $result);
    }

    public function testResolvesNestedRelativeImageWithoutConfusingSameBasenames(): void
    {
        $processor = new CertificateZipHtmlProcessor();
        $result = $processor->prepareHtml('<img src="../images/logo.png">', 'templates/index.html', [
            'images/logo.png' => ['mime' => 'image/png', 'content' => 'correct'],
            'other/logo.png' => ['mime' => 'image/png', 'content' => 'incorrect'],
        ]);

        self::assertStringContainsString(base64_encode('correct'), $result);
        self::assertStringNotContainsString(base64_encode('incorrect'), $result);
    }

    public function testRemovesExecutableMarkupAndUnresolvedExternalImages(): void
    {
        $processor = new CertificateZipHtmlProcessor();
        $result = $processor->prepareHtml(
            '<html><body><script>alert(1)</script><img src="https://example.org/tracker.png" onerror="alert(2)">'
                .'<a href="javascript:alert(3)">bad</a><span style="background:url(https://example.org/leak)">safe</span>'
                .'</body></html>',
            'index.html',
            [],
        );

        self::assertStringNotContainsString('<script', $result);
        self::assertStringNotContainsString('onerror', $result);
        self::assertStringNotContainsString('javascript:', $result);
        self::assertStringNotContainsString('https://example.org', $result);
        self::assertStringContainsString('safe', $result);
    }

    public function testDoesNotEmbedAmbiguousAbsoluteImageBasenames(): void
    {
        $processor = new CertificateZipHtmlProcessor();
        $result = $processor->prepareHtml('<img src="https://old.example.org/logo.png">', 'index.html', [
            'first/logo.png' => ['mime' => 'image/png', 'content' => 'first'],
            'second/logo.png' => ['mime' => 'image/png', 'content' => 'second'],
        ]);

        self::assertStringNotContainsString('data:image/png', $result);
        self::assertStringNotContainsString('https://old.example.org', $result);
    }
}
