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
        $course_id = (int) $cid;
        $session_id = api_get_session_id();
        $group_id = api_get_group_id();

        $course_entity = $this->courseRepository->find($course_id);
        if (!$course_entity) {
            return new JsonResponse(['error' => 'Course not found'], 404);
        }

        $session_entity = api_get_session_entity();

        $total_quota_bytes = ($course_entity->getDiskQuota() * 1024 * 1024) ?? DEFAULT_DOCUMENT_QUOTA;
        $used_quota_bytes = $this->documentRepository->getTotalSpaceByCourse($course_entity);
        
        $chartData = [];

        // Process sessions
        $this->processCourseSessions($course_entity, $session_id, $total_quota_bytes, $used_quota_bytes, $chartData);

        // Process groups
        $this->processCourseGroups($course_entity, $group_id, $total_quota_bytes, $used_quota_bytes, $chartData);

        // Process user documents
        $users = $this->courseRepository->getUsersByCourse($course_entity);
        foreach ($users as $user) {
            $user_id = $user->getId();
            $user_name = $user->getFullName();
            $this->processUserDocuments($course_entity, $session_entity, $user_id, $user_name, $total_quota_bytes, $chartData);
        }

        // Add available space
        $available_bytes = $total_quota_bytes - $used_quota_bytes;
        $available_percentage = $this->calculatePercentage($available_bytes, $total_quota_bytes);
        
        $chartData[] = [
            'label' => addslashes(get_lang('Available space')) . ' (' . format_file_size($available_bytes) . ')',
            'percentage' => $available_percentage,
        ];

        return new JsonResponse([
            'datasets' => [
                ['data' => array_column($chartData, 'percentage')],
            ],
            'labels' => array_column($chartData, 'label'),
        ]);
    }

    private function processCourseSessions($course_entity, int $session_id, int $total_quota_bytes, int &$used_quota_bytes, array &$chartData): void
    {
        $sessions = $this->sessionRepository->getSessionsByCourse($course_entity);

        foreach ($sessions as $session) {
            $quota_bytes = $this->documentRepository->getTotalSpaceByCourse($course_entity, null, $session);
            
            if ($quota_bytes > 0) {
                $session_name = $session->getTitle();
                if ($session_id === $session->getId()) {
                    $session_name .= ' * ';
                }
                
                $used_quota_bytes += $quota_bytes;
                $chartData[] = [
                    'label' => addslashes(get_lang('Session') . ': ' . $session_name) . ' (' . format_file_size($quota_bytes) . ')',
                    'percentage' => $this->calculatePercentage($quota_bytes, $total_quota_bytes),
                ];
            }
        }
    }

    private function processCourseGroups($course_entity, int $group_id, int $total_quota_bytes, int &$used_quota_bytes, array &$chartData): void
    {
        $groups_list = $this->groupRepository->findAllByCourse($course_entity)->getQuery()->getResult();
        
        foreach ($groups_list as $group_entity) {
            $quota_bytes = $this->documentRepository->getTotalSpaceByCourse($course_entity, $group_entity->getIid());
            
            if ($quota_bytes > 0) {
                $group_name = $group_entity->getTitle();
                if ($group_id === $group_entity->getIid()) {
                    $group_name .= ' * ';
                }
                
                $used_quota_bytes += $quota_bytes;
                $chartData[] = [
                    'label' => addslashes(get_lang('Group') . ': ' . $group_name) . ' (' . format_file_size($quota_bytes) . ')',
                    'percentage' => $this->calculatePercentage($quota_bytes, $total_quota_bytes),
                ];
            }
        }
    }

    private function processUserDocuments($course_entity, $session_entity, int $user_id, string $user_name, int $total_quota_bytes, array &$chartData): void
    {
        $documents_list = $this->documentRepository->getAllDocumentDataByUserAndGroup($course_entity);
        $user_quota_bytes = 0;

        foreach ($documents_list as $document_entity) {
            if ($document_entity->getResourceNode()->getCreator()?->getId() === $user_id 
                && $document_entity->getFiletype() === 'file') {
                $resourceFiles = $document_entity->getResourceNode()->getResourceFiles();
                if (!$resourceFiles->isEmpty()) {
                    $user_quota_bytes += $resourceFiles->first()->getSize();
                }
            }
        }

        if ($user_quota_bytes > 0) {
            $chartData[] = [
                'label' => addslashes(get_lang('Teacher') . ': ' . $user_name) . ' (' . format_file_size($user_quota_bytes) . ')',
                'percentage' => $this->calculatePercentage($user_quota_bytes, $total_quota_bytes),
            ];

            // Handle session context
            if ($session_entity) {
                $session_total_quota = $this->calculateSessionTotalQuota($session_entity);
                if ($session_total_quota > 0) {
                    $chartData[] = [
                        'label' => addslashes(sprintf(get_lang('TeacherXInSession'), $user_name)),
                        'percentage' => $this->calculatePercentage($user_quota_bytes, $session_total_quota),
                    ];
                }
            }
        }
    }

    private function calculateSessionTotalQuota($session_entity): int
    {
        $total = 0;
        $sessionCourses = $session_entity->getCourses();
        
        foreach ($sessionCourses as $courseEntity) {
            $total += DocumentManager::get_course_quota($courseEntity->getId());
        }
        
        return $total;
    }

    private function calculatePercentage(int $bytes, int $total_bytes): float
    {
        if ($total_bytes === 0) {
            return 0.0;
        }
        
        return round(($bytes / $total_bytes) * 100, 2);
    }
}
