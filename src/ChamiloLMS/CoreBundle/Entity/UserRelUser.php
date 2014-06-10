<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserRelUser
 *
 * @ORM\Table(name="user_rel_user", indexes={@ORM\Index(name="idx_user_rel_user__user", columns={"user_id"}), @ORM\Index(name="idx_user_rel_user__friend_user", columns={"friend_user_id"}), @ORM\Index(name="idx_user_rel_user__user_friend_user", columns={"user_id", "friend_user_id"})})
 * @ORM\Entity
 */
class UserRelUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="friend_user_id", type="integer", nullable=false)
     */
    private $friendUserId;

    /**
     * @var integer
     *
     * @ORM\Column(name="relation_type", type="integer", nullable=false)
     */
    private $relationType;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_edit", type="datetime", nullable=true)
     */
    private $lastEdit;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
