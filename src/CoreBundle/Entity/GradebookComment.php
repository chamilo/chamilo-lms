<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Table(
 *     name="gradebook_comment",
 *     indexes={}
 * )
 * @ORM\Entity
 */
class GradebookComment
{
    use UserTrait;
    use TimestampableEntity;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="gradeBookComments")
     * @ORM\JoinColumn(name="user_id",referencedColumnName="id",onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookCategory", inversedBy="comments")
     * @ORM\JoinColumn(name="gradebook_id",referencedColumnName="id",onDelete="CASCADE")
     */
    protected GradebookCategory $gradeBook;

    /**
     * @ORM\Column(name="comment", type="text")
     */
    protected string $comment;

    public function __construct()
    {
        $this->comment = '';
    }
}
