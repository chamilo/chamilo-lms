<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Manager;

use Sonata\CoreBundle\Model\BaseEntityManager;
use Chamilo\CoreBundle\Entity\Course;

/**
 * Class AccessUrlManager
 * @package Chamilo\CoreBundle\Entity\Manager
 */
class AccessUrlManager extends BaseEntityManager
{
    /**
     * @return Course
     */
    public function createUrl()
    {
        return $this->create();
    }
}
