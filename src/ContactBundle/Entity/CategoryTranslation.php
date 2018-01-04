<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ContactBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class CategoryTranslation
 *
 * @ORM\Entity
 * @ORM\Table(name="contact_category_translation")
 * )
 *
 * @package Chamilo\ContactBundle\Entity
 */
class CategoryTranslation
{
    use ORMBehaviors\Translatable\Translation;

    /**
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $name;

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
