<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Helpers\CreateUploadedFileHelper;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use finfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ZipArchive;

use const FILEINFO_MIME_TYPE;
use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;

/**
 * Imports HTML certificate templates from a ZIP without exposing extracted assets
 * as public files. Images are embedded into the stored HTML at import time.
 */
final readonly class CertificateZipImporter
{
    private const int MAX_ENTRIES = 100;
    private const int MAX_UNCOMPRESSED_BYTES = 10000000;
    private const int MAX_HTML_BYTES = 2000000;
    private const int MAX_IMAGE_BYTES = 3000000;

    private const array IMAGE_MIME = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    public function __construct(
        private CDocumentRepository $documentRepository,
        private EntityManagerInterface $entityManager,
        private CourseHelper $courseHelper,
        private CertificateZipHtmlProcessor $htmlProcessor,
    ) {}

    public function import(
        UploadedFile $uploadedFile,
        Course $course,
        ?Session $session,
        ResourceNode $parent,
        int $visibility = ResourceLink::VISIBILITY_PENDING,
    ): CDocument {
        $path = $uploadedFile->getRealPath();
        if (false === $path || '' === $path) {
            throw new BadRequestHttpException('Invalid certificate ZIP upload.');
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($path)) {
            throw new BadRequestHttpException('Invalid certificate ZIP archive.');
        }

        try {
            $entries = $this->readArchive($zip);
        } finally {
            $zip->close();
        }

        $images = [];
        $templates = [];
        foreach ($entries as $name => $entry) {
            if ('html' === $entry['extension'] || 'htm' === $entry['extension']) {
                $templates[$name] = $entry['content'];
            } else {
                $images[$name] = [
                    'mime' => self::IMAGE_MIME[$entry['extension']],
                    'content' => $entry['content'],
                ];
            }
        }

        if ([] === $templates) {
            throw new BadRequestHttpException('The certificate ZIP must contain at least one HTML file.');
        }

        $prepared = [];
        $totalBytes = 0;
        foreach ($templates as $name => $content) {
            $html = $this->htmlProcessor->prepareHtml($content, $name, $images);
            $totalBytes += \strlen($html);
            if ('' === trim($html) || $totalBytes > 15000000) {
                throw new BadRequestHttpException('Certificate ZIP produces too much HTML content.');
            }
            $prepared[$name] = $html;
        }

        // The inlined images are larger than their ZIP entry sizes; check the
        // actual storage footprint, not just the compressed archive size.
        $this->courseHelper->assertCanStoreDocumentBytes($course, $totalBytes);

        $imported = null;
        foreach ($prepared as $name => $html) {
            $fileName = str_replace('/', '_', $name);
            $fileName = preg_replace('/[^\pL\pN_.-]/u', '_', $fileName) ?? 'certificate.html';
            if ('' === $fileName) {
                $fileName = 'certificate.html';
            }
            // Do not overwrite an existing certificate on a repeated import.
            $basename = pathinfo($fileName, PATHINFO_FILENAME);
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            while ($this->documentRepository->findChildNodeByTitle($parent, $fileName)) {
                $fileName = $basename.'-'.bin2hex(random_bytes(4)).'.'.$extension;
            }

            $resourceNode = $this->documentRepository->createFileInFolder(
                $course,
                $parent,
                CreateUploadedFileHelper::fromString($fileName, 'text/html', $html),
                '',
                $visibility,
                $session,
                null,
                'certificate',
            );

            $document = $this->entityManager->getRepository(CDocument::class)->findOneBy(['resourceNode' => $resourceNode]);
            if (!$document instanceof CDocument) {
                throw new BadRequestHttpException('Unable to store certificate HTML from ZIP.');
            }

            $imported ??= $document;
        }

        return $imported;
    }

    /**
     * @return array<string, array{extension: string, content: string}>
     */
    private function readArchive(ZipArchive $zip): array
    {
        if ($zip->numFiles > self::MAX_ENTRIES) {
            throw new BadRequestHttpException('Certificate ZIP contains too many entries.');
        }

        $entries = [];
        $totalSize = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            if (!\is_array($stat)) {
                throw new BadRequestHttpException('Unable to inspect certificate ZIP entry.');
            }

            $rawName = str_replace('\\', '/', $stat['name']);
            if (str_ends_with($rawName, '/') || str_starts_with($rawName, '__MACOSX/')) {
                continue;
            }
            if ('' === $rawName || str_starts_with($rawName, '/') || preg_match('#(^|/)\.\.(/|$)|^[a-zA-Z]:|[\x00-\x1F]#', $rawName)) {
                throw new BadRequestHttpException('Certificate ZIP contains an unsafe path.');
            }
            $name = implode('/', array_values(array_filter(explode('/', $rawName), static fn (string $segment): bool => '' !== $segment && '.' !== $segment)));
            if ('' === $name || isset($entries[$name])) {
                throw new BadRequestHttpException('Certificate ZIP contains duplicate or invalid filenames.');
            }
            if ('.DS_Store' === basename($name)) {
                continue;
            }

            $opsys = 0;
            $attributes = 0;
            if (false === $zip->getExternalAttributesIndex($index, $opsys, $attributes)) {
                throw new BadRequestHttpException('Unable to inspect certificate ZIP entry attributes.');
            }
            // statIndex() does not include external attributes; inspect the entry explicitly.
            $mode = ($attributes >> 16) & 0xF000;
            if (0xA000 === $mode) {
                throw new BadRequestHttpException('Certificate ZIP cannot contain symbolic links.');
            }

            $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
            if (!\in_array($extension, ['html', 'htm'], true) && !isset(self::IMAGE_MIME[$extension])) {
                throw new BadRequestHttpException('Certificate ZIP may contain only HTML and PNG, JPEG, GIF or WebP images.');
            }

            $size = $stat['size'];
            $maxSize = \in_array($extension, ['html', 'htm'], true) ? self::MAX_HTML_BYTES : self::MAX_IMAGE_BYTES;
            $totalSize += $size;
            if ($size < 0 || $size > $maxSize || $totalSize > self::MAX_UNCOMPRESSED_BYTES) {
                throw new BadRequestHttpException('Certificate ZIP exceeds the allowed extracted size.');
            }

            $content = $zip->getFromIndex($index);
            if (false === $content || \strlen($content) !== $size) {
                throw new BadRequestHttpException('Unable to read certificate ZIP contents.');
            }

            // Check that an image is really an image, not a renamed executable.
            if (isset(self::IMAGE_MIME[$extension])) {
                $detected = (new finfo(FILEINFO_MIME_TYPE))->buffer($content);
                if ($detected !== self::IMAGE_MIME[$extension]) {
                    throw new BadRequestHttpException('Certificate ZIP contains an invalid image.');
                }
            }

            $entries[$name] = ['extension' => $extension, 'content' => $content];
        }

        return $entries;
    }
}
