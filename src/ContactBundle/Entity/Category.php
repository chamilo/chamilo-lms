<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ContactBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class Category
 *
 * @ORM\Entity
 * @ORM\Table(name="contact_category")
 *
 * @package Chamilo\FaqBundle\Entity
 */
class Category
{
    use ORMBehaviors\Translatable\Translatable;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="email", type="string")
     */
    protected $email;

    /**
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->proxyCurrentLocaleTranslation($method, $arguments);
    }

     /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getName();
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return Category
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Category
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
