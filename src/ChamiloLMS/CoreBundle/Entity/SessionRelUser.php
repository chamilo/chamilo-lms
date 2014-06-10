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
     * @ORM\Column(name="moved_to", type="integer", nullable=true)
     */
    private $movedTo;

    /**
     * @var integer
     *
     * @ORM\Column(name="moved_status", type="integer", nullable=true)
     */
    private $movedStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="moved_at", type="datetime", nullable=false)
     */
    private $movedAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_session", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idSession;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $idUser;

    /**
     * @var integer
     *
     * @ORM\Column(name="relation_type", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $relationType;


}
