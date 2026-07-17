<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Notebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Notebook\NotebookItem;
use Chamilo\CoreBundle\Entity\Language;
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

/**
 * @implements ProcessorInterface<NotebookItem, NotebookItem>
 */
final readonly class NotebookItemProcessor implements ProcessorInterface
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
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): NotebookItem
    {
        if (!$data instanceof NotebookItem) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

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

        $this->writeProtection->assertWriteAllowed($data->csrfToken);

        $title = trim(strip_tags($data->title));
        if ('' === $title) {
            throw new BadRequestHttpException('The title is required.');
        }

        if (mb_strlen($title) > 255) {
            throw new BadRequestHttpException('The title cannot exceed 255 characters.');
        }

        $user = $this->getNotebookUser($this->userHelper);
        $note = null;

        if ($operation instanceof Put) {
            $noteId = isset($uriVariables['iid']) ? (int) $uriVariables['iid'] : 0;
            $note = $this->findOwnedNotebookInContext(
                $this->notebookRepository,
                $user,
                $course,
                $session,
                $noteId,
            );
        }

        $isNew = !$note instanceof CNotebook;
        if ($isNew) {
            $note = new CNotebook();
            $note
                ->setParent($course)
                ->setUser($user)
                ->addCourseLink($course, $session)
            ;
        }

        $note
            ->setTitle($title)
            ->setDescription($this->sanitizeNotebookContent($data->content))
        ;

        if ($isNew) {
            $this->notebookRepository->create($note);
        }

        $this->applyResourceLanguage($note, $data->language);
        $this->notebookRepository->update($note);

        $this->registerNotebookAction($isNew ? 'addnote' : 'editnote', $course, $session, (int) $note->getIid());

        return $this->buildResponse($note);
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

    private function applyResourceLanguage(CNotebook $note, string $languageCode): void
    {
        $resourceNode = $note->getResourceNode();
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

    private function buildResponse(CNotebook $note): NotebookItem
    {
        $resourceNode = $note->getResourceNode();
        $language = $resourceNode?->getLanguage();

        $item = new NotebookItem();
        $item->iid = $note->getIid();
        $item->title = (string) $note->getTitle();
        $item->content = (string) $note->getDescription();
        $item->language = null !== $language ? (string) $language->getIsocode() : '';
        $item->csrfToken = (string) $this->csrfTokenManager->getToken(NotebookItemProvider::CSRF_TOKEN_ID);
        $item->canWrite = true;
        $item->isNew = false;

        return $item;
    }
}
