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
    private const BYTES_PER_MB = 1048576;

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

        /*
         * The course document quota is stored in course.disk_quota, in MB.
         * document.default_document_quotum is only used as the initial value when a course is created.
         */
        $quotaMb = (int) $this->courseHelper->resolveCourseStorageQuotaMbForCourse($course);
        $quotaBytes = $quotaMb > 0 ? $quotaMb * self::BYTES_PER_MB : 0;

        $usage = $this->documentRepository->getDocumentUsageBreakdownByCourse($course);
        $usedBytes = (int) ($usage['course'] ?? 0) + (int) ($usage['sessions'] ?? 0);

        $availableBytes = null;
        $availablePercent = 100.0;

        if ($quotaBytes > 0) {
            $availableBytes = max($quotaBytes - $usedBytes, 0);
            $availablePercent = round(($availableBytes / $quotaBytes) * 100, 4);
        }

        $labels = [];
        $data = [];

        if ($quotaBytes > 0) {
            $usedForChart = min($usedBytes, $quotaBytes);
            $availableForChart = max($quotaBytes - $usedForChart, 0);

            if ($usedForChart > 0) {
                $labels[] = (string) $this->translator->trans('Documents used');
                $data[] = $this->pct($usedForChart, $quotaBytes);
            }

            $labels[] = (string) $this->translator->trans('Available space');
            $data[] = $this->pct($availableForChart, $quotaBytes);
        } elseif ($usedBytes > 0) {
            $labels[] = (string) $this->translator->trans('Documents used');
            $data[] = 100.0;
        } else {
            $labels[] = (string) $this->translator->trans('No document usage');
            $data[] = 100.0;
        }

        return new JsonResponse([
            'datasets' => [
                ['data' => $data],
            ],
            'labels' => $labels,
            'quota' => [
                'limiter' => $quotaBytes > 0 ? 'course' : 'unlimited',
                'quotaBytes' => $quotaBytes > 0 ? $quotaBytes : null,
                'quotaMb' => $quotaMb > 0 ? $quotaMb : null,
                'usedBytes' => $usedBytes,
                'usedMb' => $this->bytesToMegabytes($usedBytes),
                'availableBytes' => null === $availableBytes ? null : (int) $availableBytes,
                'availableMb' => null === $availableBytes ? null : $this->bytesToMegabytes((int) $availableBytes),
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

    private function bytesToMegabytes(int $bytes): float
    {
        return round(max($bytes, 0) / self::BYTES_PER_MB, 2);
    }
}
