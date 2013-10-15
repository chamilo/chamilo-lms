<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * JuryMemberRelUser
 *
 * @ORM\Table(name="jury_member_rel_user")
 * @ORM\Entity()
 */
class JuryMemberRelUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="jury_member_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $juryMemberId;

    /**
    * @ORM\ManyToOne(targetEntity="JuryMembers")
    * @ORM\JoinColumn(name="jury_member_id", referencedColumnName="id")
    */
    private $member;

    /**
     *
     */
    public function __construct()
    {
    }

    public function setMember($member)
    {
        $this->member = $member;
    }

    public function getMember()
    {
        return $this->member;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set UserId
     *
     * @param int $userId
     * @return Jury
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get user id
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set setJuryMemberId
     *
     * @param integer $value
     * @return Jury
     */
    public function setJuryMemberId($value)
    {
        $this->juryMemberId = $value;

        return $this;
    }

    /**
     * Get juryMemberId
     *
     * @return integer
     */
    public function getJuryMemberId()
    {
        return $this->juryMemberId;
    }
}
