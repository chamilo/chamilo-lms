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

use const PHP_INT_MAX;

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

        $courseQuotaMb = (int) $this->courseHelper->resolveCourseStorageQuotaMbForCourse($course);
        $docsQuotaMb = (int) $this->courseHelper->resolveDocumentsToolQuotaMbForCourse($course);

        $courseQuotaBytes = $courseQuotaMb > 0 ? $courseQuotaMb * 1024 * 1024 : 0;
        $docsQuotaBytes = $docsQuotaMb > 0 ? $docsQuotaMb * 1024 * 1024 : 0;

        $courseStorageUsedBytes = (int) $this->documentRepository->getCourseStorageUsedBytes($course);

        $usage = $this->documentRepository->getDocumentUsageBreakdownByCourse($course);
        $bytesCourse = (int) ($usage['course'] ?? 0);
        $bytesSessions = (int) ($usage['sessions'] ?? 0);
        $bytesGroups = (int) ($usage['groups'] ?? 0);

        $docsUsedBytes = $bytesCourse + $bytesSessions;
        $availableCourseBytes = $courseQuotaBytes > 0 ? max($courseQuotaBytes - $courseStorageUsedBytes, 0) : PHP_INT_MAX;
        $availableDocsBytes = $docsQuotaBytes > 0 ? max($docsQuotaBytes - $docsUsedBytes, 0) : PHP_INT_MAX;
        $availableBytes = min($availableCourseBytes, $availableDocsBytes);

        $limiterQuotaBytes = 0;
        $limiter = 'unlimited';

        if (PHP_INT_MAX !== $availableBytes) {
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

        $totalForChart = $limiterQuotaBytes > 0
            ? $limiterQuotaBytes
            : max($docsUsedBytes + max((int) $availableBytes, 0), 1);

        $availableBytesForChart = PHP_INT_MAX === $availableBytes ? 0 : (int) $availableBytes;

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
                'limiter' => $limiter,
                'courseQuotaBytes' => $courseQuotaBytes > 0 ? $courseQuotaBytes : null,
                'documentsQuotaBytes' => $docsQuotaBytes > 0 ? $docsQuotaBytes : null,
                'courseStorageUsedBytes' => $courseStorageUsedBytes,
                'documentsUsedBytes' => $docsUsedBytes,
                'availableCourseBytes' => PHP_INT_MAX === $availableCourseBytes ? null : (int) $availableCourseBytes,
                'availableDocumentsBytes' => PHP_INT_MAX === $availableDocsBytes ? null : (int) $availableDocsBytes,
                'availableBytes' => PHP_INT_MAX === $availableBytes ? null : (int) $availableBytes,
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
        $max = \count($units) - 1;

        while ($size >= 1024 && $i < $max) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2).' '.$units[$i];
    }
}
