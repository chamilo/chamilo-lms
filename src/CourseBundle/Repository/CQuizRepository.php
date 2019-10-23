<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;

/**
 * Class CQuizRepository.
 */
class CQuizRepository extends ResourceRepository
{
    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var FilesystemInterface
     */
    private $fs;

    /**
     * CQuizRepository constructor.
     *
     * @param EntityManager $entityManager
     * @param MountManager  $mountManager
     */
    public function __construct(EntityManager $entityManager, MountManager $mountManager)
    {
        $this->repository = $entityManager->getRepository(CQuiz::class);
        $this->fs = $mountManager->getFilesystem('resources_fs');
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $id
     *
     * @return CQuiz|null
     */
    public function find(int $id): ?CQuiz
    {
        return $this->repository->find($id);
    }

    /**
     * @param array      $criteria
     * @param array|null $orderBy
     *
     * @return CQuiz|null
     */
    public function findOneBy(array $criteria, array $orderBy = null): ?CQuiz
    {
        return $this->repository->findOneBy($criteria, $orderBy);
    }
}
