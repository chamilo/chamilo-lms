<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsController]
final class DocumentUsageAction extends AbstractController
{
    /**
     * Fallback quota (MB) when the course quota is empty or not set.
     */
    private const DEFAULT_QUOTA_MB = 100;

    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly CDocumentRepository $documentRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    public function __invoke($cid): JsonResponse
    {
        $courseId = (int) $cid;

        $course = $this->courseRepository->find($courseId);
        if (null === $course) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }

        // Resolve quota in MB safely (avoid "($x * ...) ?? fallback").
        $quotaMb = (int) ($course->getDiskQuota() ?? 0);
        if ($quotaMb <= 0) {
            $quotaMb = self::DEFAULT_QUOTA_MB;
        }

        $totalQuotaBytes = $quotaMb * 1024 * 1024;

        // Compute usage using repository logic (deduplicated).
        $usage = $this->documentRepository->getDocumentUsageBreakdownByCourse($course);

        $bytesCourse = (int) ($usage['course'] ?? 0);
        $bytesSessions = (int) ($usage['sessions'] ?? 0);
        $bytesGroups = (int) ($usage['groups'] ?? 0);

        $usedBytes = (int) ($usage['used'] ?? ($bytesCourse + $bytesSessions + $bytesGroups));

        // Keep the pie meaningful even when used > quota.
        $denomBytes = max($totalQuotaBytes, $usedBytes, 1);

        $availableBytes = max($totalQuotaBytes - $usedBytes, 0);

        $labels = [];
        $data = [];

        if ($bytesCourse > 0) {
            $labels[] = $this->translator->trans('Course').' ('.$this->formatBytes($bytesCourse).')';
            $data[] = $this->pct($bytesCourse, $denomBytes);
        }

        if ($bytesSessions > 0) {
            $labels[] = $this->translator->trans('Session').' ('.$this->formatBytes($bytesSessions).')';
            $data[] = $this->pct($bytesSessions, $denomBytes);
        }

        if ($bytesGroups > 0) {
            $labels[] = $this->translator->trans('Group').' ('.$this->formatBytes($bytesGroups).')';
            $data[] = $this->pct($bytesGroups, $denomBytes);
        }

        $labels[] = \sprintf(
            $this->translator->trans('Available space (%s)'),
            $this->formatBytes($availableBytes)
        );
        $data[] = $this->pct($availableBytes, $denomBytes);

        return new JsonResponse([
            'datasets' => [
                ['data' => $data],
            ],
            'labels' => $labels,
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
        // Simple dependency-free formatter for API responses.
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
