<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CStudentPublication
 *
 * @ORM\Table(name="c_student_publication", indexes={@ORM\Index(name="session_id", columns={"session_id"})})
 * @ORM\Entity
 */
class CStudentPublication
{
    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255, nullable=true)
     */
    private $filename;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=255, nullable=true)
     */
    private $author;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=true)
     */
    private $active;

    /**
     * @var boolean
     *
     * @ORM\Column(name="accepted", type="boolean", nullable=true)
     */
    private $accepted;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_group_id", type="integer", nullable=false)
     */
    private $postGroupId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent_date", type="datetime", nullable=false)
     */
    private $sentDate;

    /**
     * @var string
     *
     * @ORM\Column(name="filetype", type="string", nullable=false)
     */
    private $filetype;

    /**
     * @var integer
     *
     * @ORM\Column(name="has_properties", type="integer", nullable=false)
     */
    private $hasProperties;

    /**
     * @var boolean
     *
     * @ORM\Column(name="view_properties", type="boolean", nullable=true)
     */
    private $viewProperties;

    /**
     * @var float
     *
     * @ORM\Column(name="qualification", type="float", precision=6, scale=2, nullable=false)
     */
    private $qualification;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_of_qualification", type="datetime", nullable=false)
     */
    private $dateOfQualification;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=false)
     */
    private $parentId;

    /**
     * @var integer
     *
     * @ORM\Column(name="qualificator_id", type="integer", nullable=false)
     */
    private $qualificatorId;

    /**
     * @var float
     *
     * @ORM\Column(name="weight", type="float", precision=6, scale=2, nullable=false)
     */
    private $weight;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_text_assignment", type="integer", nullable=false)
     */
    private $allowTextAssignment;

    /**
     * @var integer
     *
     * @ORM\Column(name="contains_file", type="integer", nullable=false)
     */
    private $containsFile;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
