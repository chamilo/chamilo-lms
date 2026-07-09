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
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<CourseDescriptionItem, CourseDescriptionItem>
 */
final readonly class CourseDescriptionItemProcessor implements ProcessorInterface
{
    use CourseDescriptionAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CCourseDescriptionRepository $courseDescriptionRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
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

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $this->assertCourseDescriptionToolEnabled($this->entityManager, $course);
        $session = $this->getSession($request);
        $this->assertSessionBelongsToCourse($session, $course);

        if ($this->isStudentView($request) || !$this->canManageCourseDescriptions(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to manage course descriptions in this context.');
        }

        $this->validateCsrfToken($data->csrfToken);
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

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if ($sessionId <= 0) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
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

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(CourseDescriptionItemProvider::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
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
        if (\class_exists('Security') && \defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS($content, \COURSEMANAGERLOWSECURITY);
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

    private function isStudentView(Request $request): bool
    {
        if ($request->query->has('isStudentView')) {
            return $request->query->getBoolean('isStudentView');
        }

        if (!$request->hasSession()) {
            return false;
        }

        return 'studentview' === $request->getSession()->get('studentview');
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
        $item->csrfToken = (string) $this->csrfTokenManager->getToken(CourseDescriptionItemProvider::CSRF_TOKEN_ID);

        return $item;
    }
}
