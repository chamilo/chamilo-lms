<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\NotebookBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Application\Sonata\UserBundle\Entity\User;
use Chamilo\NotebookBundle\Entity\CNotebook;
use Chamilo\CourseBundle\Tool\BaseTool;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * Class CNotebookRepository
 * @package Chamilo\NotebookBundle\Entity
 */
class CNotebookRepository extends EntityRepository
{
    /**
     * @param Course $course
     * @return mixed
     */
    public function createNewWithCourse(User $user, Course $course)
    {
        /** @var CNotebook $notebook */
        $notebook = parent::createNew();
        //$notebook->setCourse($course);

        $this->addResourceToCourse($notebook, $user, $course);

        return $notebook;
        //$notebook->save();

        //var_dump($course);
    }

    public function addResource(AbstractResource $resource, User $creator)
    {
        $resourceNode = new ResourceNode();
        $resourceNode->setName($resource->getName());
        $resourceNode->setCreator($creator);

        $resourceNode->setResourceId($resource->getId());
        $resourceNode->setTool('notebook');

        $this->getEntityManager()->persist($resourceNode);

        return $resourceNode;
    }

    public function addResourceToUser(AbstractResource $resource, User $user, User $toUser)
    {
        $resourceNode = $this->addResource($resource, $user);

        $resourceLink = new ResourceLink();
        $resourceLink->setResourceNode($resourceNode);

        $resourceLink->setUser($toUser);
        $this->getEntityManager()->persist($resourceLink);

        return $resourceLink;
    }

    public function addResourceToCourse(AbstractResource $resource, User $user, Course $course)
    {
        $resourceNode = $this->addResource($resource, $user);

        $resourceLink = new ResourceLink();
        $resourceLink->setResourceNode($resourceNode);
        $resourceLink->setCourse($course);

        $this->getEntityManager()->persist($resourceLink);

        return $resourceLink;
    }

    public function addResourceToSession(AbstractResource $resource, User $user, Course $course, Session $session)
    {
        $resourceLink = $this->addResourceToCourse($resource, $user, $course);
        $resourceLink->setSession($session);
        $this->getEntityManager()->persist($resourceLink);
    }

    public function addResourceToGroup(AbstractResource $resource, User $user, Course $course, CGroupInfo $group)
    {
        $resourceLink = $this->addResourceToCourse($resource, $user, $course);
        $resourceLink->setGroup($group);
        $this->getEntityManager()->persist($resourceLink);
    }



}
