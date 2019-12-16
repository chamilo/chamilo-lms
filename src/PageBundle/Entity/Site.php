<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PageBundle\Entity;

use Sonata\PageBundle\Entity\BaseSite;

/**
 * Class Site.
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
