<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Ai;

use Chamilo\CoreBundle\Controller\Api\CreateDocumentFileAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManager;
use finfo;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use const DNS_A;
use const DNS_AAAA;
use const FILEINFO_MIME_TYPE;
use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_VALIDATE_IP;
use const JSON_THROW_ON_ERROR;

final readonly class GeneratedMediaStorageService
{
    private const MAX_IMAGE_BYTES = 10_485_760;

    /**
     * @var array<string, string>
     */
    private const IMAGE_EXTENSIONS = [
        'image/gif' => 'gif',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
        private CDocumentRepository $documentRepository,
        private CreateDocumentFileAction $createDocumentFileAction,
        private EntityManager $entityManager,
        private KernelInterface $kernel,
        private TranslatorInterface $translator,
        private CourseRepository $courseRepository,
        private CourseHelper $courseHelper,
        private AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    /**
     * @param array<string, mixed>|string $generatedResult
     */
    public function storeGeneratedImage(
        Course $course,
        int $courseId,
        string $title,
        string $topic,
        array|string $generatedResult,
        ?string $language,
        bool $publish,
    ): CDocument {
        [$binary, $declaredMimeType] = $this->resolveImageBinary($generatedResult);

        $detectedMimeType = $this->detectImageMimeType($binary);
        if (!isset(self::IMAGE_EXTENSIONS[$detectedMimeType])) {
            throw new RuntimeException('The generated file is not a supported image.');
        }

        $extension = self::IMAGE_EXTENSIONS[$detectedMimeType];
        $fileName = $this->buildSafeFileName($title, $extension);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'chamilo_mcp_image_');

        if (false === $temporaryPath) {
            throw new RuntimeException('A temporary file for the generated image could not be created.');
        }

        try {
            $writtenBytes = file_put_contents($temporaryPath, $binary);
            if (false === $writtenBytes || $writtenBytes <= 0) {
                throw new RuntimeException('The generated image could not be written to temporary storage.');
            }

            if (
                '' !== $declaredMimeType
                && !str_starts_with($declaredMimeType, 'image/')
                && 'application/octet-stream' !== $declaredMimeType
            ) {
                throw new RuntimeException('The AI provider returned an invalid image content type.');
            }

            $uploadedFile = new UploadedFile(
                $temporaryPath,
                $fileName,
                $detectedMimeType,
                null,
                true,
            );

            $visibility = $publish
                ? ResourceLink::VISIBILITY_PUBLISHED
                : ResourceLink::VISIBILITY_DRAFT;

            /** @var CDocument $document */
            return $this->entityManager->wrapInTransaction(
                function () use (
                    $course,
                    $courseId,
                    $topic,
                    $language,
                    $visibility,
                    $uploadedFile,
                ): CDocument {
                    $courseResourceNode = $course->getResourceNode();
                    if (null === $courseResourceNode || null === $courseResourceNode->getId()) {
                        throw new RuntimeException('The course resource node could not be resolved.');
                    }

                    $request = Request::create(
                        '/api/documents?cid='.$courseId,
                        'POST',
                        [
                            'filetype' => 'file',
                            'comment' => $topic,
                            'parentResourceNodeId' => (int) $courseResourceNode->getId(),
                            'resourceLinkList' => json_encode(
                                [['visibility' => $visibility]],
                                JSON_THROW_ON_ERROR,
                            ),
                            'fileExistsOption' => 'rename',
                            'language' => $language ?? '',
                            'ai_assisted' => '1',
                        ],
                        [],
                        ['uploadFile' => $uploadedFile],
                        [],
                        '',
                    );

                    return ($this->createDocumentFileAction)(
                        $request,
                        $this->documentRepository,
                        $this->entityManager,
                        $this->kernel,
                        $this->translator,
                        $this->courseRepository,
                        $this->courseHelper,
                        $this->aiDisclosureHelper,
                    );
                }
            );
        } finally {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    /**
     * @param array<string, mixed>|string $generatedResult
     *
     * @return array{0: string, 1: string}
     */
    private function resolveImageBinary(
        array|string $generatedResult,
    ): array {
        if (\is_string($generatedResult)) {
            $value = trim($generatedResult);
            if ('' === $value) {
                throw new RuntimeException('The AI provider returned an empty image.');
            }

            if (preg_match('#^https://#i', $value)) {
                return $this->fetchRemoteImage($value);
            }

            return [
                $this->decodeBase64Image($value),
                'image/png',
            ];
        }

        if (
            isset($generatedResult['error'])
            && \is_string($generatedResult['error'])
            && '' !== trim($generatedResult['error'])
        ) {
            throw new RuntimeException(trim($generatedResult['error']));
        }

        $isBase64 = (bool) ($generatedResult['is_base64'] ?? false);
        $content = isset($generatedResult['content'])
            && \is_string($generatedResult['content'])
                ? trim($generatedResult['content'])
                : '';
        $url = isset($generatedResult['url'])
            && \is_string($generatedResult['url'])
                ? trim($generatedResult['url'])
                : '';
        $contentType = isset($generatedResult['content_type'])
            && \is_string($generatedResult['content_type'])
                ? $this->normalizeMimeType($generatedResult['content_type'])
                : 'image/png';

        if ($isBase64 && '' !== $content) {
            return [
                $this->decodeBase64Image($content),
                $contentType,
            ];
        }

        if ('' !== $url) {
            return $this->fetchRemoteImage($url);
        }

        throw new RuntimeException('The AI provider did not return usable image content.');
    }

    private function decodeBase64Image(string $content): string
    {
        if (str_starts_with($content, 'data:')) {
            $commaPosition = strpos($content, ',');
            if (false === $commaPosition) {
                throw new InvalidArgumentException('The generated image data URI is invalid.');
            }

            $content = substr($content, $commaPosition + 1);
        }

        $binary = base64_decode(
            preg_replace('/\s+/', '', $content) ?? '',
            true,
        );

        if (false === $binary || '' === $binary) {
            throw new RuntimeException('The AI provider returned invalid base64 image content.');
        }

        if (\strlen($binary) > self::MAX_IMAGE_BYTES) {
            throw new RuntimeException('The generated image exceeds the maximum allowed size.');
        }

        return $binary;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function fetchRemoteImage(string $url): array
    {
        if (!$this->isSafeRemoteUrl($url)) {
            throw new RuntimeException('The remote image URL is not allowed.');
        }

        $response = $this->httpClient->request('GET', $url, [
            'headers' => ['Accept' => 'image/*'],
            'max_redirects' => 0,
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('The remote image could not be downloaded.');
        }

        $headers = $response->getHeaders(false);
        $length = $headers['content-length'][0] ?? null;

        if (
            null !== $length
            && is_numeric($length)
            && (int) $length > self::MAX_IMAGE_BYTES
        ) {
            throw new RuntimeException('The remote image exceeds the maximum allowed size.');
        }

        $binary = $response->getContent(false);
        if ('' === $binary || \strlen($binary) > self::MAX_IMAGE_BYTES) {
            throw new RuntimeException('The remote image content is empty or too large.');
        }

        $contentType = $headers['content-type'][0] ?? 'application/octet-stream';

        return [
            $binary,
            $this->normalizeMimeType((string) $contentType),
        ];
    }

    private function isSafeRemoteUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!\is_array($parts)) {
            return false;
        }

        if ('https' !== strtolower((string) ($parts['scheme'] ?? ''))) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if (
            '' === $host
            || \in_array($host, ['localhost', '127.0.0.1', '::1'], true)
        ) {
            return false;
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        if (false === $records || [] === $records) {
            return false;
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if (!\is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
                return false;
            }

            if (
                !filter_var(
                    $ip,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
                )
            ) {
                return false;
            }
        }

        return true;
    }

    private function detectImageMimeType(string $binary): string
    {
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->buffer($binary);

        return \is_string($mimeType)
            ? $this->normalizeMimeType($mimeType)
            : '';
    }

    private function normalizeMimeType(string $mimeType): string
    {
        return strtolower(trim(explode(';', $mimeType, 2)[0]));
    }

    private function buildSafeFileName(
        string $title,
        string $extension,
    ): string {
        $name = trim(strip_tags($title));
        $name = preg_replace('/[\/\\\\\x00-\x1F\x7F]+/u', '_', $name) ?? '';
        $name = preg_replace('/\s+/u', ' ', $name) ?? '';
        $name = trim($name, " .\t\n\r\0\x0B");

        if ('' === $name) {
            $name = 'generated_illustration';
        }

        $name = mb_substr($name, 0, 180);

        return $name.'.'.$extension;
    }
}
