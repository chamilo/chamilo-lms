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
     * @ORM\Column(name="id_session", type="integer", nullable=false)
     */
    private $idSession;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

    /**
     * @var integer
     *
     * @ORM\Column(name="visibility", type="integer", nullable=false)
     */
    private $visibility;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="legal_agreement", type="integer", nullable=true)
     */
    private $legalAgreement;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
