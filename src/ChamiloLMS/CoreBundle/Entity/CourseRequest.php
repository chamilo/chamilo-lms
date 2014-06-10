<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CourseRequest
 *
 * @ORM\Table(name="course_request", uniqueConstraints={@ORM\UniqueConstraint(name="code", columns={"code"})})
 * @ORM\Entity
 */
class CourseRequest
{
    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=40, nullable=false)
     */
    private $code;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="directory", type="string", length=40, nullable=true)
     */
    private $directory;

    /**
     * @var string
     *
     * @ORM\Column(name="db_name", type="string", length=40, nullable=true)
     */
    private $dbName;

    /**
     * @var string
     *
     * @ORM\Column(name="course_language", type="string", length=20, nullable=true)
     */
    private $courseLanguage;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=250, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="category_code", type="string", length=40, nullable=true)
     */
    private $categoryCode;

    /**
     * @var string
     *
     * @ORM\Column(name="tutor_name", type="string", length=200, nullable=true)
     */
    private $tutorName;

    /**
     * @var string
     *
     * @ORM\Column(name="visual_code", type="string", length=40, nullable=true)
     */
    private $visualCode;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="request_date", type="datetime", nullable=false)
     */
    private $requestDate;

    /**
     * @var string
     *
     * @ORM\Column(name="objetives", type="text", nullable=true)
     */
    private $objetives;

    /**
     * @var string
     *
     * @ORM\Column(name="target_audience", type="text", nullable=true)
     */
    private $targetAudience;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="info", type="integer", nullable=false)
     */
    private $info;

    /**
     * @var integer
     *
     * @ORM\Column(name="exemplary_content", type="integer", nullable=false)
     */
    private $exemplaryContent;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
