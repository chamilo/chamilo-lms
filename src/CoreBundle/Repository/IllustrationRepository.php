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
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class IllustrationRepository.
 */
final class IllustrationRepository extends ResourceRepository implements ResourceRepositoryInterface
{
    public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroupInfo $group = null)
    {
        $repo = $this->getRepository();
        $className = $repo->getClassName();

        $qb = $repo->getEntityManager()->createQueryBuilder()
            ->select('resource')
            ->from($className, 'resource')
            ->innerJoin(
                ResourceNode::class,
                'node',
                Join::WITH,
                'resource.resourceNode = node.id'
            )
            //->innerJoin('node.resourceLinks', 'links')
            //->where('node.resourceType = :type')
            //->setParameter('type',$type)
        ;
        /*$qb
            ->andWhere('links.visibility = :visibility')
            ->setParameter('visibility', ResourceLink::VISIBILITY_PUBLISHED)
        ;*/

        if (null !== $parentNode) {
//            $qb->andWhere('node.parent = :parentNode');
 //           $qb->setParameter('parentNode', $parentNode);
        }

        $qb->andWhere('node.creator = :creator');
        $qb->setParameter('creator', $user);
        //var_dump($qb->getQuery()->getSQL(), $parentNode->getId());exit;

        return $qb;
    }

    public function saveUpload(UploadedFile $file)
    {
        /** @var Illustration $resource */
        $resource = $this->create();
        $resource->setName($file->getClientOriginalName());

        return $resource;
    }

    public function saveResource(FormInterface $form, $course, $session, $fileType)
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

    /**
     * @param $uploadFile
     */
    public function addIllustration(AbstractResource $resource, User $user, $uploadFile): ?ResourceFile
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
        return $this->addFile($illustration, $uploadFile);
    }

    public function getIllustrationNodeFromParent(ResourceNode $resourceNode): ?ResourceNode
    {
        $nodeRepo = $this->getResourceNodeRepository();
        $resourceType = $this->getResourceType();

        /** @var ResourceNode $node */
        return $nodeRepo->findOneBy(
            ['parent' => $resourceNode, 'resourceType' => $resourceType]
        );
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
    public function getIllustrationUrl(AbstractResource $resource, $filter = ''): string
    {
        return $this->getIllustrationUrlFromNode($resource->getResourceNode(), $filter);
    }

    public function getIllustrationUrlFromNode(ResourceNode $resourceNode, $filter = ''): string
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
                'chamilo_core_resource_view',
                $params
            );
        }

        return '';
    }

    public function getTitleColumn(Grid $grid): Column
    {
        return $grid->getColumn('name');
    }
}
