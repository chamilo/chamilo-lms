<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/usergroups/{id}/add-users-data', requirements: ['id' => '\d+'])]
class UsergroupAddUsersController extends AbstractController
{
    private const ALLOWED_RELATION_TYPES = [
        Usergroup::GROUP_USER_PERMISSION_ADMIN,
        Usergroup::GROUP_USER_PERMISSION_READER,
        Usergroup::GROUP_USER_PERMISSION_PENDING_INVITATION,
        Usergroup::GROUP_USER_PERMISSION_MODERATOR,
        Usergroup::GROUP_USER_PERMISSION_HRM,
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    #[Route('', name: 'admin_usergroup_add_users_data', methods: ['GET'])]
    public function data(Request $request, int $id): JsonResponse
    {
        $usergroup = $this->em->find(Usergroup::class, $id);
        if (null === $usergroup) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $isSocialGroup = Usergroup::SOCIAL_CLASS === $usergroup->getGroupType();
        $relationType = (int) $request->query->get('relation', Usergroup::GROUP_USER_PERMISSION_READER);
        if (!\in_array($relationType, self::ALLOWED_RELATION_TYPES, true)) {
            $relationType = Usergroup::GROUP_USER_PERMISSION_READER;
        }

        // Get users already in the group for this relation
        $membersQb = $this->em->createQueryBuilder()
            ->select('IDENTITY(ru.user) AS userId')
            ->from(UsergroupRelUser::class, 'ru')
            ->where('ru.usergroup = :ugId')
            ->setParameter('ugId', $id, Types::INTEGER)
        ;

        if ($isSocialGroup) {
            $membersQb->andWhere('ru.relationType = :rel')
                ->setParameter('rel', $relationType, Types::INTEGER)
            ;
        } else {
            $membersQb->andWhere('ru.relationType = :rel')
                ->setParameter('rel', Usergroup::GROUP_USER_PERMISSION_READER, Types::INTEGER)
            ;
        }

        $memberRows = $membersQb->getQuery()->getArrayResult();
        $memberIds = array_map(static fn (array $r): int => (int) $r['userId'], $memberRows);

        // Search filters
        $keyword = trim((string) $request->query->get('keyword', ''));
        $firstLetter = trim((string) $request->query->get('firstLetter', ''));

        $qb = $this->em->createQueryBuilder()
            ->select('u.id, u.firstname, u.lastname, u.username, u.officialCode')
            ->from(User::class, 'u')
            ->where('u.status != :anonymous')
            ->setParameter('anonymous', User::ANONYMOUS, Types::INTEGER)
            ->andWhere('u.active != :softDeleted')
            ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
        ;

        if ('' !== $keyword) {
            $qb->andWhere(
                '(u.firstname LIKE :kw OR u.lastname LIKE :kw OR u.username LIKE :kw OR u.email LIKE :kw OR u.officialCode LIKE :kw)'
            )
                ->setParameter('kw', '%'.$keyword.'%')
            ;
        }

        if ('' !== $firstLetter && '%' !== $firstLetter) {
            $qb->andWhere('u.lastname LIKE :fl')
                ->setParameter('fl', $firstLetter.'%')
            ;
        }

        $allUsers = $qb->getQuery()->getArrayResult();

        $usersInGroup = [];
        $usersNotInGroup = [];

        foreach ($allUsers as $user) {
            $userId = (int) $user['id'];
            $label = $user['lastname'].', '.$user['firstname'].' ('.$user['username'].')';
            if (!empty($user['officialCode'])) {
                $label .= ' - '.$user['officialCode'];
            }

            if (\in_array($userId, $memberIds, true)) {
                $usersInGroup[] = ['id' => $userId, 'label' => $label];
            } else {
                $usersNotInGroup[] = ['id' => $userId, 'label' => $label];
            }
        }

        return $this->json([
            'groupId' => $id,
            'groupTitle' => $usergroup->getTitle(),
            'isSocialGroup' => $isSocialGroup,
            'relationType' => $relationType,
            'usersInGroup' => $usersInGroup,
            'usersNotInGroup' => $usersNotInGroup,
            'csrfToken' => $this->csrfTokenManager->getToken('usergroup_add_users')->getValue(),
        ]);
    }

    #[Route('', name: 'admin_usergroup_add_users_save', methods: ['POST'])]
    public function save(Request $request, int $id): JsonResponse
    {
        $usergroup = $this->em->find(Usergroup::class, $id);
        if (null === $usergroup) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('usergroup_add_users', $token)) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $isSocialGroup = Usergroup::SOCIAL_CLASS === $usergroup->getGroupType();
        $relationType = (int) $request->request->get('relationType', Usergroup::GROUP_USER_PERMISSION_READER);

        if ($isSocialGroup) {
            if (!\in_array($relationType, self::ALLOWED_RELATION_TYPES, true)) {
                return $this->json(['error' => 'Invalid relation type'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            $relationType = Usergroup::GROUP_USER_PERMISSION_READER;
        }

        $rawIds = $request->request->all('userIds');
        $userIds = array_map('intval', (array) $rawIds);

        // Remove existing memberships for this group + relation type
        $this->em->createQueryBuilder()
            ->delete(UsergroupRelUser::class, 'ru')
            ->where('ru.usergroup = :ugId')
            ->andWhere('ru.relationType = :rel')
            ->setParameter('ugId', $id, Types::INTEGER)
            ->setParameter('rel', $relationType, Types::INTEGER)
            ->getQuery()
            ->execute()
        ;

        // Add new memberships
        foreach ($userIds as $userId) {
            $user = $this->em->find(User::class, $userId);
            if (null === $user) {
                continue;
            }

            $rel = new UsergroupRelUser();
            $rel->setUsergroup($usergroup);
            $rel->setUser($user);
            $rel->setRelationType($relationType);
            $this->em->persist($rel);
        }

        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/export', name: 'admin_usergroup_add_users_export', methods: ['GET'])]
    public function export(int $id): StreamedResponse
    {
        $usergroup = $this->em->find(Usergroup::class, $id);
        if (null === $usergroup) {
            return new StreamedResponse(static function (): void {}, Response::HTTP_NOT_FOUND);
        }

        $rows = $this->em->createQueryBuilder()
            ->select('u.username')
            ->from(UsergroupRelUser::class, 'ru')
            ->join('ru.user', 'u')
            ->where('ru.usergroup = :ugId')
            ->setParameter('ugId', $id, Types::INTEGER)
            ->orderBy('u.lastname', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $groupTitle = $usergroup->getTitle();
        $filename = 'export_user_class_'.date('Y-m-d_H-i-s').'.csv';

        $response = new StreamedResponse(static function () use ($rows, $groupTitle): void {
            $handle = fopen('php://output', 'w');
            if (false === $handle) {
                return;
            }
            fputcsv($handle, ['UserName', 'ClassName']);
            foreach ($rows as $row) {
                fputcsv($handle, [$row['username'], $groupTitle]);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}
