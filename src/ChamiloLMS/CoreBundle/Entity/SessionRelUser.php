<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SessionRelUser
 *
 * @ORM\Table(name="session_rel_user", indexes={@ORM\Index(name="idx_session_rel_user_id_user_moved", columns={"id_user", "moved_to"})})
 * @ORM\Entity
 */
class SessionRelUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_session", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idSession;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idUser;

    /**
     * @var integer
     *
     * @ORM\Column(name="relation_type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $relationType;

    /**
     * @var integer
     *
     * @ORM\Column(name="moved_to", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $movedTo;

    /**
     * @var integer
     *
     * @ORM\Column(name="moved_status", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $movedStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="moved_at", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $movedAt;


    /**
     * Set idSession
     *
     * @param integer $idSession
     * @return SessionRelUser
     */
    public function setIdSession($idSession)
    {
        $this->idSession = $idSession;

        return $this;
    }

    /**
     * Get idSession
     *
     * @return integer 
     */
    public function getIdSession()
    {
        return $this->idSession;
    }

    /**
     * Set idUser
     *
     * @param integer $idUser
     * @return SessionRelUser
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return integer 
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set relationType
     *
     * @param integer $relationType
     * @return SessionRelUser
     */
    public function setRelationType($relationType)
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Get relationType
     *
     * @return integer 
     */
    public function getRelationType()
    {
        return $this->relationType;
    }

    /**
     * Set movedTo
     *
     * @param integer $movedTo
     * @return SessionRelUser
     */
    public function setMovedTo($movedTo)
    {
        $this->movedTo = $movedTo;

        return $this;
    }

    /**
     * Get movedTo
     *
     * @return integer 
     */
    public function getMovedTo()
    {
        return $this->movedTo;
    }

    /**
     * Set movedStatus
     *
     * @param integer $movedStatus
     * @return SessionRelUser
     */
    public function setMovedStatus($movedStatus)
    {
        $this->movedStatus = $movedStatus;

        return $this;
    }

    /**
     * Get movedStatus
     *
     * @return integer 
     */
    public function getMovedStatus()
    {
        return $this->movedStatus;
    }

    /**
     * Set movedAt
     *
     * @param \DateTime $movedAt
     * @return SessionRelUser
     */
    public function setMovedAt($movedAt)
    {
        $this->movedAt = $movedAt;

        return $this;
    }

    /**
     * Get movedAt
     *
     * @return \DateTime 
     */
    public function getMovedAt()
    {
        return $this->movedAt;
    }
}
