<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

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

final class SupportBotJsonUploadValidationTest extends TestCase
{
    public function testJsonIsAcceptedOnlyByTheSupportBotImporterWithWhitelistPolicy(): void
    {
        $upload = $this->sendUpload('/api/support-bot/admin/trees/import', 'POST', 'file', 'support-bot-example.json');

        self::assertInstanceOf(UploadedFile::class, $upload);
        self::assertSame('support-bot-example.json', $upload->getClientOriginalName());
    }

    public function testJsonIsAcceptedWithoutExtensionRenamingWhenWhitelistReplacesUnknownExtensions(): void
    {
        $upload = $this->sendUpload('/api/support-bot/admin/trees/import', 'POST', 'file', 'my-sample.json', skip: 'false');

        self::assertInstanceOf(UploadedFile::class, $upload);
        self::assertSame('my-sample.json', $upload->getClientOriginalName());
    }

    public function testCsvAlreadyWhitelistedByAnInstallationRemainsAccepted(): void
    {
        $upload = $this->sendUpload('/api/support-bot/admin/trees/import', 'POST', 'file', 'support-bot-example.csv', whitelist: 'csv');

        self::assertInstanceOf(UploadedFile::class, $upload);
        self::assertSame('support-bot-example.csv', $upload->getClientOriginalName());
    }

    /**
     * @dataProvider disallowedImportCases
     */
    public function testJsonExceptionCannotBeUsedOutsideItsExactScope(
        string $path,
        string $method,
        string $field,
        string $filename
    ): void {
        $this->expectException(BadRequestHttpException::class);

        $this->sendUpload($path, $method, $field, $filename);
    }

    /**
     * @return iterable<string, array{string,string,string,string}>
     */
    public static function disallowedImportCases(): iterable
    {
        yield 'another endpoint' => ['/api/documents', 'POST', 'file', 'support-bot-example.json'];

        yield 'another method' => ['/api/support-bot/admin/trees/import', 'PUT', 'file', 'support-bot-example.json'];

        yield 'another field' => ['/api/support-bot/admin/trees/import', 'POST', 'attachment', 'support-bot-example.json'];

        yield 'PHP double extension' => ['/api/support-bot/admin/trees/import', 'POST', 'file', 'malicious.php.json'];

        yield 'HTML double extension' => ['/api/support-bot/admin/trees/import', 'POST', 'file', 'malicious.html.json'];
    }

    public function testExplicitJsonBlacklistIsNeverBypassed(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $this->sendUpload(
            '/api/support-bot/admin/trees/import',
            'POST',
            'file',
            'support-bot-example.json',
            listType: 'blacklist',
            blacklist: 'json'
        );
    }

    private function sendUpload(
        string $path,
        string $method,
        string $field,
        string $filename,
        string $listType = 'whitelist',
        string $whitelist = '',
        string $blacklist = '',
        string $skip = 'true'
    ): ?UploadedFile {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'support_bot_json_');
        self::assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, '{"tree":{"title":"Sample"},"nodes":[],"entryNode":"n1"}');

        try {
            $request = Request::create($path, $method);
            $request->files->set($field, new UploadedFile($temporaryFile, $filename, 'application/json', UPLOAD_ERR_OK, true));

            $settingsManager = $this->createMock(SettingsManager::class);
            $settingsManager->method('getSetting')->willReturnCallback(
                static fn (string $name, mixed ...$unused): mixed => match ($name) {
                    'document.upload_extensions_list_type' => $listType,
                    'document.upload_extensions_skip' => $skip,
                    'document.upload_extensions_whitelist' => $whitelist,
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
