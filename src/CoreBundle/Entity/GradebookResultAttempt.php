<?php

declare(strict_types=1);

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected ?bool $comment;

    /**
     * @ORM\Column(name="score", type="float", nullable=true)
     */
    protected ?float $score;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookResult")
     * @ORM\JoinColumn(name="result_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected GradebookResult $result;
}
