<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationFollow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CStudentPublicationFollow>
 */
class CStudentPublicationFollowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CStudentPublicationFollow::class);
    }

    public function isFollowing(CStudentPublication $publication, User $user): bool
    {
        return null !== $this->findOneBy([
            'publication' => (int) $publication->getIid(),
            'user' => (int) $user->getId(),
        ]);
    }

    /**
     * Idempotent: following an assignment twice keeps a single row.
     */
    public function follow(CStudentPublication $publication, User $user): void
    {
        if ($this->isFollowing($publication, $user)) {
            return;
        }

        $em = $this->getEntityManager();
        $em->persist(new CStudentPublicationFollow($publication, $user));
        $em->flush();
    }

    public function unfollow(CStudentPublication $publication, User $user): void
    {
        $this->createQueryBuilder('f')
            ->delete()
            ->where('f.publication = :publication')
            ->andWhere('f.user = :user')
            ->setParameter('publication', (int) $publication->getIid())
            ->setParameter('user', (int) $user->getId())
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @return list<User>
     */
    public function findFollowers(CStudentPublication $publication): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->innerJoin(CStudentPublicationFollow::class, 'f', 'WITH', 'f.user = u')
            ->where('f.publication = :publication')
            ->setParameter('publication', (int) $publication->getIid())
            ->orderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
