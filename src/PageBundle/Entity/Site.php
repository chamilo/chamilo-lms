<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PageBundle\Entity;

use Sonata\PageBundle\Entity\BaseSite;

/**
 * Class Site.
 *
 * @package Chamilo\PageBundle\Entity
 */
class Site extends BaseSite
{
    /**
     * @var int
     */
    protected $id;

    /**
     * Get id.
     *
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }
}
