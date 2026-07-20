<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Notebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Notebook\NotebookItem;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Repository\CNotebookRepository;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use const JSON_THROW_ON_ERROR;

/**
 * @implements ProcessorInterface<NotebookItem, void>
 */
final readonly class NotebookDeleteProcessor implements ProcessorInterface
{
    use NotebookAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CNotebookRepository $notebookRepository,
        private Security $security,
        private UserHelper $userHelper,
        private SettingsManager $settingsManager,
        private NotebookWriteProtection $writeProtection,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
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

        $studentView = $this->isNotebookStudentView($request);
        if (!$this->canWriteNotebook(
            $this->entityManager,
            $this->security,
            $this->userHelper,
            $this->settingsManager,
            $course,
            $session,
            $studentView,
        )) {
            throw new AccessDeniedHttpException('Notebook is read-only in this context.');
        }

        $this->writeProtection->assertWriteAllowed($this->getSubmittedCsrfToken($request));

        $noteId = isset($uriVariables['iid']) ? (int) $uriVariables['iid'] : 0;
        $user = $this->getNotebookUser($this->userHelper);
        $note = $this->findOwnedNotebookInContext(
            $this->notebookRepository,
            $user,
            $course,
            $session,
            $noteId,
        );

        $this->notebookRepository->delete($note);
        $this->registerNotebookAction('deletenote', $course, $session, $noteId);
    }

    private function getSubmittedCsrfToken(Request $request): string
    {
        $content = trim($request->getContent());
        if ('' === $content) {
            return '';
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        if (!\is_array($payload)) {
            return '';
        }

        $token = $payload['csrfToken'] ?? '';

        return \is_string($token) ? $token : '';
    }

}
