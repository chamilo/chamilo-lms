<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PageBundle\Entity;

use Sonata\PageBundle\Entity\BasePage;

/**
 * Class Page.
 *
 * @package Chamilo\PageBundle\Entity
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
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }
}
