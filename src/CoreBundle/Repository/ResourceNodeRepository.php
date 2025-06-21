<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Utils\AccessUrlUtil;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Tree\Entity\Repository\MaterializedPathRepository;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Throwable;
use Vich\UploaderBundle\Storage\FlysystemStorage;

/**
 * @template-extends MaterializedPathRepository<ResourceNode>
 */
class ResourceNodeRepository extends MaterializedPathRepository
{
    protected FilesystemOperator $filesystem;

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly FlysystemStorage $storage,
        private readonly FilesystemOperator $resourceFilesystem,
        private readonly RouterInterface $router,
        private readonly AccessUrlUtil $accessUrlUtil,
        private readonly SettingsManager $settingsManager
    ) {
        $this->filesystem = $resourceFilesystem; // Asignar el filesystem correcto
        parent::__construct($manager, $manager->getClassMetadata(ResourceNode::class));
    }

    public function getFilename(ResourceFile $resourceFile): ?string
    {
        return $this->storage->resolveUri($resourceFile);
    }

    /*public function create(ResourceNode $node): void
     * {
     * $this->getEntityManager()->persist($node);
     * $this->getEntityManager()->flush();
     * }
     * public function update(ResourceNode $node, bool $andFlush = true): void
     * {
     * //$node->setUpdatedAt(new \DateTime());
     * $this->getEntityManager()->persist($node);
     * if ($andFlush) {
     * $this->getEntityManager()->flush();
     * }
     * }*/
    public function getFileSystem(): FilesystemOperator
    {
        return $this->filesystem;
    }

    public function getResourceNodeFileContent(ResourceNode $resourceNode, ?ResourceFile $resourceFile = null): string
    {
        try {
            $resourceFile ??= $resourceNode->getResourceFiles()->first();

            if ($resourceFile) {
                $fileName = $this->getFilename($resourceFile);

                return $this->getFileSystem()->read($fileName);
            }

            return '';
        } catch (Throwable $throwable) {
            throw new FileNotFoundException($resourceNode->getTitle());
        }
    }

    /**
     * @return false|resource
     */
    public function getResourceNodeFileStream(ResourceNode $resourceNode, ?ResourceFile $resourceFile = null)
    {
        try {
            $resourceFile ??= $resourceNode->getResourceFiles()->first();

            if ($resourceFile) {
                $fileName = $this->getFilename($resourceFile);

                return $this->getFileSystem()->readStream($fileName);
            }

            return false;
        } catch (Throwable $exception) {
            throw new FileNotFoundException($resourceNode->getTitle());
        }
    }

    public function getResourceFileUrl(?ResourceNode $resourceNode, array $extraParams = [], ?int $referenceType = null, ?ResourceFile $resourceFile = null): string
    {
        try {
            $file = $resourceFile ?? $resourceNode?->getResourceFiles()->first();

            if ($file) {
                $params = [
                    'tool' => $resourceNode->getResourceType()->getTool(),
                    'type' => $resourceNode->getResourceType(),
                    'id' => $resourceNode->getUuid(),
                ];

                if ($resourceFile) {
                    $params['resourceFileId'] = $resourceFile->getId();
                }

                if (!empty($extraParams)) {
                    $params = array_merge($params, $extraParams);
                }

                $referenceType ??= UrlGeneratorInterface::ABSOLUTE_PATH;

                $mode = $params['mode'] ?? 'view';
                // Remove mode from params and sent directly to the controller.
                unset($params['mode']);

                switch ($mode) {
                    case 'download':
                        return $this->router->generate('chamilo_core_resource_download', $params, $referenceType);

                    case 'view':
                        return $this->router->generate('chamilo_core_resource_view', $params, $referenceType);
                }
            }

            return '';
        } catch (Throwable $exception) {
            throw new FileNotFoundException($resourceNode->getTitle());
        }
    }

    /**
     * @todo filter files, check status
     */
    public function getSize(ResourceNode $resourceNode, ResourceType $type, ?Course $course = null, ?Session $session = null): int
    {
        $qb = $this->createQueryBuilder('node')
            ->select('SUM(file.size) as total')
            ->innerJoin('node.resourceFiles', 'file')
            ->innerJoin('node.resourceLinks', 'l')
            ->where('node.resourceType = :type')
            ->andWhere('node.parent = :parentNode')
            ->andWhere('file IS NOT NULL')
        ;

        $params = [];
        if (null !== $course) {
            $qb->andWhere('l.course = :course');
            $params['course'] = $course;
        }
        $params['parentNode'] = $resourceNode;
        $params['type'] = $type;

        $qb->setParameters($params);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findByResourceTypeAndCourse(string $type, Course $course): array
    {
        $qb = $this->createQueryBuilder('node');

        return $qb
            ->innerJoin('node.resourceType', 'resourceType')
            ->innerJoin('node.resourceLinks', 'resourceLinks')
            ->where($qb->expr()->eq('resourceType.title', ':resourceType'))
            ->andWhere($qb->expr()->eq('resourceLinks.course', ':course'))
            ->setParameters([
                'resourceType' => $type,
                'course' => $course,
            ])
            ->getQuery()
            ->getResult()
        ;
    }
}
