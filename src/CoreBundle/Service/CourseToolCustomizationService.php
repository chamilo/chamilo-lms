<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Security\FloorPlanSvgValidator;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class CourseToolCustomizationService
{
    private const string ICON_METADATA_KEY = 'course_home_custom_icon';
    private const int MAX_ICON_BYTES = 262144;
    private const int MAX_TITLE_LENGTH = 120;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResourceNodeRepository $resourceNodeRepository,
        private FloorPlanSvgValidator $svgValidator,
    ) {}

    public static function getCustomTitle(CTool $tool): ?string
    {
        $storedTitle = trim($tool->getTitle());
        $defaultTitle = trim($tool->getTool()->getTitle());

        return '' !== $storedTitle && 0 !== strcasecmp($storedTitle, $defaultTitle) ? $storedTitle : null;
    }

    public static function getCustomIcon(CTool $tool): ?ResourceFile
    {
        $node = $tool->getResourceNode();
        if (null === $node) {
            return null;
        }

        foreach ($node->getResourceFiles() as $resourceFile) {
            if (true === ($resourceFile->getMetadata()[self::ICON_METADATA_KEY] ?? false)) {
                return $resourceFile;
            }
        }

        return null;
    }

    public function getCustomIconUrl(CTool $tool): ?string
    {
        $file = self::getCustomIcon($tool);
        $node = $tool->getResourceNode();
        if (null === $file || null === $node) {
            return null;
        }

        return $this->resourceNodeRepository->getResourceFileUrl($node, [
            'cid' => $tool->getCourse()->getId(),
            'sid' => 0,
            'gid' => 0,
        ], null, $file);
    }

    /**
     * @return array{customTitle: ?string, customIconUrl: ?string}
     */
    public function update(CTool $tool, string $title, ?UploadedFile $icon): array
    {
        $title = trim($title);
        if ('' === $title || mb_strlen($title) > self::MAX_TITLE_LENGTH
            || preg_match('/[<>\x00-\x1F\x7F]/u', $title)
        ) {
            throw new BadRequestHttpException('The tool name must be plain text between 1 and 120 characters.');
        }

        $node = $tool->getResourceNode();
        if (null === $node) {
            throw new BadRequestHttpException('The course tool has no resource node.');
        }

        if ($icon instanceof UploadedFile) {
            $size = $icon->getSize();
            if (!$icon->isValid() || false === $size || $size < 1 || $size > self::MAX_ICON_BYTES
                || 'svg' !== strtolower($icon->getClientOriginalExtension())
            ) {
                throw new BadRequestHttpException('Upload an SVG icon no larger than 256 KB.');
            }

            $contents = file_get_contents($icon->getPathname());
            if (false === $contents || !$this->svgValidator->isSafe($contents)) {
                throw new BadRequestHttpException('The icon must be a safe static SVG with width and height.');
            }
        }

        // c_tool.title is per-course; tool.title remains the immutable internal identifier.
        $tool->setTitle($title);

        if ($icon instanceof UploadedFile) {
            $previous = self::getCustomIcon($tool);
            $resourceFile = (new ResourceFile())
                ->setTitle('course-tool-icon.svg')
                ->setMimeType('image/svg+xml')
                ->setMetadata([self::ICON_METADATA_KEY => true])
                ->setFile($icon)
            ;
            $node->addResourceFile($resourceFile);
            $this->entityManager->persist($resourceFile);

            if ($previous instanceof ResourceFile) {
                $node->removeResourceFile($previous);
                $this->entityManager->remove($previous);
            }
        }

        $this->entityManager->flush();

        return [
            'customTitle' => self::getCustomTitle($tool),
            'customIconUrl' => $this->getCustomIconUrl($tool),
        ];
    }
}
