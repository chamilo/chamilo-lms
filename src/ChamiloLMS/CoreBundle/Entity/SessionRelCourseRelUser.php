<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SessionRelCourseRelUser
 *
 * @ORM\Table(name="session_rel_course_rel_user", indexes={@ORM\Index(name="idx_session_rel_course_rel_user_id_user", columns={"id_user"}), @ORM\Index(name="idx_session_rel_course_rel_user_course_id", columns={"c_id"})})
 * @ORM\Entity
 */
class SessionRelCourseRelUser
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
     * @ORM\Column(name="id_session", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $idSession;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $idUser;

    /**
     * @var integer
     *
     * @ORM\Column(name="visibility", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visibility;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="legal_agreement", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $legalAgreement;


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
     * Set idSession
     *
     * @param integer $idSession
     * @return SessionRelCourseRelUser
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
     * Set cId
     *
     * @param integer $cId
     * @return SessionRelCourseRelUser
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer 
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set idUser
     *
     * @param integer $idUser
     * @return SessionRelCourseRelUser
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
     * Set visibility
     *
     * @param integer $visibility
     * @return SessionRelCourseRelUser
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return integer 
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return SessionRelCourseRelUser
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set legalAgreement
     *
     * @param integer $legalAgreement
     * @return SessionRelCourseRelUser
     */
    public function setLegalAgreement($legalAgreement)
    {
        $this->legalAgreement = $legalAgreement;

        return $this;
    }

    /**
     * Get legalAgreement
     *
     * @return integer 
     */
    public function getLegalAgreement()
    {
        return $this->legalAgreement;
    }
}
