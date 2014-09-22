<?php

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\CourseBundle\Entity\CItem;
use Chamilo\CoreBundle\Entity\Course;

use Sonata\CoreBundle\Model\BaseEntityManager;
use Chamilo\CourseBundle\Tool\BaseTool;
use Chamilo\UserBundle\Entity\User;

/**
 * Class CourseManager
 * @package Chamilo\CoreBundle\Entity
 */
class ItemManager extends BaseEntityManager
{
    /**
     * @param BaseTool $tool
     * @param User $user
     *
     * @return int id
     */
    public function addItem(BaseTool $tool, User $user)
    {
        /** @var CItem $item */
        $item = $this->create();
        $item->setTool($tool->getName());
        $item->setUser($user);
        //$item->setRef($tool->getObject()->getId());
        $this->save($item);

        return $item->getId();
    }

    public function addItemToCourse(BaseTool $tool, User $user, Course $course)
    {
        //$this->addItem();
    }

    public function addItemToGroup(BaseTool $tool, User $user, Course $course, CGroupInfo $group)
    {

    }

    public function addItemToSession(BaseTool $tool, User $user, Course $course, Session $session)
    {

    }

    public function addItemToUser(BaseTool $tool, User $user, User $toUser)
    {

    }
}
