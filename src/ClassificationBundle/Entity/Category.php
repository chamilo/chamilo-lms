<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\ClassificationBundle\Entity;

use Sonata\ClassificationBundle\Entity\BaseCategory;

/**
 * Class Category.
 */
class Category extends BaseCategory
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
