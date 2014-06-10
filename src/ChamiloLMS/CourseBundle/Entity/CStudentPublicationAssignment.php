<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CStudentPublicationAssignment
 *
 * @ORM\Table(name="c_student_publication_assignment")
 * @ORM\Entity
 */
class CStudentPublicationAssignment
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
     * @var \DateTime
     *
     * @ORM\Column(name="expires_on", type="datetime", nullable=false)
     */
    private $expiresOn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ends_on", type="datetime", nullable=false)
     */
    private $endsOn;

    /**
     * @var boolean
     *
     * @ORM\Column(name="add_to_calendar", type="boolean", nullable=false)
     */
    private $addToCalendar;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enable_qualification", type="boolean", nullable=false)
     */
    private $enableQualification;

    /**
     * @var integer
     *
     * @ORM\Column(name="publication_id", type="integer", nullable=false)
     */
    private $publicationId;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
