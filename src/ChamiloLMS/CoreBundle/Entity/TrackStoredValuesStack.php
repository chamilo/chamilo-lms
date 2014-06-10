<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackStoredValuesStack
 *
 * @ORM\Table(name="track_stored_values_stack", uniqueConstraints={@ORM\UniqueConstraint(name="user_id_2", columns={"user_id", "sco_id", "course_id", "sv_key", "stack_order"})})
 * @ORM\Entity
 */
class TrackStoredValuesStack
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
     * @ORM\Column(name="sco_id", type="integer", nullable=false)
     */
    private $scoId;

    /**
     * @var integer
     *
     * @ORM\Column(name="stack_order", type="integer", nullable=false)
     */
    private $stackOrder;

    /**
     * @var string
     *
     * @ORM\Column(name="course_id", type="string", length=40, nullable=false)
     */
    private $courseId;

    /**
     * @var string
     *
     * @ORM\Column(name="sv_key", type="string", length=64, nullable=false)
     */
    private $svKey;

    /**
     * @var string
     *
     * @ORM\Column(name="sv_value", type="text", nullable=false)
     */
    private $svValue;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
