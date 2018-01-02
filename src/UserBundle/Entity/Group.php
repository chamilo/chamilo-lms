<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Entity;

use Sonata\UserBundle\Entity\BaseGroup as BaseGroup;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Chamilo\UserBundle\Repository\GroupRepository")
 * @ORM\Table(name="fos_group")
 */
class Group extends BaseGroup
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToMany(targetEntity="Chamilo\UserBundle\Entity\User", mappedBy="groups")
     *
     */
    protected $users;

    /**
     * @var string
     * @ORM\Column(name="code", type="string", length=40, nullable=false, unique=true)
     */
    protected $code;

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return Group
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }


}
