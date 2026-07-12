<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Component\Mpdf\SafeMpdfHttpClient;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Wiki\WikiAccessHelperTrait;
use Chamilo\CoreBundle\State\Wiki\WikiPageExportService;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class WikiPageExportController extends AbstractController
{
    use WikiAccessHelperTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CWikiRepository $wikiRepository,
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
        private readonly WikiPageExportService $exportService,
        #[Autowire('%kernel.cache_dir%')]
        private readonly string $cacheDir,
    ) {}

    #[Route(
        '/api/wiki/page/{pageId}/export.pdf',
        name: 'api_wiki_page_export_pdf',
        requirements: ['pageId' => '\d+'],
        methods: ['GET'],
    )]
    public function pdf(int $pageId, Request $request): Response
    {
        [$wiki, $course, $session, $group, $nodeId, $canManage] = $this->getAuthorizedPage($pageId, $request);

        if (!$canManage && !$this->resolveWikiBoolean(
            $this->settingsManager->getSetting('document.students_export2pdf', true),
            true,
        )) {
            throw new AccessDeniedHttpException('PDF download is not allowed for learners.');
        }

        $export = $this->buildExport($wiki, $course, $session, $group, $nodeId, $request);
        $tempDir = $this->cacheDir.'/mpdf';
        if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Failed to create the PDF temporary directory.');
        }

        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        try {
            $mpdf = new Mpdf([
                'format' => 'A4',
                'orientation' => 'P',
                'tempDir' => $tempDir,
                'margin_left' => 16,
                'margin_right' => 16,
                'margin_top' => 16,
                'margin_bottom' => 16,
            ], SafeMpdfHttpClient::container());
            $mpdf->WriteHTML($export['document']);
            $binary = $mpdf->Output('', Destination::STRING_RETURN);
        } catch (MpdfException $exception) {
            throw new RuntimeException('Failed to generate the Wiki PDF.', 0, $exception);
        }

        $filename = $export['filename'].'.pdf';
        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
        );

        return new Response(
            $binary,
            Response::HTTP_OK,
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'Content-Disposition' => $disposition,
                'Content-Type' => 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    /**
     * @return array{0:CWiki,1:Course,2:Session|null,3:CGroup|null,4:int,5:bool}
     */
    private function getAuthorizedPage(int $pageId, Request $request): array
    {
        if ($pageId <= 0) {
            throw new BadRequestHttpException('A valid Wiki page id is required.');
        }

        $course = $this->getWikiCourse($this->entityManager, $request);
        $this->assertWikiToolEnabled($this->entityManager, $course);
        $nodeId = $this->assertWikiRouteNode($course, $request);
        $session = $this->getWikiSession($this->entityManager, $request);
        $this->assertWikiSessionBelongsToCourse($session, $course);
        $group = $this->getWikiGroup($this->entityManager, $request);
        $this->assertWikiGroupBelongsToContext($group, $course, $session);

        if (!$this->canReadWikiContext($this->security, $this->settingsManager, $course, $session, $group)) {
            throw new AccessDeniedHttpException('You are not allowed to view Wiki pages in this context.');
        }

        $studentView = $this->isWikiStudentView($request);
        $canManage = !$studentView && $this->canManageWikiContext(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        );
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

        $this->assertWikiPageVisible($this->security, $wiki, $canManage);

        return [$wiki, $course, $session, $group, $nodeId, $canManage];
    }

    /**
     * @return array{title:string,filename:string,content:string,body:string,document:string}
     */
    private function buildExport(
        CWiki $wiki,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        int $nodeId,
        Request $request,
    ): array {
        return $this->exportService->build(
            $wiki,
            $nodeId,
            [
                'cid' => (int) $course->getId(),
                'sid' => (int) ($session?->getId() ?? 0),
                'gid' => (int) ($group?->getIid() ?? 0),
            ],
            $this->isWikiCourseSettingEnabled(
                $this->entityManager,
                $course,
                'wiki_html_strict_filtering',
                false,
            ),
            $request->getSchemeAndHttpHost(),
        );
    }
}
