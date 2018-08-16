<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PageBundle\Entity;

use Sonata\PageBundle\Entity\BaseBlock;

/**
 * Class Block.
 *
 * @package Chamilo\PageBundle\Entity
 */
class Block extends BaseBlock
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
