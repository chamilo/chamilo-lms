<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\MediaBundle\Entity;

use Sonata\MediaBundle\Entity\BaseMedia;

/**
 * Class Media.
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
