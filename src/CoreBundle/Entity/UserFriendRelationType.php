<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserFriendRelationType.
 *
 * @ORM\Table(name="user_friend_relation_type")
 * @ORM\Entity
 */
class UserFriendRelationType
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=20, nullable=true)
     */
    protected $title;

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return UserFriendRelationType
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

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
     * Set id.
     *
     * @return int
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
