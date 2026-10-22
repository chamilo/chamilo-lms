<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Welfare;

use Chamilo\CoreBundle\Service\Welfare\WelfareCrypto;
use Chamilo\CoreBundle\Service\Welfare\WelfareEvidenceStorage;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class WelfareEvidenceStorageTest extends TestCase
{
    private string $directory;
    private WelfareEvidenceStorage $storage;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/chamilo-welfare-test-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->directory, 0700));
        $this->storage = new WelfareEvidenceStorage(
            new WelfareCrypto(base64_encode(random_bytes(32))),
            $this->directory,
        );
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/var/welfare-evidence/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->directory.'/var/welfare-evidence')) {
            rmdir($this->directory.'/var/welfare-evidence');
        }
        if (is_dir($this->directory.'/var')) {
            rmdir($this->directory.'/var');
        }
        foreach (glob($this->directory.'/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    public function testEvidenceIsEncryptedAtRestAndCannotBeReadInOtherReport(): void
    {
        $contents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+iZlsAAAAASUVORK5CYII=',
            true,
        );
        self::assertIsString($contents);
        $path = $this->directory.'/photo.png';
        file_put_contents($path, $contents);
        $file = new UploadedFile($path, 'test.png', 'image/png', null, true);

        $result = $this->storage->store($file, 'report-a');
        $onDisk = file_get_contents($this->directory.'/var/welfare-evidence/'.$result['storageKey']);
        self::assertIsString($onDisk);
        self::assertNotSame($contents, $onDisk);
        self::assertSame($contents, $this->storage->read($result['storageKey'], 'report-a'));

        $this->expectException(RuntimeException::class);
        $this->storage->read($result['storageKey'], 'report-b');
    }

    public function testDisguisedUnsupportedFileIsRejected(): void
    {
        $path = $this->directory.'/pretend.png';
        file_put_contents($path, 'This is plain text, not a PNG image.');
        $file = new UploadedFile($path, 'pretend.png', 'image/png', null, true);

        $this->expectException(BadRequestHttpException::class);
        $this->storage->store($file, 'report-a');
    }
}
