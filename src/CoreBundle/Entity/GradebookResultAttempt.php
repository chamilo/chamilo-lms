<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * GradebookResultAttempt.
 *
 * @ORM\Table(name="gradebook_result_attempt")
 * @ORM\Entity
 */
class GradebookResultAttempt
{
    use TimestampableEntity;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var bool
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float", nullable=true)
     */
    protected $score;

    /**
     * @var int
     *
     * @ORM\Column(name="result_id", type="integer", nullable=false)
     */
    protected $resultId;
}
