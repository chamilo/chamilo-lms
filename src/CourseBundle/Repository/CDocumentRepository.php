<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Gaufrette\Exception\FileNotFound;
use Sonata\MediaBundle\Provider\MediaProviderInterface;
use Sonata\MediaBundle\Provider\Pool;

/**
 * Class CDocumentRepository.
 */
class CDocumentRepository extends ResourceRepository
{
    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var Pool
     */
    private $mediaPool;

    /**
     * CDocumentRepository constructor.
     *
     * @param EntityManager $entityManager
     * @param Pool          $mediaPool
     */
    public function __construct(EntityManager $entityManager, Pool $mediaPool)
    {
        $this->repository = $entityManager->getRepository(CDocument::class);
        $this->mediaPool = $mediaPool;
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $id
     *
     * @return CDocument|null
     */
    public function find(int $id): ?CDocument
    {
        return $this->repository->find($id);
    }

    /**
     * @param array      $criteria
     * @param array|null $orderBy
     *
     * @return CDocument|null
     */
    public function findOneBy(array $criteria, array $orderBy = null): ?CDocument
    {
        return $this->repository->findOneBy($criteria, $orderBy);
    }

    /**
     * @param int $id
     *
     * @return string
     */
    public function getDocumentPath($id): string
    {
        try {
            $document = $this->find($id);
            $resourceNode = $document->getResourceNode();
            $resourceFile = $resourceNode->getResourceFile();
            $media = $resourceFile->getMedia();
            $provider = $this->mediaPool->getProvider($media->getProviderName());

            $format = MediaProviderInterface::FORMAT_REFERENCE;
            $filename = sprintf(
                '%s/%s',
                $provider->getFilesystem()->getAdapter()->getDirectory(),
                $provider->generatePrivateUrl($media, $format)
            );

            return $filename;
        } catch (\Throwable $exception) {
            throw new FileNotFound($id);
        }
    }

    /**
     * @param CDocument $document
     *
     * @return CDocument|null
     */
    public function getParent(CDocument $document)
    {
        $resourceParent = $document->getResourceNode()->getParent();

        if (!empty($resourceParent)) {
            $resourceParentId = $resourceParent->getId();
            $criteria = [
                'resourceNode' => $resourceParentId,
            ];

            return $this->findOneBy($criteria);
        }

        return null;
    }

    /**
     * @param int    $courseId
     * @param string $path
     *
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getFolderSize($courseId, $path)
    {
        $path = str_replace('_', '\_', $path);
        $addedSlash = $path === '/' ? '' : '/';

        $repo = $this->repository;
        $qb = $repo->createQueryBuilder('d');
        $query = $qb
            ->select('SUM(d.size)')
            ->innerJoin('d.resourceNode', 'r')
            ->innerJoin('r.resourceLinks', 'l')
            ->where('d.path LIKE :path')
            ->andWhere('d.path NOT LIKE :deleted')
            ->andWhere('d.path NOT LIKE :extra_path ')
            ->andWhere('l.visibility <> :visibility')
            ->andWhere('d.course = :course')
            ->setParameters([
                'path' => $path.$addedSlash.'%',
                'extra_path' => $path.$addedSlash.'%/%',
                'course' => $courseId,
                'deleted' => '%_DELETED_%',
                'visibility' => ResourceLink::VISIBILITY_DELETED,
            ])
            ->getQuery();

        return $query->getSingleScalarResult();
    }

    public function getTotalSpace($courseId, $groupId = null, $sessionId = null)
    {
        $repo = $this->repository;
        $qb = $repo->createQueryBuilder('d');
        $query = $qb
            ->select('SUM(d.size)')
            ->innerJoin('d.resourceNode', 'r')
            ->innerJoin('r.resourceLinks', 'l')
            ->where('l.course = :course')
            ->andWhere('l.group = :group')
            ->andWhere('l.session = :session')
            ->andWhere('l.visibility <> :visibility')
            ->setParameters([
                'course' => $courseId,
                'group' => $groupId,
                'session' => $sessionId,
                'visibility' => ResourceLink::VISIBILITY_DELETED,
            ])
            ->getQuery();

        return $query->getSingleScalarResult();
    }
}
