<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\AntivirusScan;
use Chamilo\CoreBundle\Helpers\CsvFormulaEscaper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AntivirusScanController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SettingsManager $settings,
    ) {}

    #[Route('/admin/antivirus-scans-data', name: 'admin_antivirus_scans_data', methods: ['GET'], priority: 10)]
    public function list(Request $request): JsonResponse
    {
        $page = min(100000, max(1, $request->query->getInt('page', 1)));
        $size = min(100, max(1, $request->query->getInt('size', 25)));
        $portalQuery = $this->portalQuery();
        $summary = ['clean' => 0, 'infected' => 0, 'error' => 0];
        foreach ((clone $portalQuery)->select('a.status, COUNT(a.id) AS total')->groupBy('a.status')->getQuery()->getArrayResult() as $row) {
            if (isset($summary[$row['status']])) {
                $summary[$row['status']] = (int) $row['total'];
            }
        }
        $query = $this->filter($portalQuery, $request);
        $status = $request->query->getString('status');
        $total = '' === $status ? array_sum($summary) : $summary[$status];
        $rows = $this->select($query)->setFirstResult(($page - 1) * $size)->setMaxResults($size)->getQuery()->getArrayResult();
        foreach ($rows as &$row) {
            $row['scannedAt'] = $row['scannedAt']->format('Y-m-d H:i:s');
        }
        unset($row);
        $response = $this->json(['items' => $rows, 'total' => $total, 'summary' => $summary]);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    #[Route('/admin/antivirus-scans-export', name: 'admin_antivirus_scans_export', methods: ['GET'], priority: 10)]
    public function export(Request $request): StreamedResponse
    {
        $query = $this->select($this->filter($this->portalQuery(), $request))->setMaxResults(10000)->getQuery();
        $response = new StreamedResponse(static function () use ($query): void {
            $output = fopen('php://output', 'wb');
            if (false === $output) {
                return;
            }

            try {
                fputcsv($output, ['id', 'filename', 'file_size', 'status', 'threat', 'user_id', 'course_id', 'session_id', 'source', 'scanned_at_utc', 'duration_ms', 'sha256'], ',', '"', '');
                foreach ($query->toIterable([], AbstractQuery::HYDRATE_ARRAY) as $row) {
                    $row['scannedAt'] = $row['scannedAt']->format('Y-m-d H:i:s');
                    fputcsv($output, CsvFormulaEscaper::escapeRow($row), ',', '"', '');
                }
            } finally {
                fclose($output);
            }
        });
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="antivirus-scans.csv"');
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    private function portalQuery(): QueryBuilder
    {
        // Resolve exactly the same portal as the upload guard.
        $this->settings->load('security', 'antivirus_upload_enabled');
        $portalId = $this->settings->getUrl()?->getId();
        if (null === $portalId) {
            throw $this->createAccessDeniedException();
        }

        return $this->em->createQueryBuilder()->from(AntivirusScan::class, 'a')
            ->where('a.accessUrlId = :portal')->setParameter('portal', $portalId)
        ;
    }

    private function filter(QueryBuilder $query, Request $request): QueryBuilder
    {
        $status = $request->query->getString('status');
        if ('' !== $status && !\in_array($status, ['clean', 'infected', 'error'], true)) {
            throw new BadRequestHttpException('Invalid scan status.');
        }
        if ('' !== $status) {
            $query->andWhere('a.status = :status')->setParameter('status', $status);
        }

        return $query;
    }

    private function select(QueryBuilder $query): QueryBuilder
    {
        return $query->select('a.id, a.filename, a.fileSize, a.status, a.threatName, a.userId, a.courseId, a.sessionId, a.source, a.scannedAt, a.durationMs, a.sha256')
            ->orderBy('a.id', 'DESC')
        ;
    }
}
