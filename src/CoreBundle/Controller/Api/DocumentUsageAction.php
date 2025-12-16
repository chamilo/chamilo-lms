<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;


#[AsController]
class DocumentUsageAction extends AbstractController
{

    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly CDocumentRepository $documentRepository,
        private readonly CGroupRepository $groupRepository,
    ) {
    }

    public function __invoke($cid): JsonResponse
    {
        $courseId = (int) $cid;
        
        $courseEntity = $this->courseRepository->find($courseId);
        if (!$courseEntity) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }

        $totalQuotaBytes = ($courseEntity->getDiskQuota() * 1024 * 1024) ?? DEFAULT_DOCUMENT_QUOTA;
        $usedQuotaBytes = $this->documentRepository->getTotalSpaceByCourse($courseEntity);

        $chartData = [];

        // Process sessions
        $this->processCourseSessions($courseEntity, $totalQuotaBytes, $usedQuotaBytes, $chartData);

        // Process groups
        $this->processCourseGroups($courseEntity, $totalQuotaBytes, $usedQuotaBytes, $chartData);

        // Process user documents
        $users = $this->courseRepository->getUsersByCourse($courseEntity);
        foreach ($users as $user) {
            $this->processUserDocuments($courseEntity, $user, $totalQuotaBytes, $chartData);
        }

        // Add available space
        $availableBytes = $totalQuotaBytes - $usedQuotaBytes;
        $availablePercentage = $this->calculatePercentage($availableBytes, $totalQuotaBytes);

        $chartData[] = [
            'label' => addslashes(get_lang('Available space')) . ' (' . format_file_size($availableBytes) . ')',
            'percentage' => $availablePercentage,
        ];

        return new JsonResponse([
            'datasets' => [
                ['data' => array_column($chartData, 'percentage')],
            ],
            'labels' => array_column($chartData, 'label'),
        ]);
    }

    private function processCourseSessions($courseEntity, int $totalQuotaBytes, int &$usedQuotaBytes, array &$chartData): void
    {
        foreach ($courseEntity->getSessions() as $sessionRel) {
            $session = $sessionRel->getSession();
            $quotaBytes = $this->documentRepository->getTotalSpaceByCourse($courseEntity, null, $session);

            if ($quotaBytes > 0) {
                $usedQuotaBytes += $quotaBytes;
                $chartData[] = [
                    'label' => addslashes(get_lang('Session') . ': ' . $session->getTitle()) . ' (' . format_file_size($quotaBytes) . ')',
                    'percentage' => $this->calculatePercentage($quotaBytes, $totalQuotaBytes),
                ];
            }
        }
    }

    private function processCourseGroups($courseEntity, int $totalQuotaBytes, int &$usedQuotaBytes, array &$chartData): void
    {
        $groupsList = $this->groupRepository->findAllByCourse($courseEntity)->getQuery()->getResult();
        
        foreach ($groupsList as $groupEntity) {
            $quotaBytes = $this->documentRepository->getTotalSpaceByCourse($courseEntity, $groupEntity->getIid());

            if ($quotaBytes > 0) {
                $usedQuotaBytes += $quotaBytes;
                $chartData[] = [
                    'label' => addslashes(get_lang('Group') . ': ' . $groupEntity->getTitle() . ' (' . format_file_size($quotaBytes) . ')'),
                    'percentage' => $this->calculatePercentage($quotaBytes, $totalQuotaBytes),
                ];
            }
        }
    }

    private function processUserDocuments($courseEntity, $user, int $totalQuotaBytes, array &$chartData): void
    {
        $documentsList = $this->documentRepository->getAllDocumentDataByUserAndGroup($courseEntity);
        $userQuotaBytes = 0;

        foreach ($documentsList as $documentEntity) {
            if ($documentEntity->getResourceNode()->getCreator()?->getId() === $user->getId()
                && $documentEntity->getFiletype() === 'file') {
                $resourceFiles = $documentEntity->getResourceNode()->getResourceFiles();
                if (!$resourceFiles->isEmpty()) {
                    $userQuotaBytes += $resourceFiles->first()->getSize();
                }
            }
        }

        if ($userQuotaBytes > 0) {
            $chartData[] = [
                'label' => addslashes(get_lang('Teacher') . ': ' . $user->getFullName()) . ' (' . format_file_size($userQuotaBytes) . ')',
                'percentage' => $this->calculatePercentage($userQuotaBytes, $totalQuotaBytes),
            ];
        }
    }

    private function calculatePercentage(int $bytes, int $totalBytes): float
    {
        if ($totalBytes === 0) {
            return 0.0;
        }

        return round(($bytes / $totalBytes) * 100, 2);
    }
}
