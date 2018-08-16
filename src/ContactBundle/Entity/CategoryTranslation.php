<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ContactBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class CategoryTranslation.
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="contact_category_translation",
 *     options={"row_format":"DYNAMIC"}
 * )
 *
 * @package Chamilo\ContactBundle\Entity
 */
class CategoryTranslation
{
    use ORMBehaviors\Translatable\Translation;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=190, nullable=false)
     */
    protected $locale;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return CategoryTranslation
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
