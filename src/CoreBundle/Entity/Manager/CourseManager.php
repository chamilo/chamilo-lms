<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Manager;

use Chamilo\CoreBundle\Entity\Course;
use Sonata\CoreBundle\Model\BaseEntityManager;

/**
 * Class CourseManager.
 *
 * @package Chamilo\CoreBundle\Entity\Manager
 */
class CourseManager extends BaseEntityManager
{
    /**
     * @return Course
     */
    public function createCourse()
    {
        return $this->create();
    }

    /**
     * @param string $code
     *
     * @return mixed
     */
    public function findOneByCode($code)
    {
        return $this->getRepository()->findOneByCode($code);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function findOneByTitle($name)
    {
        return $this->getRepository()->findOneByTitle($name);
    }
}
