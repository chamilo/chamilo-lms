<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Chamilo\CoreBundle\Entity\ResourceFile;

class ResourceFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResourceFile::class);
    }

    public function searchFiles(string $search, int $offset, int $limit): array
    {
        $uuid = '';
        if (preg_match('/[a-f0-9]{32}|[a-f0-9\-]{36}/i', $search, $matches)) {
            $uuid = strtoupper(str_replace('-', '', $matches[0]));
        }

        $uuidBinary = pack('H*', $uuid);

        $queryBuilder = $this->createQueryBuilder('rf')
            ->leftJoin('rf.resourceNode', 'rn')
            ->leftJoin('rn.resourceLinks', 'rl')
            ->leftJoin('rl.course', 'c')
            ->leftJoin('rl.user', 'u')
            ->addSelect('rn', 'rl', 'c', 'u');

        if ($search) {
            $queryBuilder->where('rf.title LIKE :search')
                ->orWhere('rf.originalName LIKE :search')
                ->orWhere('c.title LIKE :search')
                ->orWhere('u.username LIKE :search')
                ->orWhere('rn.uuid = :uuid')
                ->setParameter('search', '%' . $search . '%')
                ->setParameter('uuid', $uuidBinary);
        }

        $queryBuilder->orderBy('rf.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    public function countFiles(string $search): int
    {
        $uuid = '';
        if (preg_match('/[a-f0-9]{32}|[a-f0-9\-]{36}/i', $search, $matches)) {
            $uuid = strtoupper(str_replace('-', '', $matches[0]));
        }

        $uuidBinary = pack('H*', $uuid);

        $queryBuilder = $this->createQueryBuilder('rf')
            ->leftJoin('rf.resourceNode', 'rn')
            ->leftJoin('rn.resourceLinks', 'rl')
            ->leftJoin('rl.course', 'c')
            ->leftJoin('rl.user', 'u')
            ->select('COUNT(rf.id)');

        if ($search) {
            $queryBuilder->where('rf.title LIKE :search')
                ->orWhere('rf.originalName LIKE :search')
                ->orWhere('c.title LIKE :search')
                ->orWhere('u.username LIKE :search')
                ->orWhere('rn.uuid = :uuid')
                ->setParameter('search', '%' . $search . '%')
                ->setParameter('uuid', $uuidBinary);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
