<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Component\Mpdf\SafeMpdfHttpClient;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\LpAdvancedAccessHelper;
use Chamilo\CoreBundle\Service\LearningPath\LearningPathContentPdfService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\LearningPath\LearningPathStateHelperTrait;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CLpRepository;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class LearningPathContentPdfAction extends AbstractController
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
        private readonly LpAdvancedAccessHelper $advancedAccessHelper,
        private readonly CLpRepository $lpRepository,
        private readonly LearningPathContentPdfService $contentPdfService,
        #[Autowire('%kernel.cache_dir%')]
        private readonly string $cacheDir,
    ) {}

    #[Route(
        '/api/learning_paths/{lpId}/content-pdf/items',
        name: 'api_learning_path_content_pdf_items',
        requirements: ['lpId' => '\\d+'],
        methods: ['GET'],
    )]
    public function items(int $lpId, Request $request): JsonResponse
    {
        [$lp, $course, $session, $group] = $this->getAuthorizedContext($lpId, $request);
        $items = $this->contentPdfService->getExportableItems($lp, $course, $session, $group);

        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        return $this->json(['items' => $items]);
    }

    #[Route(
        '/api/learning_paths/{lpId}/content.pdf',
        name: 'api_learning_path_content_pdf',
        requirements: ['lpId' => '\\d+'],
        methods: ['GET'],
    )]
    public function pdf(int $lpId, Request $request): Response
    {
        [$lp, $course, $session, $group] = $this->getAuthorizedContext($lpId, $request);
        $selectedItemIds = $this->parseSelectedItemIds((string) $request->query->get('items', ''));
        $html = $this->contentPdfService->buildHtml($lp, $course, $session, $group, $selectedItemIds);
        if ('' === $html) {
            throw new BadRequestHttpException('No exportable learning path items were selected.');
        }

        $tempDir = $this->cacheDir.'/mpdf';
        if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Failed to create the Mpdf temporary directory.');
        }

        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        try {
            $mpdf = new Mpdf([
                'format' => 'A4',
                'orientation' => 'P',
                'tempDir' => $tempDir,
            ], SafeMpdfHttpClient::container());
            $mpdf->WriteHTML($html);
            $pdfBinary = $mpdf->Output('', Destination::STRING_RETURN);
        } catch (MpdfException $exception) {
            throw new RuntimeException('Failed to generate the learning path content PDF.', 0, $exception);
        }

        $filename = 'learning-path-content-'.$lpId.'-'.date('Ymd-His').'.pdf';
        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
        );

        return new Response(
            $pdfBinary,
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
     * @return array{0: CLp, 1: Course, 2: Session|null, 3: CGroup|null}
     */
    private function getAuthorizedContext(int $lpId, Request $request): array
    {
        if ($this->settingEnabled('lp.hide_scorm_pdf_link')) {
            throw new AccessDeniedHttpException('Learning path PDF export is disabled.');
        }

        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);
        $lp = $this->lpRepository->find($lpId);
        if (!$lp instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }

        $resourceNode = $lp->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('VIEW', $resourceNode)) {
            throw new AccessDeniedHttpException('The learning path is not available.');
        }

        $resourceLink = $this->getContextResourceLink($lp, $course, $session, $group);
        if (!$resourceLink instanceof ResourceLink) {
            throw new AccessDeniedHttpException('The learning path is not linked to the current context.');
        }

        if ($this->canManageLearningPaths($this->security)) {
            return [$lp, $course, $session, $group];
        }

        if (ResourceLink::VISIBILITY_PUBLISHED !== $resourceLink->getVisibility()) {
            throw new AccessDeniedHttpException('The learning path is not visible.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || !$this->advancedAccessHelper->isAllowed($course, $lp, $session, $user)) {
            throw new AccessDeniedHttpException('The learning path is not available to this user.');
        }

        $category = $lp->getCategory();
        if (null !== $category) {
            $categoryLink = $this->getContextResourceLink($category, $course, $session, $group);
            if (!$categoryLink instanceof ResourceLink
                || ResourceLink::VISIBILITY_PUBLISHED !== $categoryLink->getVisibility()
            ) {
                throw new AccessDeniedHttpException('The learning path category is not visible.');
            }
        }

        if (!$this->isCurrentlyAvailable($lp)) {
            throw new AccessDeniedHttpException('The learning path is not currently available.');
        }

        return [$lp, $course, $session, $group];
    }

    /** @return list<int> */
    private function parseSelectedItemIds(string $value): array
    {
        if ('' === trim($value)) {
            return [];
        }

        $ids = [];
        foreach (explode(',', $value) as $rawId) {
            $itemId = (int) trim($rawId);
            if ($itemId > 0) {
                $ids[$itemId] = $itemId;
            }
        }

        if (\count($ids) > 500) {
            throw new BadRequestHttpException('Too many learning path items were selected.');
        }

        return array_values($ids);
    }

    private function settingEnabled(string $name): bool
    {
        return \in_array(
            strtolower(trim((string) $this->settingsManager->getSetting($name, true))),
            ['1', 'true', 'yes', 'on'],
            true,
        );
    }

    private function isCurrentlyAvailable(CLp $lp): bool
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $startDate = $lp->getPublishedOn();
        $endDate = $lp->getExpiredOn();

        if ($startDate instanceof DateTimeInterface && $startDate > $now) {
            return false;
        }

        if ($endDate instanceof DateTimeInterface && $endDate < $now) {
            return false;
        }

        return true;
    }
}
