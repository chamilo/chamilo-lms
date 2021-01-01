<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Illustration;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceIllustrationInterface;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Form\Resource\IllustrationType;
use Chamilo\CoreBundle\Repository\GridInterface;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\UploadInterface;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class IllustrationRepository.
 */
final class IllustrationRepository extends ResourceRepository implements GridInterface, UploadInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Illustration::class);
    }

    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('resource')
            ->select('resource')
            //->from($className, 'resource')
            ->innerJoin(
                'resource.resourceNode',
                'node'
            )
        ;

        $qb->andWhere('node.creator = :creator');
        $qb->setParameter('creator', $user);

        return $qb;
    }

    public function saveUpload(UploadedFile $file)
    {
        $resource = new Illustration();
        $resource->setName($file->getClientOriginalName());

        return $resource;
    }

    public function setResourceProperties(FormInterface $form, $course, $session, $fileType)
    {
        return $form->getData();

        //->setCourse($course)
            //->setSession($session)
            //->setFiletype($fileType)
            //->setTitle($title) // already added in $form->getData()
    }

    public function addIllustration(AbstractResource $resource, User $user, UploadedFile $uploadFile = null): ?ResourceFile
    {
        if (null === $uploadFile) {
            return null;
        }

        $illustrationNode = $this->getIllustrationNodeFromParent($resource->getResourceNode());
        $em = $this->getEntityManager();

        if (null === $illustrationNode) {
            $illustration = new Illustration();
            $em->persist($illustration);
            $this->addResourceNode($illustration, $user, $resource);
        } else {
            $illustration = $this->findOneBy(['resourceNode' => $illustrationNode]);
        }

        //$this->addResourceToEveryone($illustrationNode);
        return $this->addFile($illustration, $uploadFile);
    }

    public function addIllustrationToUser(User $user, $uploadFile): ?ResourceFile
    {
        if (null === $uploadFile) {
            return null;
        }

        $illustrationNode = $this->getIllustrationNodeFromParent($user->getResourceNode());
        $em = $this->getEntityManager();

        if (null === $illustrationNode) {
            $illustration = new Illustration();
            $illustration->setParentResourceNode($user->getResourceNode()->getId());
            $em->persist($illustration);
        } else {
            $illustration = $this->findOneBy(['resourceNode' => $illustrationNode]);
        }

        $file = $this->addFile($illustration, $uploadFile);
        $em->persist($file);
        $em->flush();

        return $file;
    }

    public function getIllustrationNodeFromParent(ResourceNode $resourceNode): ?ResourceNode
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
            ->setParameters(['parent' => $resourceNode->getId(), 'name' => $name])
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function deleteIllustration(AbstractResource $resource)
    {
        $node = $this->getIllustrationNodeFromParent($resource->getResourceNode());

        if (null !== $node) {
            $this->getEntityManager()->remove($node);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param string $filter See: services.yaml parameter "glide_media_filters" to see the list of filters.
     */
    public function getIllustrationUrl(
        ResourceIllustrationInterface $resource,
        string $filter = '',
        $size = null
    ): string {
        $illustration = $this->getIllustrationUrlFromNode($resource->getResourceNode(), $filter);

        if (empty($illustration)) {
            $illustration = $resource->getDefaultIllustration($size);
        }

        return $illustration;
    }

    public function getIllustrationUrlFromNode(ResourceNode $node, string $filter = ''): string
    {
        $node = $this->getIllustrationNodeFromParent($node);

        if (null !== $node) {
            $params = [
                'id' => $node->getId(),
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

    public function getResourceFormType(): string
    {
        return IllustrationType::class;
    }
}
