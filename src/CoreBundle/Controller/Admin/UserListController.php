<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_SESSION_MANAGER")'))]
#[Route('/admin/user-list-data')]
class UserListController extends AbstractController
{
    private const ALLOWED_SORT_FIELDS = [
        'officialCode' => 'u.officialCode',
        'firstname' => 'u.firstname',
        'lastname' => 'u.lastname',
        'username' => 'u.username',
        'email' => 'u.email',
        'active' => 'u.active',
        'createdAt' => 'u.createdAt',
        'lastLogin' => 'u.lastLogin',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
    ) {}

    #[Route('', name: 'admin_user_list_data', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(200, (int) $request->query->get('limit', 20)));
        $sortField = (string) $request->query->get('sortField', 'lastname');
        $sortOrder = 'DESC' === strtoupper((string) $request->query->get('sortOrder', 'ASC')) ? 'DESC' : 'ASC';
        $view = (string) $request->query->get('view', 'all');
        $keyword = trim((string) $request->query->get('keyword', ''));
        $keywordFirstname = trim((string) $request->query->get('keyword_firstname', ''));
        $keywordLastname = trim((string) $request->query->get('keyword_lastname', ''));
        $keywordUsername = trim((string) $request->query->get('keyword_username', ''));
        $keywordEmail = trim((string) $request->query->get('keyword_email', ''));
        $keywordOfficialCode = trim((string) $request->query->get('keyword_officialcode', ''));

        $dqlSortField = self::ALLOWED_SORT_FIELDS[$sortField] ?? 'u.lastname';
        $showDeleted = 'deleted' === $view;

        $qb = $this->em->createQueryBuilder()
            ->from(User::class, 'u')
            ->andWhere('u.status != :fallback')
            ->setParameter('fallback', User::ROLE_FALLBACK)
        ;

        if ($showDeleted) {
            $qb->andWhere('u.active = :activeStatus')
                ->setParameter('activeStatus', User::SOFT_DELETED)
            ;
        } else {
            $qb->andWhere('u.active != :activeStatus')
                ->setParameter('activeStatus', User::SOFT_DELETED)
            ;
        }

        if ('' !== $keyword) {
            $qb->andWhere('(u.firstname LIKE :kw OR u.lastname LIKE :kw OR u.username LIKE :kw OR u.email LIKE :kw OR u.officialCode LIKE :kw)')
                ->setParameter('kw', '%'.$keyword.'%')
            ;
        } else {
            if ('' !== $keywordFirstname) {
                $qb->andWhere('u.firstname LIKE :kwfn')->setParameter('kwfn', '%'.$keywordFirstname.'%');
            }
            if ('' !== $keywordLastname) {
                $qb->andWhere('u.lastname LIKE :kwln')->setParameter('kwln', '%'.$keywordLastname.'%');
            }
            if ('' !== $keywordUsername) {
                $qb->andWhere('u.username LIKE :kwun')->setParameter('kwun', '%'.$keywordUsername.'%');
            }
            if ('' !== $keywordEmail) {
                $qb->andWhere('u.email LIKE :kwem')->setParameter('kwem', '%'.$keywordEmail.'%');
            }
            if ('' !== $keywordOfficialCode) {
                $qb->andWhere('u.officialCode LIKE :kwoc')->setParameter('kwoc', '%'.$keywordOfficialCode.'%');
            }
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        $rows = (clone $qb)
            ->select('u.id, u.officialCode, u.firstname, u.lastname, u.username, u.email, u.active, u.createdAt, u.lastLogin, u.roles, u.status')
            ->orderBy($dqlSortField, $sortOrder)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult()
        ;

        $items = [];
        foreach ($rows as $row) {
            $userId = (int) $row['id'];
            $roles = array_values(array_filter(
                (array) $row['roles'],
                static fn (string $r): bool => !in_array(strtoupper($r), ['ROLE_USER', 'USER', 'ROLE_ANONYMOUS', 'ANONYMOUS'], true)
            ));

            $items[] = [
                'id' => $userId,
                'officialCode' => $row['officialCode'] ?? '',
                'firstname' => $row['firstname'] ?? '',
                'lastname' => $row['lastname'] ?? '',
                'username' => $row['username'] ?? '',
                'email' => $row['email'] ?? '',
                'active' => (int) $row['active'],
                'roles' => $roles,
                'createdAt' => $row['createdAt'] ? $row['createdAt']->format('Y-m-d H:i') : null,
                'lastLogin' => $row['lastLogin'] ? $row['lastLogin']->format('Y-m-d H:i') : null,
                'avatarUrl' => $this->userRepository->getUserPicture($userId, UserRepository::USER_IMAGE_SIZE_SMALL),
            ];
        }

        return $this->json(['items' => $items, 'total' => $total]);
    }
}
