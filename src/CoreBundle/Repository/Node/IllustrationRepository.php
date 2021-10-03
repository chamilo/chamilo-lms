<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Illustration;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceIllustrationInterface;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class IllustrationRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Illustration::class);
    }

    /*public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('resource')
            ->select('resource')
            ->innerJoin(
                'resource.resourceNode',
                'node'
            )
        ;

        $qb->andWhere('node.creator = :creator');
        $qb->setParameter('creator', $user);

        return $qb;
    }*/

    /**
     * @param ResourceInterface|User $resource
     */
    public function addIllustration(
        ResourceInterface $resource,
        User $creator,
        UploadedFile $uploadFile = null,
        string $crop = ''
    ): ?ResourceFile {
        if (null === $uploadFile) {
            return null;
        }

        $illustrationNode = $this->getIllustrationNodeFromParent($resource->getResourceNode());
        $em = $this->getEntityManager();

        if (null === $illustrationNode) {
            $illustration = (new Illustration())
                ->setCreator($creator)
                ->setParent($resource)
            ;
            $em->persist($illustration);
        } else {
            $illustration = $this->findOneBy([
                'resourceNode' => $illustrationNode,
            ]);
        }

        $file = $this->addFile($illustration, $uploadFile);
        if (null !== $file) {
            if (!empty($crop)) {
                $file->setCrop($crop);
            }
            $em->persist($file);
        }
        $em->flush();

        return $file;
    }

    public function getIllustrationNodeFromParent(?ResourceNode $resourceNode): ?ResourceNode
    {
        $nodeRepo = $this->getResourceNodeRepository();
        $name = $this->getResourceTypeName();

        $qb = $nodeRepo->createQueryBuilder('n')
            ->select('node')
            ->from(ResourceNode::class, 'node')
            ->innerJoin('node.resourceType', 'type')
            ->innerJoin('node.resourceFile', 'file')
            ->where('node.parent = :parent')
            ->andWhere('type.name = :name')
            ->setParameters([
                'parent' => isset($resourceNode) ? $resourceNode->getId() : 0,
                'name' => $name,
            ])
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function deleteIllustration(ResourceInterface $resource): void
    {
        $node = $this->getIllustrationNodeFromParent($resource->getResourceNode());

        if (null !== $node) {
            $this->getEntityManager()->remove($node);
            $this->getEntityManager()->flush();
        }
    }

    public function hasIllustration(ResourceIllustrationInterface $resource): bool
    {
        $node = $this->getIllustrationNodeFromParent($resource->getResourceNode());

        return null !== $node;
    }

    /**
     * @param string $filter See: services.yaml parameter "glide_media_filters" to see the list of filters.
     */
    public function getIllustrationUrl(
        ResourceIllustrationInterface $resource,
        string $filter = '',
        int $size = 32
    ): string {
        $illustration = $this->getIllustrationUrlFromNode($resource->getResourceNode(), $filter);

        if (empty($illustration)) {
            $illustration = $resource->getDefaultIllustration($size);
        }

        return $illustration;
    }

    private function getIllustrationUrlFromNode(?ResourceNode $node, string $filter = ''): string
    {
        $node = $this->getIllustrationNodeFromParent($node);

        if (null !== $node) {
            $params = [
                'id' => $node->getUuid(),
                'tool' => $node->getResourceType()->getTool(),
                'type' => $node->getResourceType()->getName(),
            ];

            if (!empty($filter)) {
                $params['filter'] = $filter;
            }

            return $this->getRouter()->generate('chamilo_core_resource_view', $params);
        }

        return '';
    }
}
