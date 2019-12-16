<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\ClassificationBundle\Entity;

use Sonata\ClassificationBundle\Entity\BaseTag;

/**
 * Class Tag.
 */
class Tag extends BaseTag
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
    public function getId(): int
    {
        return $this->id;
    }
}
