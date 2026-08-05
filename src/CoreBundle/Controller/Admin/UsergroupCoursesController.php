<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelCourse;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/usergroup-courses-data')]
class UsergroupCoursesController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    #[Route('/{id}', name: 'admin_usergroup_courses_data', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function data(int $id): JsonResponse
    {
        $usergroup = $this->em->find(Usergroup::class, $id);
        if (null === $usergroup) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->belongsToCurrentUrl($usergroup)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $subscribedIds = $this->em->createQueryBuilder()
            ->select('IDENTITY(rc.course) AS cId')
            ->from(UsergroupRelCourse::class, 'rc')
            ->where('rc.usergroup = :ugId')
            ->setParameter('ugId', $id, Types::INTEGER)
            ->getQuery()
            ->getSingleColumnResult()
        ;
        $subscribedIds = array_map('intval', $subscribedIds);

        $qb = $this->em->createQueryBuilder()
            ->select('c.id, c.title, c.code, c.visualCode')
            ->from(Course::class, 'c')
        ;

        if ($this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();
            if (null !== $accessUrl) {
                $qb->join('c.urls', 'urlRel')
                    ->andWhere('urlRel.url = :urlId')
                    ->setParameter('urlId', $accessUrl->getId(), Types::INTEGER)
                ;
            }
        }

        $allCourses = $qb
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $inGroup = [];
        $notInGroup = [];

        foreach ($allCourses as $course) {
            $item = [
                'id' => $course['id'],
                'label' => $course['title'].' ('.$course['visualCode'].')',
            ];

            if (\in_array((int) $course['id'], $subscribedIds, true)) {
                $inGroup[] = $item;
            } else {
                $notInGroup[] = $item;
            }
        }

        return $this->json([
            'groupId' => $id,
            'groupTitle' => $usergroup->getTitle(),
            'coursesInGroup' => $inGroup,
            'coursesNotInGroup' => $notInGroup,
            'csrfToken' => $this->csrfTokenManager->getToken('usergroup_courses')->getValue(),
        ]);
    }

    #[Route('/{id}', name: 'admin_usergroup_courses_save', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function save(Request $request, int $id): JsonResponse
    {
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('usergroup_courses', $token)) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $usergroup = $this->em->find(Usergroup::class, $id);
        if (null === $usergroup) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->belongsToCurrentUrl($usergroup)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $courseIds = array_map('intval', (array) $request->request->all('courseIds'));

        $this->em->createQueryBuilder()
            ->delete(UsergroupRelCourse::class, 'rc')
            ->where('rc.usergroup = :ugId')
            ->setParameter('ugId', $id, Types::INTEGER)
            ->getQuery()
            ->execute()
        ;

        foreach ($courseIds as $courseId) {
            $course = $this->em->find(Course::class, $courseId);
            if (null === $course) {
                continue;
            }

            $rel = new UsergroupRelCourse();
            $rel->setUsergroup($usergroup);
            $rel->setCourse($course);
            $this->em->persist($rel);
        }

        $this->em->flush();

        return $this->json(['success' => true]);
    }

    private function belongsToCurrentUrl(Usergroup $usergroup): bool
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return true;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            return true;
        }

        $urlId = $accessUrl->getId();
        foreach ($usergroup->getUrls() as $urlRel) {
            $relUrl = $urlRel->getUrl();
            if (null !== $relUrl && $relUrl->getId() === $urlId) {
                return true;
            }
        }

        return false;
    }
}
