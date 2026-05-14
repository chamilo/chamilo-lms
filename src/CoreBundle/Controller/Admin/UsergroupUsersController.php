<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
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
#[Route('/admin/usergroup-users-data')]
class UsergroupUsersController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    #[Route('/{id}', name: 'admin_usergroup_users_data', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function data(int $id): JsonResponse
    {
        $usergroup = $this->em->find(Usergroup::class, $id);
        if (null === $usergroup) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->belongsToCurrentUrl($usergroup)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $this->em->createQueryBuilder()
            ->select('u.id, u.firstname, u.lastname, u.username, ru.relationType')
            ->from(UsergroupRelUser::class, 'ru')
            ->join('ru.user', 'u')
            ->where('ru.usergroup = :ugId')
            ->setParameter('ugId', $id, Types::INTEGER)
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $users = array_map(
            static fn (array $row): array => [
                'id' => $row['id'],
                'name' => $row['lastname'].', '.$row['firstname'].' ('.$row['username'].')',
                'relationType' => $row['relationType'],
            ],
            $rows
        );

        return $this->json([
            'groupId' => $id,
            'groupTitle' => $usergroup->getTitle(),
            'users' => $users,
            'csrfToken' => $this->csrfTokenManager->getToken('usergroup_users')->getValue(),
        ]);
    }

    #[Route('/{id}/user/{userId}', name: 'admin_usergroup_users_remove', requirements: ['id' => '\d+', 'userId' => '\d+'], methods: ['DELETE'])]
    public function remove(Request $request, int $id, int $userId): JsonResponse
    {
        $token = (string) $request->headers->get('X-CSRF-Token', '');
        if (!$this->isCsrfTokenValid('usergroup_users', $token)) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $usergroup = $this->em->find(Usergroup::class, $id);
        if (null === $usergroup) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->belongsToCurrentUrl($usergroup)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->em->createQueryBuilder()
            ->delete(UsergroupRelUser::class, 'ru')
            ->where('ru.usergroup = :ugId')
            ->andWhere('ru.user = :userId')
            ->setParameter('ugId', $id, Types::INTEGER)
            ->setParameter('userId', $userId, Types::INTEGER)
            ->getQuery()
            ->execute()
        ;

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
