<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\EventSubscriber\UploadedFileValidationSubscriber;
use Chamilo\CoreBundle\Security\Upload\UploadFilenamePolicy;
use Chamilo\CoreBundle\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use const UPLOAD_ERR_OK;

final class UploadedFileValidationSubscriberTest extends TestCase
{
    public function testPaletteUploadIsAcceptedWithWhitelistWithoutRelyingOnMatchedRoute(): void
    {
        // Upload filtering must not depend on a RouterListener attribute or path.
        $upload = $this->sendUpload('/themes/chamilo/palette', 'palette', 'chamilo-chart-palette.color');
        self::assertInstanceOf(UploadedFile::class, $upload);
        self::assertSame('chamilo-chart-palette.color', $upload->getClientOriginalName());
    }

    public function testAValidPaletteFilenameDoesNotRequireAControllerRouteException(): void
    {
        $upload = $this->sendUpload('/some/other/endpoint', 'file', 'example.color');
        self::assertInstanceOf(UploadedFile::class, $upload);
        // This is only extension admission: each controller still controls uploads.
    }

    /**
     * @dataProvider rejectedFilenameCases
     */
    public function testUnsafeFilenameIsRejected(string $filename): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->sendUpload('/themes/chamilo/palette', 'palette', $filename);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function rejectedFilenameCases(): iterable
    {
        yield 'double extension' => ['malicious.html.color'];

        yield 'php double extension' => ['malicious.php.color'];

        yield 'filename with spaces' => ['unsafe name.color'];

        yield 'executable upload' => ['malicious.php'];
    }

    public function testExplicitBlacklistStillRejectsColorAtPaletteEndpoint(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->sendUpload('/themes/chamilo/palette', 'palette', 'chart.color', 'blacklist', 'color');
    }

    public function testExistingSafeUploadsStillWork(): void
    {
        $upload = $this->sendUpload('/api/documents', 'file', 'document.png');
        self::assertInstanceOf(UploadedFile::class, $upload);
    }

    private function sendUpload(
        string $path,
        string $field,
        string $filename,
        string $listType = 'whitelist',
        string $blacklist = '',
    ): ?UploadedFile {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'palette_test_');
        self::assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, "19,59,118,100\n");

        try {
            $request = Request::create($path, 'POST');
            $request->files->set($field, new UploadedFile($temporaryFile, $filename, 'text/plain', UPLOAD_ERR_OK, true));

            $settingsManager = $this->createMock(SettingsManager::class);
            $settingsManager->method('getSetting')->willReturnCallback(
                static fn (string $name, mixed ...$unused): mixed => match ($name) {
                    'document.upload_extensions_list_type' => $listType,
                    'document.upload_extensions_skip' => 'true',
                    'document.upload_extensions_whitelist' => '',
                    'document.upload_extensions_blacklist' => $blacklist,
                    'document.upload_extensions_replace_by' => 'txt',
                    'editor.enabled_support_svg' => 'false',
                    default => '',
                }
            );

            $subscriber = new UploadedFileValidationSubscriber(new UploadFilenamePolicy($settingsManager));
            $kernel = $this->createMock(HttpKernelInterface::class);
            $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

            return $request->files->get($field);
        } finally {
            @unlink($temporaryFile);
        }
    }
}
