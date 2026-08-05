<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiPageAction;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiPageExportAction;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/**
 * @implements ProcessorInterface<WikiPageExportAction, WikiPageExportAction>
 */
final readonly class WikiPageExportProcessor implements ProcessorInterface
{
    use WikiAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CWikiRepository $wikiRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private WikiPageExportService $exportService,
        #[Autowire('%kernel.cache_dir%')]
        private string $cacheDir,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WikiPageExportAction
    {
        if (!$data instanceof WikiPageExportAction) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getWikiCourse($this->entityManager, $request);
        $this->assertWikiToolEnabled($this->entityManager, $course);
        $nodeId = $this->assertWikiRouteNode($course, $request);
        $session = $this->getWikiSession($this->entityManager, $request);
        $this->assertWikiSessionBelongsToCourse($session, $course);
        $group = $this->getWikiGroup($this->entityManager, $request);
        $this->assertWikiGroupBelongsToContext($group, $course, $session);

        if ($this->isWikiStudentView($request) || !$this->canManageWikiContext(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to export this Wiki page to Documents.');
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(
            WikiPageAction::CSRF_TOKEN_ID,
            $data->csrfToken,
        ))) {
            throw new AccessDeniedHttpException('The CSRF token is invalid.');
        }

        $pageId = (int) ($uriVariables['pageId'] ?? 0);
        if ($pageId <= 0) {
            throw new BadRequestHttpException('A valid Wiki page id is required.');
        }

        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $groupId = (int) ($group?->getIid() ?? 0);
        $sourceSessionId = $sessionId;
        $wiki = $this->wikiRepository->findLatestVersionInContext(
            $courseId,
            $pageId,
            $groupId,
            $sourceSessionId,
        );
        if (!$wiki instanceof CWiki && $sessionId > 0) {
            $sourceSessionId = 0;
            $wiki = $this->wikiRepository->findLatestVersionInContext(
                $courseId,
                $pageId,
                $groupId,
                $sourceSessionId,
            );
        }
        if (!$wiki instanceof CWiki) {
            throw new NotFoundHttpException('The requested Wiki page was not found in this context.');
        }

        $export = $this->exportService->build(
            $wiki,
            $nodeId,
            ['cid' => $courseId, 'sid' => $sessionId, 'gid' => $groupId],
            $this->isWikiCourseSettingEnabled(
                $this->entityManager,
                $course,
                'wiki_html_strict_filtering',
                false,
            ),
            $request->getSchemeAndHttpHost(),
        );
        $tempDirectory = $this->cacheDir.'/wiki-export';
        if (!is_dir($tempDirectory) && !mkdir($tempDirectory, 0775, true) && !is_dir($tempDirectory)) {
            throw new RuntimeException('Failed to create the Wiki export temporary directory.');
        }
        $tempFile = tempnam($tempDirectory, 'wiki-');
        if (false === $tempFile) {
            throw new RuntimeException('Failed to create the Wiki export file.');
        }

        $fileName = $export['filename'].'.html';

        try {
            if (false === file_put_contents($tempFile, $export['document'])) {
                throw new RuntimeException('Failed to write the Wiki export file.');
            }
            $document = new CDocument();
            $document->setTitle($fileName);
            $document->setUploadFile(new UploadedFile($tempFile, $fileName, 'text/html', null, true));
            $document->setFiletype('file');
            $document->setParentResourceNode($nodeId);
            $document->setResourceLinkArray([[
                'cid' => $courseId,
                'sid' => $sessionId,
                'gid' => $groupId,
                'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
            ]]);
            $this->entityManager->persist($document);
            $this->entityManager->flush();
        } catch (Throwable $exception) {
            @unlink($tempFile);

            throw $exception;
        }

        @unlink($tempFile);

        return $data;
    }
}
