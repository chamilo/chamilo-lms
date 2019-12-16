<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PageBundle\Entity;

use Sonata\PageBundle\Entity\BasePage;

/**
 * Class Page.
 */
class Page extends BasePage
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
