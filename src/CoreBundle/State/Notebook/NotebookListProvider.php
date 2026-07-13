<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Notebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Notebook\NotebookList;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CNotebook;
use Chamilo\CourseBundle\Repository\CNotebookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Security as LegacySecurity;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const COURSEMANAGERLOWSECURITY;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const DATE_ATOM;

/**
 * @implements ProviderInterface<NotebookList>
 */
final readonly class NotebookListProvider implements ProviderInterface
{
    use NotebookAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CNotebookRepository $notebookRepository,
        private Security $security,
        private UserHelper $userHelper,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): NotebookList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getNotebookCourse($this->entityManager, $request);
        $session = $this->getNotebookSession($this->entityManager, $request);
        $this->assertNotebookSessionBelongsToCourse($session, $course);

        if (!$this->canReadNotebook(
            $this->security,
            $this->userHelper,
            $this->settingsManager,
            $course,
            $session,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to view Notebook in this context.');
        }

        $user = $this->getNotebookUser($this->userHelper);
        $studentView = $this->isNotebookStudentView($request);
        $canWrite = $this->canWriteNotebook(
            $this->entityManager,
            $this->security,
            $this->userHelper,
            $this->settingsManager,
            $course,
            $session,
            $studentView,
        );
        $sort = $this->resolveSort($request);
        $direction = $this->normalizeDirection($request->query->getString('direction'));

        $list = new NotebookList();
        $list->courseId = (int) $course->getId();
        $list->sessionId = null !== $session ? (int) $session->getId() : null;
        $list->canWrite = $canWrite;
        $list->studentView = $studentView;
        $list->csrfToken = $canWrite
            ? (string) $this->csrfTokenManager->getToken(NotebookItemProvider::CSRF_TOKEN_ID)
            : '';
        $list->sort = $sort;
        $list->direction = $direction;

        $notes = $this->notebookRepository->findByUser($user, $course, $session, $sort, $direction);
        foreach ($notes as $note) {
            if (!$note instanceof CNotebook || null === $note->getIid()) {
                continue;
            }

            $list->items[] = $this->normalizeNote($note, $course, $session, $canWrite);
        }

        $list->totalItems = \count($list->items);

        $this->registerNotebookToolAccess();
        $this->registerNotebookAction(
            $request->query->has('sort') ? 'changeview' : '',
            $course,
            $session,
        );

        return $list;
    }

    private function resolveSort(Request $request): string
    {
        $requestedSort = $request->query->getString('sort');
        if (\in_array($requestedSort, ['creation_date', 'update_date', 'title'], true)) {
            if ($request->hasSession()) {
                $request->getSession()->set('notebook_view', $requestedSort);
            }

            return $requestedSort;
        }

        if ($request->hasSession()) {
            $storedSort = (string) $request->getSession()->get('notebook_view', 'creation_date');
            if (\in_array($storedSort, ['creation_date', 'update_date', 'title'], true)) {
                return $storedSort;
            }

            $request->getSession()->set('notebook_view', 'creation_date');
        }

        return 'creation_date';
    }

    private function normalizeDirection(string $direction): string
    {
        return 'DESC' === strtoupper($direction) ? 'DESC' : 'ASC';
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeNote(
        CNotebook $note,
        Course $course,
        ?Session $session,
        bool $canWrite,
    ): array {
        $resourceNode = $note->getResourceNode();
        $language = $resourceNode?->getLanguage();
        $creationDate = $note->getCreationDate();
        $updateDate = $note->getUpdateDate();
        $hasUpdate = $updateDate->getTimestamp() !== $creationDate->getTimestamp();

        return [
            'iid' => (int) $note->getIid(),
            'title' => trim(strip_tags((string) $note->getTitle())),
            'content' => $this->sanitizeNotebookContent((string) $note->getDescription()),
            'creationDate' => $creationDate->format(DATE_ATOM),
            'updateDate' => $hasUpdate ? $updateDate->format(DATE_ATOM) : null,
            'sessionId' => $this->getContextSessionId($note, $course, $session),
            'language' => null !== $language ? (string) $language->getIsocode() : null,
            'canEdit' => $canWrite,
            'canDelete' => $canWrite,
        ];
    }

    private function sanitizeNotebookContent(string $content): string
    {
        if (class_exists(LegacySecurity::class)) {
            if (\defined('COURSEMANAGERLOWSECURITY')) {
                return (string) LegacySecurity::remove_XSS($content, COURSEMANAGERLOWSECURITY);
            }

            return (string) LegacySecurity::remove_XSS($content);
        }

        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function getContextSessionId(CNotebook $note, Course $course, ?Session $session): ?int
    {
        $resourceNode = $note->getResourceNode();
        if (null === $resourceNode) {
            return null;
        }

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink || null !== $link->getDeletedAt()) {
                continue;
            }

            $linkCourse = $link->getCourse();
            $linkSession = $link->getSession();
            $sameCourse = null !== $linkCourse && $linkCourse->getId() === $course->getId();
            $sameSession = null === $session
                ? null === $linkSession
                : null !== $linkSession && $linkSession->getId() === $session->getId();

            if ($sameCourse && $sameSession) {
                return null !== $linkSession ? (int) $linkSession->getId() : null;
            }
        }

        return null;
    }
}
