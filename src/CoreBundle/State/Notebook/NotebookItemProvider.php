<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Notebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
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
 * @implements ProviderInterface<NotebookItem>
 */
final readonly class NotebookItemProvider implements ProviderInterface
{
    use NotebookAccessHelperTrait;

    public const string CSRF_TOKEN_ID = 'notebook_item';

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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): NotebookItem
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
        $canWrite = $this->canWriteNotebook(
            $this->entityManager,
            $this->security,
            $this->userHelper,
            $this->settingsManager,
            $course,
            $session,
            $studentView,
        );
        if (!$canWrite) {
            throw new AccessDeniedHttpException('Notebook is read-only in this context.');
        }

        $user = $this->getNotebookUser($this->userHelper);
        $noteId = isset($uriVariables['iid'])
            ? (int) $uriVariables['iid']
            : $request->query->getInt('id');
        $note = null;

        if ($noteId > 0) {
            $note = $this->findOwnedNotebookInContext(
                $this->notebookRepository,
                $user,
                $course,
                $session,
                $noteId,
            );
        }

        $item = $this->buildNotebookItem($note);
        $item->canWrite = true;
        $item->fullEditor = $this->canUseFullNotebookEditor(
            $this->entityManager,
            $this->security,
            $this->userHelper,
            $this->settingsManager,
            $course,
            $session,
        );
        $item->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $item->languages = $this->getLanguages();

        return $item;
    }

    private function buildNotebookItem(?CNotebook $note): NotebookItem
    {
        $item = new NotebookItem();
        if (!$note instanceof CNotebook) {
            return $item;
        }

        $resourceNode = $note->getResourceNode();
        $language = $resourceNode?->getLanguage();

        $item->iid = $note->getIid();
        $item->title = trim(strip_tags((string) $note->getTitle()));
        $item->content = $this->sanitizeNotebookContent((string) $note->getDescription());
        $item->language = null !== $language ? (string) $language->getIsocode() : '';
        $item->isNew = false;

        return $item;
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

    /**
     * @return array<int, array<string, string>>
     */
    private function getLanguages(): array
    {
        $languages = [
            [
                'label' => 'No specific language',
                'value' => '',
            ],
        ];

        $availableLanguages = $this->entityManager
            ->getRepository(Language::class)
            ->findBy(['available' => true], ['englishName' => 'ASC'])
        ;

        foreach ($availableLanguages as $language) {
            if (!$language instanceof Language) {
                continue;
            }

            $languages[] = [
                'label' => (string) ($language->getOriginalName() ?: $language->getEnglishName()),
                'value' => (string) $language->getIsocode(),
            ];
        }

        return $languages;
    }
}
