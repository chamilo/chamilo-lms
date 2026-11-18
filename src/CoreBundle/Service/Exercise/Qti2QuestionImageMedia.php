<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;
use ZipArchive;

use const ENT_HTML5;
use const ENT_QUOTES;
use const PHP_URL_QUERY;

/**
 * QTI 2.1 image transport for a question in the current course context.
 * Never fetches remote URLs, reads arbitrary filesystem paths, or creates public files.
 */
final readonly class Qti2QuestionImageMedia
{
    public const LABEL = 'chamilo:unique-answer-image';
    private const MAX_BASE64_LENGTH = 7 * 1024 * 1024;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResourceNodeRepository $resourceNodeRepository,
        private CQuizQuestionRepository $questionRepository,
        private RequestStack $requestStack,
    ) {}

    /**
     * @return array{path: string, mime: string, width: int, height: int}
     */
    public function exportQuestionImage(CQuizQuestion $question, ZipArchive $archive, Qti2MediaArchive $media): array
    {
        $node = $question->getResourceNode();
        $file = $node?->getFirstResourceFile();
        if (!$node instanceof ResourceNode || !$file instanceof ResourceFile || null !== $file->getAccessUrl()) {
            throw new InvalidArgumentException('The QTI hotspot question has no eligible image resource.');
        }
        $content = $this->resourceNodeRepository->getResourceNodeFileContent($node, $file);
        $path = $media->addImage($archive, $content, (string) ($file->getOriginalName() ?: 'hotspot.png'));
        $details = getimagesizefromstring($content);
        if (!\is_array($details)) {
            throw new InvalidArgumentException('Invalid stored hotspot image.');
        }

        return [
            'path' => $path,
            'mime' => (string) $details['mime'],
            'width' => (int) $details[0],
            'height' => (int) $details[1],
        ];
    }

    public function importQuestionImage(CQuizQuestion $question, ZipArchive $archive, string $path): void
    {
        if (null === $question->getResourceNode()) {
            throw new InvalidArgumentException('The imported hotspot question has no resource node.');
        }
        [$contents, $filename, $mime] = Qti2MediaArchive::readImage($archive, $path);
        $file = $this->questionRepository->addFileFromString($question, $filename, $mime, $contents, true);
        if (!$file instanceof ResourceFile) {
            throw new InvalidArgumentException('The imported hotspot image could not be stored.');
        }
    }

    public function exportHtml(string $html, ZipArchive $archive, Qti2MediaArchive $media, Course $course, ?Session $session): string
    {
        return $media->exportHtml(
            $html,
            $archive,
            fn (string $source): ?array => $this->resolveSource($source, $course, $session)
        );
    }

    public function validateImportedHtml(string $html, ZipArchive $archive): void
    {
        $html = self::decodeImportedHtml($html);
        if (false === stripos($html, '<img')) {
            return;
        }
        (new Qti2MediaArchive())->importHtml(
            $html,
            $archive,
            static fn (string $content, string $filename, string $mime): string => '/r/qti-validated-image'
        );
    }

    public function importHtml(string $html, ZipArchive $archive, CQuizQuestion $question): string
    {
        if (null === $question->getResourceNode()) {
            throw new InvalidArgumentException('The imported question has no resource node.');
        }

        return (new Qti2MediaArchive())->importHtml(
            self::decodeImportedHtml($html),
            $archive,
            function (string $content, string $filename, string $mime) use ($question): string {
                $resourceFile = $this->questionRepository->addFileFromString($question, $filename, $mime, $content, true);
                if (!$resourceFile instanceof ResourceFile) {
                    throw new InvalidArgumentException('The imported QTI image could not be stored.');
                }

                return $this->resourceNodeRepository->getResourceFileUrl(
                    $question->getResourceNode(),
                    [],
                    null,
                    $resourceFile
                );
            }
        );
    }

    /**
     * Exported choice HTML can arrive as escaped text inside a QTI CDATA element.
     * Decode it once before checking image paths or storing resource files.
     */
    private static function decodeImportedHtml(string $html): string
    {
        return html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @return array{content: string, filename: string}|null
     */
    private function resolveSource(string $source, Course $course, ?Session $session): ?array
    {
        if (preg_match('#^data:image/(png|jpeg|gif);base64,([a-zA-Z0-9+/=]+)$#D', $source, $matches)) {
            if (\strlen($matches[2]) > self::MAX_BASE64_LENGTH) {
                throw new InvalidArgumentException('The embedded QTI image exceeds the size limit.');
            }
            $content = base64_decode($matches[2], true);
            if (!\is_string($content)) {
                throw new InvalidArgumentException('Invalid embedded QTI image data.');
            }

            return ['content' => $content, 'filename' => 'embedded.'.$matches[1]];
        }

        $resourceId = self::resourceUuidFromUrl($source, $this->requestStack->getCurrentRequest()?->getHost());
        if (null === $resourceId) {
            return null;
        }
        $node = $this->entityManager->getRepository(ResourceNode::class)->findOneBy(['uuid' => $resourceId]);
        if (!$node instanceof ResourceNode || !$this->belongsToCourse($node, $course, $session)) {
            return null;
        }
        $file = $node->getFirstResourceFile();
        $query = parse_url($source, PHP_URL_QUERY);
        if (\is_string($query)) {
            parse_str($query, $parameters);
            if (isset($parameters['resourceFileId'])) {
                if (!ctype_digit((string) $parameters['resourceFileId'])) {
                    return null;
                }
                foreach ($node->getResourceFiles() as $candidate) {
                    if ((int) $candidate->getId() === (int) $parameters['resourceFileId']) {
                        $file = $candidate;

                        break;
                    }
                }
                if (!$file instanceof ResourceFile || (int) $file->getId() !== (int) $parameters['resourceFileId']) {
                    return null;
                }
            }
        }
        if (!$file instanceof ResourceFile || null !== $file->getAccessUrl()) {
            return null;
        }

        return [
            'content' => $this->resourceNodeRepository->getResourceNodeFileContent($node, $file),
            'filename' => (string) ($file->getOriginalName() ?: $file->getTitle() ?: 'image.png'),
        ];
    }

    /**
     * Accept only local authenticated resource-view routes, never arbitrary URLs or remote hosts.
     */
    public static function resourceUuidFromUrl(string $source, ?string $currentHost): ?string
    {
        $parts = parse_url($source);
        if (!\is_array($parts) || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            return null;
        }
        if (isset($parts['host']) && (null === $currentHost || 0 !== strcasecmp((string) $parts['host'], $currentHost))) {
            return null;
        }
        if (isset($parts['scheme']) && !\in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return null;
        }
        $path = (string) ($parts['path'] ?? '');
        if (preg_match('#(?:^|/)r/[^/]+/[^/]+/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/view$#iD', $path, $match)) {
            return $match[1];
        }

        return null;
    }

    private function belongsToCourse(ResourceNode $node, Course $course, ?Session $session): bool
    {
        foreach ($node->getResourceLinks() as $link) {
            if (null !== $link->getDeletedAt()
                || $link->getCourse()?->getId() !== $course->getId()
                || (null !== $link->getSession() && $link->getSession()?->getId() !== $session?->getId())) {
                continue;
            }

            return true;
        }

        return false;
    }
}
