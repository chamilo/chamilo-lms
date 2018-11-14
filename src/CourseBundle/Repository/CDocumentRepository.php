<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

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
}
