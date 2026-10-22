<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Welfare;

use finfo;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

use const FILEINFO_MIME_TYPE;

/**
 * Stores only ciphertext, outside public/. Files have unguessable names and no direct URLs.
 */
final readonly class WelfareEvidenceStorage
{
    public const int MAX_SIZE = 8388608;
    public const int MAX_FILES = 3;

    private const array ALLOWED_MIME = [
        'image/jpeg', 'image/png', 'image/webp',
        'audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/x-wav',
        'video/mp4', 'video/webm',
    ];

    public function __construct(
        private WelfareCrypto $crypto,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {}

    /**
     * @return array{storageKey:string, filenameCipher:string, mimeType:string, fileSize:int}
     */
    public function store(UploadedFile $file, string $reportReference): array
    {
        if (!$file->isValid() || $file->getSize() <= 0 || $file->getSize() > self::MAX_SIZE) {
            throw new BadRequestHttpException('Evidence file exceeds the allowed size or is invalid.');
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file->getPathname());
        if (!\is_string($mimeType) || !\in_array($mimeType, self::ALLOWED_MIME, true)) {
            throw new BadRequestHttpException('Unsupported evidence file type.');
        }

        $storageKey = bin2hex(random_bytes(16));
        $filename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $filename = trim(preg_replace('/[\x00-\x1f\x7f]/u', '', $filename) ?? 'evidence');
        $filename = '' === $filename ? 'evidence' : mb_substr($filename, 0, 150);
        $contents = file_get_contents($file->getPathname());
        if (false === $contents || \strlen($contents) > self::MAX_SIZE) {
            throw new BadRequestHttpException('Evidence file could not be read.');
        }
        $ciphertext = $this->crypto->encrypt($contents, 'evidence:'.$reportReference.':'.$storageKey);
        $directory = $this->directory();
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Private evidence storage is not writable.');
        }

        $path = $directory.'/'.$storageKey;
        $handle = fopen($path, 'x');
        if (false === $handle) {
            throw new RuntimeException('Cannot create private evidence file.');
        }

        try {
            chmod($path, 0600);
            $written = fwrite($handle, $ciphertext);
            if (false === $written || $written !== \strlen($ciphertext)) {
                throw new RuntimeException('Private evidence file was not written completely.');
            }
        } catch (Throwable $exception) {
            fclose($handle);
            unlink($path);

            throw $exception;
        }
        fclose($handle);

        return [
            'storageKey' => $storageKey,
            'filenameCipher' => $this->crypto->encrypt($filename, 'filename:'.$reportReference.':'.$storageKey),
            'mimeType' => $mimeType,
            'fileSize' => \strlen($contents),
        ];
    }

    public function read(string $storageKey, string $reportReference): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $storageKey)) {
            throw new RuntimeException('Invalid private evidence identifier.');
        }
        $contents = file_get_contents($this->directory().'/'.$storageKey);
        if (false === $contents) {
            throw new RuntimeException('Private evidence is unavailable.');
        }

        return $this->crypto->decrypt($contents, 'evidence:'.$reportReference.':'.$storageKey);
    }

    public function remove(string $storageKey): void
    {
        if (preg_match('/^[a-f0-9]{32}$/D', $storageKey)) {
            $path = $this->directory().'/'.$storageKey;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function directory(): string
    {
        return $this->projectDir.'/var/welfare-evidence';
    }
}
