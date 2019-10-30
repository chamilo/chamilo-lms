<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Class ExtraFieldSavedSearch.
 *
 * @ORM\Entity
 * @ORM\Table(name="extra_field_saved_search")
 */
class ExtraFieldSavedSearch
{
    use TimestampableEntity;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var ExtraField
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\ExtraField")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    protected $field;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="array", nullable=true, unique=false)
     */
    protected $value;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ExtraField
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param ExtraField $field
     *
     * @return ExtraFieldSavedSearch
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return ExtraFieldSavedSearch
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return ExtraFieldSavedSearch
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}
