<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UrlManager;

use const PHP_SESSION_ACTIVE;

/**
 * Data source for assigning courses to access URLs, replacing the legacy
 * access_url_edit_courses_to_url.php / access_url_add_courses_to_url.php pair.
 *
 * CSRF is handled globally by CsrfProtectionListener (stateless, origin-based)
 * for every state-changing request the router resolves — no per-endpoint
 * token is generated or checked here.
 */
#[IsGranted('ROLE_GLOBAL_ADMIN')]
#[Route('/admin/access-urls-courses-data')]
class AccessUrlCoursesController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'admin_access_urls_courses_data', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $accessUrlId = (int) $request->query->get('access_url_id', 0);

        $urls = $this->em->createQueryBuilder()
            ->select('a.id AS id, a.url AS url')
            ->from(AccessUrl::class, 'a')
            ->where('a.active = 1')
            ->orderBy('a.url', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $assignedIds = $this->getAssignedCourseIds($accessUrlId);

        $courses = $this->em->createQueryBuilder()
            ->select('c.id AS id, c.title AS title, c.code AS code')
            ->from(Course::class, 'c')
            ->orderBy('c.title', 'ASC')
            ->addOrderBy('c.code', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $assigned = [];
        $available = [];
        foreach ($courses as $c) {
            $id = (int) $c['id'];
            $item = ['id' => $id, 'title' => $c['title'], 'code' => $c['code']];
            if (\in_array($id, $assignedIds, true)) {
                $assigned[] = $item;
            } else {
                $available[] = $item;
            }
        }

        return $this->json([
            'urls' => array_map(
                static fn (array $u): array => ['id' => (int) $u['id'], 'url' => $u['url']],
                $urls
            ),
            'assigned' => $assigned,
            'available' => $available,
        ]);
    }

    #[Route('', name: 'admin_access_urls_courses_assign', methods: ['POST'])]
    public function assign(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $accessUrlId = (int) ($data['access_url_id'] ?? 0);
        if ($accessUrlId <= 0) {
            return $this->json(['error' => 'Select a URL'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $courseIds = array_map('intval', $data['course_ids'] ?? []);

        UrlManager::update_urls_rel_course($courseIds, $accessUrlId);

        return $this->json(['success' => true]);
    }

    #[Route('/bulk', name: 'admin_access_urls_courses_bulk', methods: ['POST'])]
    public function bulk(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $courseIds = array_map('intval', $data['course_ids'] ?? []);
        $urlIds = array_map('intval', $data['url_ids'] ?? []);
        $action = (string) ($data['action'] ?? '');

        if ([] === $courseIds || [] === $urlIds || !\in_array($action, ['add', 'remove'], true)) {
            return $this->json(['error' => 'You must select at least one course and one URL'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $codes = $this->em->createQueryBuilder()
            ->select('c.code AS code')
            ->from(Course::class, 'c')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $courseIds)
            ->getQuery()
            ->getSingleColumnResult()
        ;

        if ('add' === $action) {
            UrlManager::add_courses_to_urls($codes, $urlIds);
        } else {
            UrlManager::remove_courses_from_urls($codes, $urlIds);
        }

        return $this->json(['success' => true]);
    }

    /**
     * @return int[]
     */
    private function getAssignedCourseIds(int $accessUrlId): array
    {
        if ($accessUrlId <= 0) {
            return [];
        }

        $rows = $this->em->createQueryBuilder()
            ->select('IDENTITY(r.course) AS courseId')
            ->from(AccessUrlRelCourse::class, 'r')
            ->where('r.url = :urlId')
            ->setParameter('urlId', $accessUrlId)
            ->getQuery()
            ->getArrayResult()
        ;

        return array_map(static fn (array $row): int => (int) $row['courseId'], $rows);
    }
}
