<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
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
        private readonly SessionRepository $sessionRepository,
    ) {
    }

    public function __invoke($cid): JsonResponse
    {
        $courseId = (int) $cid;
        $sessionId = api_get_session_id();
        $groupId = api_get_group_id();

        $courseEntity = $this->courseRepository->find($courseId);
        if (!$courseEntity) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }

        $sessionEntity = api_get_session_entity();

        $totalQuotaBytes = ($courseEntity->getDiskQuota() * 1024 * 1024) ?? DEFAULT_DOCUMENT_QUOTA;
        $usedQuotaBytes = $this->documentRepository->getTotalSpaceByCourse($courseEntity);
        
        $chartData = [];

        // Process sessions
        $this->processCourseSessions($courseEntity, $sessionId, $totalQuotaBytes, $usedQuotaBytes, $chartData);

        // Process groups
        $this->processCourseGroups($courseEntity, $groupId, $totalQuotaBytes, $usedQuotaBytes, $chartData);

        // Process user documents
        $users = $this->courseRepository->getUsersByCourse($courseEntity);
        foreach ($users as $user) {
            $userId = $user->getId();
            $userName = $user->getFullName();
            $this->processUserDocuments($courseEntity, $sessionEntity, $userId, $userName, $totalQuotaBytes, $chartData);
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

    private function processCourseSessions($courseEntity, int $sessionId, int $totalQuotaBytes, int &$usedQuotaBytes, array &$chartData): void
    {
        $sessions = $this->sessionRepository->getSessionsByCourse($courseEntity);

        foreach ($sessions as $session) {
            $quotaBytes = $this->documentRepository->getTotalSpaceByCourse($courseEntity, null, $session);
            
            if ($quotaBytes > 0) {
                $sessionName = $session->getTitle();
                if ($sessionId === $session->getId()) {
                    $sessionName .= ' * ';
                }
                
                $usedQuotaBytes += $quotaBytes;
                $chartData[] = [
                    'label' => addslashes(get_lang('Session') . ': ' . $sessionName) . ' (' . format_file_size($quotaBytes) . ')',
                    'percentage' => $this->calculatePercentage($quotaBytes, $totalQuotaBytes),
                ];
            }
        }
    }

    private function processCourseGroups($courseEntity, int $groupId, int $totalQuotaBytes, int &$usedQuotaBytes, array &$chartData): void
    {
        $groupsList = $this->groupRepository->findAllByCourse($courseEntity)->getQuery()->getResult();
        
        foreach ($groupsList as $groupEntity) {
            $quotaBytes = $this->documentRepository->getTotalSpaceByCourse($courseEntity, $groupEntity->getIid());
            
            if ($quotaBytes > 0) {
                $groupName = $groupEntity->getTitle();
                if ($groupId === $groupEntity->getIid()) {
                    $groupName .= ' * ';
                }
                
                $usedQuotaBytes += $quotaBytes;
                $chartData[] = [
                    'label' => addslashes(get_lang('Group') . ': ' . $groupName) . ' (' . format_file_size($quotaBytes) . ')',
                    'percentage' => $this->calculatePercentage($quotaBytes, $totalQuotaBytes),
                ];
            }
        }
    }

    private function processUserDocuments($courseEntity, $sessionEntity, int $userId, string $userName, int $totalQuotaBytes, array &$chartData): void
    {
        $documentsList = $this->documentRepository->getAllDocumentDataByUserAndGroup($courseEntity);
        $userQuotaBytes = 0;

        foreach ($documentsList as $documentEntity) {
            if ($documentEntity->getResourceNode()->getCreator()?->getId() === $userId 
                && $documentEntity->getFiletype() === 'file') {
                $resourceFiles = $documentEntity->getResourceNode()->getResourceFiles();
                if (!$resourceFiles->isEmpty()) {
                    $userQuotaBytes += $resourceFiles->first()->getSize();
                }
            }
        }

        if ($userQuotaBytes > 0) {
            $chartData[] = [
                'label' => addslashes(get_lang('Teacher') . ': ' . $userName) . ' (' . format_file_size($userQuotaBytes) . ')',
                'percentage' => $this->calculatePercentage($userQuotaBytes, $totalQuotaBytes),
            ];

            // Handle session context
            if ($sessionEntity) {
                $sessionTotalQuota = $this->calculateSessionTotalQuota($sessionEntity);
                if ($sessionTotalQuota > 0) {
                    $chartData[] = [
                        'label' => addslashes(sprintf(get_lang('TeacherXInSession'), $userName)),
                        'percentage' => $this->calculatePercentage($userQuotaBytes, $sessionTotalQuota),
                    ];
                }
            }
        }
    }

    private function calculateSessionTotalQuota($sessionEntity): int
    {
        $total = 0;
        $sessionCourses = $sessionEntity->getCourses();
        
        foreach ($sessionCourses as $courseEntity) {
            $total += DocumentManager::get_course_quota($courseEntity->getId());
        }
        
        return $total;
    }

    private function calculatePercentage(int $bytes, int $totalBytes): float
    {
        if ($totalBytes === 0) {
            return 0.0;
        }
        
        return round(($bytes / $totalBytes) * 100, 2);
    }
}
