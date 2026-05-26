<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\User;
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
#[Route('/admin')]
class UsergroupImportController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    #[Route('/usergroup-import-data', name: 'admin_usergroup_import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('usergroup_import', $token)) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $file = $request->files->get('import_file');
        if (null === $file) {
            return $this->json(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $rows = $this->parseCsv($file->getPathname(), ['title', 'description', 'users']);

        $errors = [];
        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2;
            $title = trim($row['title'] ?? '');
            if ('' === $title) {
                $errors[] = ['line' => $lineNumber, 'error' => 'Title is required'];

                continue;
            }

            $existing = $this->em->getRepository(Usergroup::class)->findOneBy(['title' => $title]);
            if (null !== $existing) {
                $errors[] = ['line' => $lineNumber, 'error' => \sprintf('Group "%s" already exists', $title)];
            }
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $imported = 0;
        foreach ($rows as $row) {
            $title = trim($row['title'] ?? '');
            $description = trim($row['description'] ?? '');
            $usersStr = trim($row['users'] ?? '');

            $ug = new Usergroup();
            $ug->setTitle($title)
                ->setDescription($description)
                ->setGroupType(Usergroup::NORMAL_CLASS)
                ->setVisibility('1')
                ->setAllowMembersToLeaveGroup(0)
            ;

            $this->em->persist($ug);
            $this->em->flush();

            if ($this->accessUrlHelper->isMultiple()) {
                $accessUrl = $this->accessUrlHelper->getCurrent();
                if (null !== $accessUrl) {
                    $ug->addAccessUrl($accessUrl);
                    $this->em->flush();
                }
            }

            if ('' !== $usersStr) {
                $usernames = array_filter(array_map('trim', explode(',', $usersStr)));
                foreach ($usernames as $username) {
                    $user = $this->findUserByUsername($username);
                    if (null === $user) {
                        continue;
                    }

                    $rel = new UsergroupRelUser();
                    $rel->setUsergroup($ug);
                    $rel->setUser($user);
                    $rel->setRelationType(0);
                    $this->em->persist($rel);
                }
                $this->em->flush();
            }

            ++$imported;
        }

        return $this->json(['imported' => $imported]);
    }

    #[Route('/usergroup-user-import-data', name: 'admin_usergroup_user_import', methods: ['POST'])]
    public function userImport(Request $request): JsonResponse
    {
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('usergroup_import', $token)) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $file = $request->files->get('import_file');
        if (null === $file) {
            return $this->json(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $rows = $this->parseCsv($file->getPathname(), ['username', 'className']);

        $errors = [];
        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2;
            $username = trim($row['username'] ?? '');
            $className = trim($row['className'] ?? '');

            if ('' === $username || '' === $className) {
                $errors[] = ['line' => $lineNumber, 'error' => 'Both username and class name are required'];

                continue;
            }

            $user = $this->findUserByUsername($username);
            if (null === $user) {
                $errors[] = ['line' => $lineNumber, 'error' => \sprintf('User "%s" not found', $username)];
            }

            $usergroup = $this->em->getRepository(Usergroup::class)->findOneBy(['title' => $className]);
            if (null === $usergroup) {
                $errors[] = ['line' => $lineNumber, 'error' => \sprintf('Class "%s" not found', $className)];
            }
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $unsubscribe = '' !== trim((string) $request->request->get('unsubscribe', ''));

        $byClass = [];
        foreach ($rows as $row) {
            $className = trim($row['className'] ?? '');
            $username = trim($row['username'] ?? '');

            if (!isset($byClass[$className])) {
                $byClass[$className] = [];
            }
            $byClass[$className][] = $username;
        }

        $imported = 0;
        foreach ($byClass as $className => $usernames) {
            $usergroup = $this->em->getRepository(Usergroup::class)->findOneBy(['title' => $className]);
            if (null === $usergroup) {
                continue;
            }

            $ugId = (int) $usergroup->getId();

            $userIds = [];
            foreach ($usernames as $username) {
                $user = $this->findUserByUsername($username);
                if (null !== $user) {
                    $userIds[] = (int) $user->getId();
                }
            }

            if ($unsubscribe) {
                $this->em->createQueryBuilder()
                    ->delete(UsergroupRelUser::class, 'ru')
                    ->where('ru.usergroup = :ugId')
                    ->setParameter('ugId', $ugId, Types::INTEGER)
                    ->getQuery()
                    ->execute()
                ;
            }

            foreach ($userIds as $userId) {
                $existing = $this->em->createQueryBuilder()
                    ->select('COUNT(ru.id)')
                    ->from(UsergroupRelUser::class, 'ru')
                    ->where('ru.usergroup = :ugId')
                    ->andWhere('ru.user = :userId')
                    ->setParameter('ugId', $ugId, Types::INTEGER)
                    ->setParameter('userId', $userId, Types::INTEGER)
                    ->getQuery()
                    ->getSingleScalarResult()
                ;

                if ((int) $existing > 0) {
                    continue;
                }

                $user = $this->em->find(User::class, $userId);
                if (null === $user) {
                    continue;
                }

                $rel = new UsergroupRelUser();
                $rel->setUsergroup($usergroup);
                $rel->setUser($user);
                $rel->setRelationType(0);
                $this->em->persist($rel);
            }

            $this->em->flush();
            $imported += \count($usernames);
        }

        return $this->json(['imported' => $imported]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseCsv(string $path, array $keys): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        if (false === $handle) {
            return $rows;
        }

        $lineNumber = 0;
        while (false !== ($data = fgetcsv($handle))) {
            ++$lineNumber;
            if (1 === $lineNumber) {
                continue; // skip header
            }

            $row = [];
            foreach ($keys as $i => $key) {
                $row[$key] = $data[$i] ?? '';
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function findUserByUsername(string $username): ?User
    {
        return $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('LOWER(u.username) = LOWER(:username)')
            ->setParameter('username', $username)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
