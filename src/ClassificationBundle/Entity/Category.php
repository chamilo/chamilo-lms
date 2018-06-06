<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ClassificationBundle\Entity;

use Sonata\ClassificationBundle\Entity\BaseCategory;

/**
 * Class Category.
 *
 * @package Chamilo\ClassificationBundle\Entity
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
     * @return int $id
     */
    public function getId(): int
    {
        return $this->id;
    }
}
