<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
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

    private const ROLE_LABELS = [
        'ROLE_STUDENT' => 'Learner',
        'ROLE_TEACHER' => 'Teacher',
        'ROLE_HR' => 'Human Resources Manager',
        'ROLE_SESSION_MANAGER' => 'Session administrator',
        'ROLE_STUDENT_BOSS' => 'Superior (n+1)',
        'ROLE_INVITEE' => 'Invitee',
        'ROLE_QUESTION_MANAGER' => 'Question manager',
        'ROLE_ADMIN' => 'Administrator',
        'ROLE_PLATFORM_ADMIN' => 'Administrator',
        'ROLE_GLOBAL_ADMIN' => 'Global Administrator',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
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
        $keywordRoles = $request->query->all('keyword_roles');
        $keywordActive = $request->query->get('keyword_active');
        $keywordInactive = $request->query->get('keyword_inactive');
        $classId = (int) $request->query->get('class_id', 0);

        $dqlSortField = self::ALLOWED_SORT_FIELDS[$sortField] ?? 'u.lastname';
        $showDeleted = 'deleted' === $view;

        $qb = $this->em->createQueryBuilder()
            ->from(User::class, 'u')
            ->andWhere('u.status != :fallback')
            ->setParameter('fallback', User::ROLE_FALLBACK)
        ;

        if ($showDeleted) {
            $qb->andWhere('u.active = :softDeleted')
                ->setParameter('softDeleted', User::SOFT_DELETED)
            ;
        } else {
            $qb->andWhere('u.active != :softDeleted')
                ->setParameter('softDeleted', User::SOFT_DELETED)
            ;
        }

        if ($classId > 0) {
            $qb->join('u.classes', 'ug')
                ->andWhere('ug.usergroup = :classId')
                ->setParameter('classId', $classId)
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

            if (!empty($keywordRoles)) {
                $adminVariants = ['ROLE_PLATFORM_ADMIN', 'PLATFORM_ADMIN', 'ROLE_GLOBAL_ADMIN', 'GLOBAL_ADMIN', 'ROLE_ADMIN', 'ADMIN'];
                $roleConds = [];
                $i = 0;
                foreach ($keywordRoles as $role) {
                    $role = strtoupper(trim($role));
                    if ('' === $role) {
                        continue;
                    }
                    // Map admin variants to actual role names stored in the JSON column
                    if (\in_array($role, $adminVariants, true)) {
                        $paramA = 'role'.$i++;
                        $paramB = 'role'.$i++;
                        $roleConds[] = "(u.roles LIKE :{$paramA} OR u.roles LIKE :{$paramB})";
                        $qb->setParameter($paramA, '%"ROLE_ADMIN"%');
                        $qb->setParameter($paramB, '%"ROLE_GLOBAL_ADMIN"%');
                    } else {
                        $paramName = 'role'.$i++;
                        $roleConds[] = "u.roles LIKE :{$paramName}";
                        $qb->setParameter($paramName, '%"'.$role.'"%');
                    }
                }
                if (!empty($roleConds)) {
                    $qb->andWhere('('.implode(' OR ', $roleConds).')');
                }
            }

            if ('1' === $keywordActive && '1' !== $keywordInactive) {
                $qb->andWhere('u.active = 1');
            } elseif ('1' === $keywordInactive && '1' !== $keywordActive) {
                $qb->andWhere('u.active = 0');
            }
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        $rows = (clone $qb)
            ->select('u.id, u.officialCode, u.firstname, u.lastname, u.username, u.email, u.active, u.createdAt, u.lastLogin, u.expirationDate, u.roles, u.status')
            ->orderBy($dqlSortField, $sortOrder)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult()
        ;

        $currentUser = $this->getUser();
        $currentUserId = $currentUser ? $currentUser->getId() : 0;
        $isPlatformAdmin = $this->isGranted('ROLE_ADMIN');
        $isSessionAdmin = $this->isGranted('ROLE_SESSION_MANAGER') && !$isPlatformAdmin;

        $adminTable = $this->em->getConnection()->createQueryBuilder()
            ->select('user_id')
            ->from('admin')
            ->executeQuery()
            ->fetchFirstColumn()
        ;
        $adminIds = array_map('intval', $adminTable);

        $items = [];
        $now = new DateTime();

        foreach ($rows as $row) {
            $userId = (int) $row['id'];
            $allRoles = (array) $row['roles'];
            $filteredRoles = array_values(array_filter(
                $allRoles,
                static fn (string $r): bool => !\in_array(strtoupper($r), ['ROLE_USER', 'USER', 'ROLE_ANONYMOUS', 'ANONYMOUS'], true)
            ));

            $isAnonymous = \in_array('ROLE_ANONYMOUS', $allRoles, true);
            $isUserAdmin = \in_array('ROLE_PLATFORM_ADMIN', $allRoles, true)
                || \in_array('ROLE_GLOBAL_ADMIN', $allRoles, true)
                || \in_array('ROLE_ADMIN', $allRoles, true)
                || \in_array($userId, $adminIds, true);
            $isStudent = \in_array('ROLE_STUDENT', $allRoles, true);
            $isSessionManager = \in_array('ROLE_SESSION_MANAGER', $allRoles, true);
            $isHR = \in_array('ROLE_HR', $allRoles, true);
            $isStudentBoss = \in_array('ROLE_STUDENT_BOSS', $allRoles, true);

            $activeValue = (int) $row['active'];
            $expirationDate = $row['expirationDate'];
            if (1 === $activeValue && $expirationDate instanceof DateTime && $expirationDate < $now) {
                $activeValue = User::INACTIVE_AUTOMATIC;
            }

            $items[] = [
                'id' => $userId,
                'officialCode' => $row['officialCode'] ?? '',
                'firstname' => $row['firstname'] ?? '',
                'lastname' => $row['lastname'] ?? '',
                'username' => $row['username'] ?? '',
                'email' => $row['email'] ?? '',
                'active' => $activeValue,
                'roles' => $filteredRoles,
                'isAdmin' => $isUserAdmin,
                'isAnonymous' => $isAnonymous,
                'isStudent' => $isStudent,
                'isSessionManager' => $isSessionManager,
                'isHR' => $isHR,
                'isStudentBoss' => $isStudentBoss,
                'createdAt' => $row['createdAt'] ? $row['createdAt']->format('Y-m-d H:i') : null,
                'lastLogin' => $row['lastLogin'] ? $row['lastLogin']->format('Y-m-d H:i') : null,
                'avatarUrl' => $this->userRepository->getUserPicture($userId, UserRepository::USER_IMAGE_SIZE_SMALL),
            ];
        }

        return $this->json([
            'items' => $items,
            'total' => $total,
            'viewer' => [
                'id' => $currentUserId,
                'isPlatformAdmin' => $isPlatformAdmin,
                'isSessionAdmin' => $isSessionAdmin,
            ],
            'roleLabels' => self::ROLE_LABELS,
            'csrfToken' => $this->csrfTokenManager->getToken('user_list_action')->getValue(),
            'loginAsToken' => $this->csrfTokenManager->getToken('login_as')->getValue(),
        ]);
    }
}
