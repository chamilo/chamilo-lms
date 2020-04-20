<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use APY\DataGridBundle\Grid\Column\Column;
use APY\DataGridBundle\Grid\Grid;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Illustration;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceFile;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Form\Resource\IllustrationType;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class IllustrationRepository.
 */
final class IllustrationRepository extends ResourceRepository implements GridInterface, UploadInterface
{
    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroupInfo $group = null): QueryBuilder
    {
        $repo = $this->getRepository();
        $className = $repo->getClassName();

        $qb = $repo->getEntityManager()->createQueryBuilder()
            ->select('resource')
            ->from($className, 'resource')
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
        /** @var Illustration $resource */
        $resource = $this->create();
        $resource->setName($file->getClientOriginalName());

        return $resource;
    }

    public function setResourceProperties(FormInterface $form, $course, $session, $fileType)
    {
        $newResource = $form->getData();
        $newResource
            //->setCourse($course)
            //->setSession($session)
            //->setFiletype($fileType)
            //->setTitle($title) // already added in $form->getData()
        ;

        return $newResource;
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
            $illustration = $this->repository->findOneBy(['resourceNode' => $illustrationNode]);
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
            $em->persist($illustration);
            $this->createNodeForResource($illustration, $user, $user->getResourceNode());
        } else {
            $illustration = $this->repository->findOneBy(['resourceNode' => $illustrationNode]);
        }

        //$this->addResourceToEveryone($illustrationNode);
        $file = $this->addFile($illustration, $uploadFile);
        $em->flush();

        return $file;
    }

    public function getIllustrationNodeFromParent(ResourceNode $resourceNode): ?ResourceNode
    {
        $nodeRepo = $this->getResourceNodeRepository();
        $name = $this->getResourceTypeName();

        $qb = $nodeRepo->getEntityManager()->createQueryBuilder()
            ->select('node')
            ->from(ResourceNode::class, 'node')
            ->innerJoin('node.resourceType', 'type')
            ->innerJoin('node.resourceFile', 'file')
            ->where('node.parent = :parent')
            ->andWhere('type.name = :name')
            ->setParameters(['parent' => $resourceNode, 'name' => $name])
        ;

        return $qb->getQuery()->getFirstResult();
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
    public function getIllustrationUrl(AbstractResource $resource, string $filter = ''): string
    {
        return $this->getIllustrationUrlFromNode($resource->getResourceNode(), $filter);
    }

    public function getIllustrationUrlFromNode(ResourceNode $resourceNode, string $filter = ''): string
    {
        $node = $this->getIllustrationNodeFromParent($resourceNode);

        if (null !== $node) {
            $params = [
                'id' => $node->getId(),
                'tool' => $node->getResourceType()->getTool(),
                'type' => $node->getResourceType()->getName(),
            ];
            if (!empty($filter)) {
                $params['filter'] = $filter;
            }

            return $this->getRouter()->generate(
                'chamilo_core_resource_view_file',
                $params
            );
        }

        return '';
    }

    public function getTitleColumn(Grid $grid): Column
    {
        return $grid->getColumn('name');
    }

    public function getResourceFormType(): string
    {
        return IllustrationType::class;
    }
}
