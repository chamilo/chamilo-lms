<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseDescription;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseDescription\CourseDescriptionItem;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CourseDescriptionHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const COURSEMANAGERLOWSECURITY;

/**
 * @implements ProcessorInterface<CourseDescriptionItem, CourseDescriptionItem>
 */
final readonly class CourseDescriptionItemProcessor implements ProcessorInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private EntityManagerInterface $entityManager,
        private CCourseDescriptionRepository $courseDescriptionRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CourseDescriptionHelper $courseDescriptionHelper,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CourseDescriptionItem
    {
        if (!$data instanceof CourseDescriptionItem) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $this->courseDescriptionHelper->assertToolEnabled($course);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->courseDescriptionHelper->assertSessionBelongsToCourse($session, $course);

        if (!$this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session)) {
            throw new AccessDeniedHttpException('You are not allowed to manage course descriptions in this context.');
        }

        $descriptionType = $this->normalizeDescriptionType($data->descriptionType);
        $title = trim($data->title);
        $content = trim($data->content);

        if ('' === $title) {
            throw new BadRequestHttpException('The title is required.');
        }

        if ('' === $content) {
            throw new BadRequestHttpException('The content is required.');
        }

        $description = null;
        if ($operation instanceof Put) {
            $descriptionId = isset($uriVariables['iid']) ? (int) $uriVariables['iid'] : 0;
            $description = $this->getDescriptionFromOwnContext($descriptionId, $course, $session);
        } elseif (CCourseDescription::TYPE_CUSTOM !== $descriptionType) {
            $description = $this->findOwnDescriptionByType($descriptionType, $course, $session);
        }

        $isNew = !$description instanceof CCourseDescription;
        if ($isNew) {
            $description = new CCourseDescription();
            $description
                ->setParent($course)
                ->addCourseLink($course, $session)
                ->setDescriptionType($descriptionType)
            ;
        }

        if ($this->isSettingEnabled('search.search_enabled')) {
            $description->setSkipSearchIndex(!$data->enableSearch);
        }

        $description
            ->setTitle($this->sanitizeTitle($title))
            ->setContent($this->sanitizeContent($content))
            ->setProgress($data->progress)
        ;

        if ($isNew) {
            $this->courseDescriptionRepository->create($description);
        }

        $this->applyResourceLanguage($description, $data->language);
        $this->courseDescriptionRepository->update($description);

        return $this->buildResponse($description);
    }

    private function normalizeDescriptionType(int $descriptionType): int
    {
        if (!\in_array($descriptionType, CCourseDescription::getTypes(), true)) {
            throw new BadRequestHttpException('The course description type is invalid.');
        }

        return $descriptionType;
    }

    private function getDescriptionFromOwnContext(int $descriptionId, Course $course, ?Session $session): CCourseDescription
    {
        if ($descriptionId <= 0) {
            throw new BadRequestHttpException('A valid course description id is required.');
        }

        $description = $this->courseDescriptionRepository->find($descriptionId);
        if (!$description instanceof CCourseDescription) {
            throw new NotFoundHttpException('The requested course description was not found.');
        }

        if (!$this->belongsToExactContext($description, $course, $session)) {
            throw new AccessDeniedHttpException('The requested course description does not belong to the current course context.');
        }

        $resourceNode = $description->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this course description.');
        }

        return $description;
    }

    private function findOwnDescriptionByType(int $descriptionType, Course $course, ?Session $session): ?CCourseDescription
    {
        $descriptions = $this->courseDescriptionRepository->findByTypeInCourse($descriptionType, $course, $session);
        foreach ($descriptions as $description) {
            if (!$description instanceof CCourseDescription) {
                continue;
            }

            if ($this->belongsToExactContext($description, $course, $session)) {
                return $description;
            }
        }

        return null;
    }

    private function belongsToExactContext(CCourseDescription $description, Course $course, ?Session $session): bool
    {
        $resourceNode = $description->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink) {
                continue;
            }

            $linkCourse = $link->getCourse();
            $linkSession = $link->getSession();
            $sameCourse = null !== $linkCourse && $linkCourse->getId() === $course->getId();
            $sameSession = null === $session
                ? null === $linkSession
                : null !== $linkSession && $linkSession->getId() === $session->getId();

            if ($sameCourse && $sameSession) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeTitle(string $title): string
    {
        if ($this->isSettingEnabled('editor.save_titles_as_html')) {
            return $this->sanitizeContent($title);
        }

        return trim(strip_tags($title));
    }

    private function sanitizeContent(string $content): string
    {
        if (class_exists('Security') && \defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);
        }

        return $content;
    }

    private function applyResourceLanguage(CCourseDescription $description, string $languageCode): void
    {
        $resourceNode = $description->getResourceNode();
        if (null === $resourceNode) {
            return;
        }

        $languageCode = trim($languageCode);
        $language = null;

        if ('' !== $languageCode) {
            $language = $this->entityManager
                ->getRepository(Language::class)
                ->findOneBy([
                    'isocode' => $languageCode,
                    'available' => true,
                ])
            ;

            if (!$language instanceof Language) {
                throw new BadRequestHttpException('The selected language is invalid.');
            }
        }

        $resourceNode->setLanguage($language);
        $this->entityManager->persist($resourceNode);
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function buildResponse(CCourseDescription $description): CourseDescriptionItem
    {
        $resourceNode = $description->getResourceNode();
        $language = $resourceNode?->getLanguage();

        $item = new CourseDescriptionItem();
        $item->iid = $description->getIid();
        $item->descriptionType = (int) $description->getDescriptionType();
        $item->title = (string) $description->getTitle();
        $item->content = (string) $description->getContent();
        $item->progress = (int) $description->getProgress();
        $item->language = null !== $language ? (string) $language->getIsocode() : '';
        $item->enableSearch = true;
        $item->canEdit = true;
        $item->isNew = false;

        return $item;
    }
}
