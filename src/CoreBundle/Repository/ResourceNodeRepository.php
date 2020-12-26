<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Tree\Entity\Repository\MaterializedPathRepository;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Vich\UploaderBundle\Storage\FlysystemStorage;

/**
 * Class ResourceNodeRepository.
 */
class ResourceNodeRepository extends MaterializedPathRepository
{
    protected $mountManager;
    protected $storage;

    public function __construct(EntityManagerInterface $manager, FlysystemStorage $storage, MountManager $mountManager)
    {
        parent::__construct($manager, $manager->getClassMetadata(ResourceNode::class));
        $this->storage = $storage;
        $this->mountManager = $mountManager;
    }

    public function getFilename(ResourceFile $resourceFile)
    {
        return $this->storage->resolveUri($resourceFile);
    }

    /*public function create(ResourceNode $node): void
    {
        $this->getEntityManager()->persist($node);
        $this->getEntityManager()->flush();
    }

    public function update(ResourceNode $node, $andFlush = true): void
    {
        //$node->setUpdatedAt(new \DateTime());
        $this->getEntityManager()->persist($node);
        if ($andFlush) {
            $this->getEntityManager()->flush();
        }
    }*/

    /**
     * @return FilesystemInterface
     */
    public function getFileSystem()
    {
        // Flysystem mount name is saved in config/packages/oneup_flysystem.yaml @todo add it as a service.
        return $this->mountManager->getFilesystem('resources_fs');
    }

    public function getResourceNodeFileContent(ResourceNode $resourceNode): string
    {
        try {
            if ($resourceNode->hasResourceFile()) {
                $resourceFile = $resourceNode->getResourceFile();
                $fileName = $this->getFilename($resourceFile);

                return $this->getFileSystem()->read($fileName);
            }

            return '';
        } catch (\Throwable $exception) {
            throw new FileNotFoundException($resourceNode);
        }
    }

    public function getResourceNodeFileStream(ResourceNode $resourceNode)
    {
        try {
            if ($resourceNode->hasResourceFile()) {
                $resourceFile = $resourceNode->getResourceFile();
                $fileName = $this->getFilename($resourceFile);

                return $this->getFileSystem()->readStream($fileName);
            }

            return '';
        } catch (\Throwable $exception) {
            throw new FileNotFoundException($resourceNode);
        }
    }

    /**
     * @todo filter files, check status
     */
    public function getSize(ResourceNode $resourceNode, ResourceType $type, Course $course = null, Session $session = null): int
    {
        $qb = $this->createQueryBuilder('node')
            ->select('SUM(file.size) as total')
            ->innerJoin('node.resourceFile', 'file')
            ->innerJoin('node.resourceLinks', 'l')
            ->where('node.resourceType = :type')
            ->setParameter('type', $type)
            ->andWhere('node.parent = :parentNode')
            ->setParameter('parentNode', $resourceNode)
            ->andWhere('file IS NOT NULL')
            ->andWhere('l.visibility <> :visibility')
            ->setParameter('visibility', ResourceLink::VISIBILITY_DELETED)
        ;

        if ($course) {
            $qb
                ->andWhere('l.course = :course')
                ->setParameter('course', $course);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
