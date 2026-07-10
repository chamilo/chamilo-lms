<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Announcement;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Announcement\AnnouncementForm;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use DateTime;
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
 * @implements ProcessorInterface<AnnouncementForm, AnnouncementForm>
 */
final readonly class AnnouncementFormProcessor implements ProcessorInterface
{
    use AnnouncementAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CAnnouncementRepository $announcementRepository,
        private AnnouncementRecipientResolver $recipientResolver,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AnnouncementForm
    {
        if (!$data instanceof AnnouncementForm) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $this->assertAnnouncementToolEnabled($this->entityManager, $course);

        $session = $this->getSession($request);
        $this->assertSessionBelongsToCourse($session, $course);

        $group = $this->getGroup($request);
        $this->assertGroupBelongsToContext($group, $course, $session);

        if ($this->isStudentView($request) || !$this->canManageAnnouncements(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to manage announcements in this context.');
        }

        $this->validateCsrfToken($data->csrfToken);
        $selection = $this->recipientResolver->normalizeSelection(
            $data->recipients,
            $course,
            $session,
            $group,
        );

        if ('post_announcement_preview' === $operation->getName()) {
            $response = new AnnouncementForm();
            $response->csrfToken = (string) $this->csrfTokenManager->getToken(AnnouncementFormProvider::CSRF_TOKEN_ID);
            $response->recipients = $selection;
            $response->previewRecipients = $this->recipientResolver->getPreviewLabels(
                $selection,
                $course,
                $session,
                $group,
            );

            return $response;
        }

        $title = $this->sanitizeTitle($data->title);
        $content = $this->sanitizeContent($data->content);

        if ('' === $title) {
            throw new BadRequestHttpException('The subject is required.');
        }

        if ('' === trim(strip_tags($content))) {
            throw new BadRequestHttpException('The description is required.');
        }

        $language = $this->resolveResourceLanguage($data->language);
        $announcement = null;
        if ($operation instanceof Put) {
            $announcementId = isset($uriVariables['id']) ? (int) $uriVariables['id'] : 0;
            $announcement = $this->getAnnouncementForEdit($announcementId, $course, $session, $group);
        }

        $isNew = !$announcement instanceof CAnnouncement;
        if ($isNew) {
            $announcement = (new CAnnouncement())
                ->setParent($course)
                ->setTitle($title)
                ->setContent($content)
                ->setEndDate(new DateTime())
                ->setEmailSent(false)
            ;
        } else {
            $announcement
                ->setTitle($title)
                ->setContent($content)
            ;
        }

        $this->recipientResolver->replaceRecipientLinks(
            $announcement,
            $course,
            $session,
            $group,
            $selection,
        );

        if ($isNew) {
            $this->announcementRepository->create($announcement);
        }

        $this->applyResourceLanguage($announcement, $language);
        $this->announcementRepository->update($announcement);
        $this->entityManager->refresh($announcement);

        $response = new AnnouncementForm();
        $response->id = $announcement->getIid();
        $response->title = $announcement->getTitle();
        $response->content = (string) $announcement->getContent();
        $response->language = (string) ($announcement->getResourceNode()?->getLanguage()?->getIsocode() ?? '');
        $response->recipients = $selection;
        $response->csrfToken = (string) $this->csrfTokenManager->getToken(AnnouncementFormProvider::CSRF_TOKEN_ID);
        $response->canEdit = true;
        $response->isNew = false;
        $response->groupContext = $group instanceof CGroup;

        return $response;
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

    private function getGroup(Request $request): ?CGroup
    {
        $groupId = $request->query->getInt('gid');
        if ($groupId <= 0) {
            return null;
        }

        $group = $this->entityManager->getRepository(CGroup::class)->find($groupId);
        if (!$group instanceof CGroup) {
            throw new BadRequestHttpException('The requested group was not found.');
        }

        return $group;
    }

    private function getAnnouncementForEdit(
        int $announcementId,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): CAnnouncement {
        if ($announcementId <= 0) {
            throw new BadRequestHttpException('A valid announcement id is required.');
        }

        $announcement = $this->announcementRepository->find($announcementId);
        if (!$announcement instanceof CAnnouncement) {
            throw new NotFoundHttpException('The requested announcement was not found.');
        }

        if ([] === $this->recipientResolver->getScopedLinks($announcement, $course, $session, $group)) {
            throw new AccessDeniedHttpException('The requested announcement does not belong to the current course context.');
        }

        if ($group instanceof CGroup && $this->recipientResolver->hasMultipleGroupTargets(
            $announcement,
            $course,
            $session,
        )) {
            throw new AccessDeniedHttpException('This announcement targets several groups and cannot be edited from one group.');
        }

        if (!$this->canEditAnnouncement(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $announcement,
            $course,
            $session,
            $group,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to edit this announcement.');
        }

        return $announcement;
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(AnnouncementFormProvider::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }

    private function sanitizeTitle(string $title): string
    {
        return trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function sanitizeContent(string $content): string
    {
        $content = trim($content);
        if (\class_exists(\Security::class) && \defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS($content, \COURSEMANAGERLOWSECURITY);
        }

        return $content;
    }

    private function resolveResourceLanguage(string $languageCode): ?Language
    {
        $languageCode = trim($languageCode);
        if ('' === $languageCode) {
            return null;
        }

        $language = $this->entityManager
            ->getRepository(Language::class)
            ->findOneBy([
                'isocode' => $languageCode,
                'available' => true,
            ])
        ;

        if (!$language instanceof Language) {
            throw new BadRequestHttpException('The selected resource language is invalid.');
        }

        return $language;
    }

    private function applyResourceLanguage(CAnnouncement $announcement, ?Language $language): void
    {
        $resourceNode = $announcement->getResourceNode();
        if (null === $resourceNode) {
            return;
        }

        $resourceNode->setLanguage($language);
    }
}
