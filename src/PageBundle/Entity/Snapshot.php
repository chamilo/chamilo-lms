<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PageBundle\Entity;

use Sonata\PageBundle\Entity\BaseSnapshot;

/**
 * Class Snapshot.
 *
 * @package Chamilo\PageBundle\Entity
 */
class Snapshot extends BaseSnapshot
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
