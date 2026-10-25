<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\AccessUrl;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Handwritten signature printed on the official transcripts of an access URL. It is kept next to
 * the issuer key, outside public/, so that nobody can download it outside a transcript.
 */
final readonly class TranscriptSignatureImage
{
    private const int MAX_SIZE = 1048576;

    private const array EXTENSIONS = ['image/png' => 'png', 'image/jpeg' => 'jpg'];

    public function __construct(
        #[Autowire('%chamilo.certificate_issuer_dir%')]
        private string $dir,
    ) {}

    public function save(AccessUrl $url, UploadedFile $file): void
    {
        $mimeType = (string) $file->getMimeType();
        if (!isset(self::EXTENSIONS[$mimeType]) || false === @getimagesize($file->getPathname())) {
            throw new InvalidArgumentException('The signature must be a PNG or JPEG image.');
        }
        if ($file->getSize() > self::MAX_SIZE) {
            throw new InvalidArgumentException('The signature image must not exceed 1 MB.');
        }
        if (!is_dir($this->dir) && !mkdir($this->dir, 0700, true) && !is_dir($this->dir)) {
            throw new RuntimeException('Cannot create the signature directory.');
        }

        $this->delete($url);
        $path = $this->getBasePath($url).'.'.self::EXTENSIONS[$mimeType];
        if (!copy($file->getPathname(), $path)) {
            throw new RuntimeException('Cannot save the signature image.');
        }
        @chmod($path, 0600);
    }

    public function delete(AccessUrl $url): void
    {
        foreach (self::EXTENSIONS as $extension) {
            $path = $this->getBasePath($url).'.'.$extension;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * The image as a data URI, ready for the PDF renderer, which then fetches nothing.
     */
    public function getDataUri(AccessUrl $url): ?string
    {
        foreach (self::EXTENSIONS as $mimeType => $extension) {
            $content = @file_get_contents($this->getBasePath($url).'.'.$extension);
            if (\is_string($content) && '' !== $content) {
                return 'data:'.$mimeType.';base64,'.base64_encode($content);
            }
        }

        return null;
    }

    private function getBasePath(AccessUrl $url): string
    {
        return $this->dir.'/transcript-signature-'.$url->getId();
    }
}
