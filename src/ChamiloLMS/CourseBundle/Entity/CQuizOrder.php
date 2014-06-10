<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CQuizOrder
 *
 * @ORM\Table(name="c_quiz_order")
 * @ORM\Entity
 */
class CQuizOrder
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
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exercise_id", type="integer", nullable=false)
     */
    private $exerciseId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exercise_order", type="integer", nullable=false)
     */
    private $exerciseOrder;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
