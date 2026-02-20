<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsController]
final class DocumentUsageAction extends AbstractController
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly CDocumentRepository $documentRepository,
        private readonly CourseHelper $courseHelper,
        private readonly TranslatorInterface $translator,
    ) {}

    public function __invoke($cid): JsonResponse
    {
        $courseId = (int) $cid;
        $course = $this->courseRepository->find($courseId);

        if (null === $course) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }

        $courseQuotaMb = (int) $this->courseHelper->resolveCourseStorageQuotaMbForCourse($course); // 0 => unlimited
        $docsQuotaMb = (int) $this->courseHelper->resolveDocumentsToolQuotaMb(); // 0 => unlimited

        $courseQuotaBytes = $courseQuotaMb > 0 ? $courseQuotaMb * 1024 * 1024 : 0;
        $docsQuotaBytes = $docsQuotaMb > 0 ? $docsQuotaMb * 1024 * 1024 : 0;

        // Global course storage usage (all tools)
        $courseStorageUsedBytes = (int) $this->documentRepository->getCourseStorageUsedBytes($course);

        // Documents usage breakdown (deduplicated)
        $usage = $this->documentRepository->getDocumentUsageBreakdownByCourse($course);
        $bytesCourse = (int) ($usage['course'] ?? 0);
        $bytesSessions = (int) ($usage['sessions'] ?? 0);
        $bytesGroups = (int) ($usage['groups'] ?? 0);

        $docsUsedBytes = $bytesCourse + $bytesSessions;
        $availableCourseBytes = $courseQuotaBytes > 0 ? max($courseQuotaBytes - $courseStorageUsedBytes, 0) : PHP_INT_MAX;
        $availableDocsBytes = $docsQuotaBytes > 0 ? max($docsQuotaBytes - $docsUsedBytes, 0) : PHP_INT_MAX;
        $availableBytes = min($availableCourseBytes, $availableDocsBytes);

        // Determine which quota is limiting (for percent)
        $limiterQuotaBytes = 0;
        $limiter = 'unlimited';

        if ($availableBytes !== PHP_INT_MAX) {
            if ($availableCourseBytes <= $availableDocsBytes) {
                $limiter = 'course';
                $limiterQuotaBytes = $courseQuotaBytes;
            } else {
                $limiter = 'documents';
                $limiterQuotaBytes = $docsQuotaBytes;
            }
        }

        $availablePercent = 100.0;
        if ($limiterQuotaBytes > 0) {
            $availablePercent = round(($availableBytes / $limiterQuotaBytes) * 100, 4);
        }

        $totalForChart = ($limiterQuotaBytes > 0) ? $limiterQuotaBytes : max($docsUsedBytes + max((int) $availableBytes, 0), 1);
        $availableBytesForChart = ($availableBytes === PHP_INT_MAX) ? 0 : (int) $availableBytes;

        $labels = [];
        $data = [];

        if ($bytesCourse > 0) {
            $labels[] = $this->translator->trans('Course').' ('.$this->formatBytes($bytesCourse).')';
            $data[] = $this->pct($bytesCourse, $totalForChart);
        }

        if ($bytesSessions > 0) {
            $labels[] = $this->translator->trans('Session').' ('.$this->formatBytes($bytesSessions).')';
            $data[] = $this->pct($bytesSessions, $totalForChart);
        }

        if ($bytesGroups > 0) {
            $labels[] = $this->translator->trans('Group').' ('.$this->formatBytes($bytesGroups).')';
            $data[] = $this->pct($bytesGroups, $totalForChart);
        }

        $labels[] = sprintf(
            (string) $this->translator->trans('Available space (%s)'),
            $this->formatBytes($availableBytesForChart)
        );
        $data[] = $this->pct($availableBytesForChart, $totalForChart);

        return new JsonResponse([
            'datasets' => [
                ['data' => $data],
            ],
            'labels' => $labels,
            'quota' => [
                'limiter' => $limiter, // 'course' | 'documents' | 'unlimited'

                // Total quotas (null = unlimited)
                'courseQuotaBytes' => $courseQuotaBytes > 0 ? $courseQuotaBytes : null,
                'documentsQuotaBytes' => $docsQuotaBytes > 0 ? $docsQuotaBytes : null,

                // Used
                'courseStorageUsedBytes' => $courseStorageUsedBytes,
                'documentsUsedBytes' => $docsUsedBytes,

                // Remaining per quota (null = unlimited)
                'availableCourseBytes' => ($availableCourseBytes === PHP_INT_MAX) ? null : (int) $availableCourseBytes,
                'availableDocumentsBytes' => ($availableDocsBytes === PHP_INT_MAX) ? null : (int) $availableDocsBytes,

                // Remaining actually applicable to uploads (min(course, documents))
                'availableBytes' => ($availableBytes === PHP_INT_MAX) ? null : (int) $availableBytes,

                // Percent of the LIMITING quota (for your <1% warning)
                'availablePercent' => $availablePercent,
            ],
        ]);
    }

    private function pct(int $part, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($part / $total) * 100, 2);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) max($bytes, 0);
        $i = 0;

        $max = count($units) - 1;
        while ($size >= 1024 && $i < $max) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2).' '.$units[$i];
    }
}
