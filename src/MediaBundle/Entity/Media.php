<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\MediaBundle\Entity;

use Sonata\MediaBundle\Entity\BaseMedia;

/**
 * Class Media.
 *
 * @package Chamilo\MediaBundle\Entity
 */
class Media extends BaseMedia
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
