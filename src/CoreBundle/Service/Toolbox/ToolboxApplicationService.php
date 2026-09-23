<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Toolbox;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\LearningPath\ScormPackageImporter;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CToolbox;
use Chamilo\CourseBundle\Entity\CToolboxVersion;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Repository\CToolboxRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Throwable;

final readonly class ToolboxApplicationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CToolboxRepository $toolboxRepository,
        private CLpRepository $learningPathRepository,
        private ToolboxAiGenerator $aiGenerator,
        private ToolboxScormPackageBuilder $packageBuilder,
        private ScormPackageImporter $packageImporter,
    ) {}

    public function create(
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $user,
        string $title,
        string $description,
        string $prompt,
        ?string $provider,
    ): CToolbox {
        $courseNode = $course->getResourceNode();
        if (null === $courseNode || null === $courseNode->getId()) {
            throw new RuntimeException('Course resource node is missing.');
        }

        $item = (new CToolbox())
            ->setTitle($title)
            ->setDescription('' !== trim($description) ? trim($description) : null)
            ->setCurrentVersion(1)
            ->setCreator($user)
            ->setParentResourceNode((int) $courseNode->getId())
            ->setResourceLinkArray([$this->resourceLinkPayload($course, $session, $group, false)])
        ;

        $this->toolboxRepository->create($item);

        try {
            $this->generateVersion($item, $course, $session, $group, $user, $prompt, $provider);
        } catch (Throwable $exception) {
            try {
                $this->toolboxRepository->delete($item);
            } catch (Throwable) {
                // Keep the generation exception as the actionable error.
            }

            throw $exception;
        }

        return $item;
    }

    public function generateVersion(
        CToolbox $item,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $user,
        string $prompt,
        ?string $provider,
    ): CToolboxVersion {
        $current = $item->getCurrentVersionEntity();
        $previousSource = $current instanceof CToolboxVersion && $current->hasGeneratedSource()
            ? [
                'html' => (string) $current->getHtmlContent(),
                'css' => (string) $current->getCssContent(),
                'javascript' => (string) $current->getJavascriptContent(),
            ]
            : null;

        $generated = $this->aiGenerator->generate(
            $user,
            $item->getTitle(),
            $prompt,
            $provider,
            $previousSource,
        );

        $reuseInitialVersion = $this->canReuseInitialPlaceholder($item);
        $versionNumber = $reuseInitialVersion ? 1 : $this->nextVersionNumber($item);

        $package = $this->packageBuilder->build(
            $item->getTitle(),
            $generated['html'],
            $generated['css'],
            $generated['javascript'],
            (int) $item->getIid(),
            $versionNumber,
        );

        try {
            $created = $this->packageImporter->import(
                $package['file'],
                $course,
                $session,
                $group,
                true,
                'local',
                'Toolbox',
                false,
            );
        } finally {
            if (is_file($package['path'])) {
                @unlink($package['path']);
            }
        }

        if (1 !== \count($created)) {
            throw new RuntimeException('The generated Toolbox SCORM package did not create exactly one learning path.');
        }

        $learningPathId = (int) ($created[0]['id'] ?? 0);
        $learningPath = $this->learningPathRepository->find($learningPathId);
        if (!$learningPath instanceof CLp) {
            throw new RuntimeException('The generated Toolbox learning path could not be loaded.');
        }

        $learningPath
            ->setTitle($item->getTitle())
            ->setContentMaker('Toolbox')
            ->setAuthor($user->getFullName())
            ->setHideTocFrame(true)
            ->setDefaultViewMod('embedded')
            ->setForceCommit(true)
            ->setPreventReinit(false)
            ->setSeriousgameMode(false)
            ->setMaxAttempts(0)
        ;
        $this->entityManager->persist($learningPath);

        $visible = $this->isPublished($item, $course, $session, $group);
        $this->setLearningPathVisibility($learningPath, $course, $session, $group, $visible);

        if ($current instanceof CToolboxVersion
            && $current->getLearningPath() instanceof CLp
            && (int) $current->getLearningPath()->getIid() !== $learningPathId
        ) {
            $this->setLearningPathVisibility($current->getLearningPath(), $course, $session, $group, false);
        }

        if ($reuseInitialVersion) {
            $version = $item->getVersions()->first();
            if (!$version instanceof CToolboxVersion) {
                throw new RuntimeException('The initial Toolbox version could not be reused.');
            }
        } else {
            $version = (new CToolboxVersion())
                ->setToolbox($item)
                ->setVersionNumber($versionNumber)
                ->setCreatedBy($user)
                ->setCreatedAt(new DateTime())
            ;
            $item->addVersion($version);
        }

        $version
            ->setTitle($item->getTitle())
            ->setDescription('' !== $generated['description'] ? $generated['description'] : $item->getDescription())
            ->setPrompt($prompt)
            ->setProvider($generated['provider'])
            ->setChangeSummary(
                '' !== $generated['changeSummary']
                    ? $generated['changeSummary']
                    : (1 === $versionNumber ? 'Initial generated version.' : 'AI-generated update.')
            )
            ->setHtmlContent($generated['html'])
            ->setCssContent($generated['css'])
            ->setJavascriptContent($generated['javascript'])
            ->setLearningPath($learningPath)
        ;

        if (null === $item->getDescription() && '' !== $generated['description']) {
            $item->setDescription($generated['description']);
        }
        $item->setCurrentVersion($versionNumber);

        $this->entityManager->persist($version);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $version;
    }

    public function setPublished(
        CToolbox $item,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        bool $visible,
    ): void {
        $itemLink = $this->getContextResourceLink($item, $course, $session, $group);
        if (!$itemLink instanceof ResourceLink) {
            throw new RuntimeException('The Toolbox item is not linked to the current context.');
        }
        if ($visible && !($item->getCurrentVersionEntity()?->getLearningPath() instanceof CLp)) {
            throw new RuntimeException('Generate the Toolbox application before publishing it.');
        }

        $itemLink->setVisibility($visible ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT);
        $this->entityManager->persist($itemLink);

        foreach ($item->getVersions() as $version) {
            $learningPath = $version->getLearningPath();
            if (!$learningPath instanceof CLp) {
                continue;
            }

            $this->setLearningPathVisibility(
                $learningPath,
                $course,
                $session,
                $group,
                $visible && $version->getVersionNumber() === $item->getCurrentVersion(),
            );
        }

        $this->entityManager->flush();
    }

    public function restoreVersion(
        CToolbox $item,
        int $versionNumber,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): CToolboxVersion {
        $target = null;
        foreach ($item->getVersions() as $version) {
            if ($version->getVersionNumber() === $versionNumber) {
                $target = $version;

                break;
            }
        }

        if (!$target instanceof CToolboxVersion || !$target->getLearningPath() instanceof CLp) {
            throw new RuntimeException('The requested Toolbox version cannot be restored.');
        }

        $visible = $this->isPublished($item, $course, $session, $group);
        foreach ($item->getVersions() as $version) {
            $learningPath = $version->getLearningPath();
            if ($learningPath instanceof CLp) {
                $this->setLearningPathVisibility(
                    $learningPath,
                    $course,
                    $session,
                    $group,
                    $visible && $version->getVersionNumber() === $versionNumber,
                );
            }
        }

        $item->setCurrentVersion($versionNumber);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $target;
    }

    public function isPublished(
        CToolbox $item,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): bool {
        $link = $this->getContextResourceLink($item, $course, $session, $group);

        return $link instanceof ResourceLink && ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility();
    }

    private function canReuseInitialPlaceholder(CToolbox $item): bool
    {
        if (1 !== $item->getVersions()->count()) {
            return false;
        }

        $version = $item->getVersions()->first();

        return $version instanceof CToolboxVersion
            && 1 === $version->getVersionNumber()
            && !($version->getLearningPath() instanceof CLp)
            && !$version->hasGeneratedSource()
            && null === $version->getPrompt();
    }

    private function nextVersionNumber(CToolbox $item): int
    {
        $max = 0;
        foreach ($item->getVersions() as $version) {
            $max = max($max, $version->getVersionNumber());
        }

        return $max + 1;
    }

    /**
     * @return array<string, int>
     */
    private function resourceLinkPayload(
        Course $course,
        ?Session $session,
        ?CGroup $group,
        bool $visible,
    ): array {
        $link = [
            'cid' => (int) $course->getId(),
            'visibility' => $visible ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT,
        ];
        if ($session instanceof Session) {
            $link['sid'] = (int) $session->getId();
        }
        if ($group instanceof CGroup) {
            $link['gid'] = (int) $group->getIid();
        }

        return $link;
    }

    private function setLearningPathVisibility(
        CLp $learningPath,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        bool $visible,
    ): void {
        $link = $this->getContextResourceLink($learningPath, $course, $session, $group);
        if (!$link instanceof ResourceLink) {
            throw new RuntimeException('The generated Toolbox learning path is not linked to the current context.');
        }

        $link->setVisibility($visible ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT);
        $this->entityManager->persist($link);
    }

    private function getContextResourceLink(
        object $resource,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): ?ResourceLink {
        if (!method_exists($resource, 'getResourceNode')) {
            return null;
        }

        $resourceNode = $resource->getResourceNode();
        if (null === $resourceNode) {
            return null;
        }

        $link = $this->findExactResourceLink($resourceNode->getResourceLinks(), $course, $session, $group);
        if ($link instanceof ResourceLink) {
            return $link;
        }

        // Course resources can be inherited inside a session when no session-specific
        // resource link exists. Keep the same fallback used elsewhere, but make it exact.
        if ($session instanceof Session && !($group instanceof CGroup)) {
            return $this->findExactResourceLink($resourceNode->getResourceLinks(), $course, null, null);
        }

        return null;
    }

    /**
     * ResourceNode::getResourceLinkByContext() only adds criteria for non-null
     * context values. For a course-only request that can accidentally return a
     * session/group link from the same course. Toolbox visibility must target the
     * exact context so publish/hide changes are read back consistently.
     *
     * @param iterable<ResourceLink> $links
     */
    private function findExactResourceLink(
        iterable $links,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): ?ResourceLink {
        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $groupId = (int) ($group?->getIid() ?? 0);

        foreach ($links as $link) {
            if (!$link instanceof ResourceLink) {
                continue;
            }

            if ((int) ($link->getCourse()?->getId() ?? 0) !== $courseId) {
                continue;
            }
            if ((int) ($link->getSession()?->getId() ?? 0) !== $sessionId) {
                continue;
            }
            if ((int) ($link->getGroup()?->getIid() ?? 0) !== $groupId) {
                continue;
            }

            return $link;
        }

        return null;
    }
}
