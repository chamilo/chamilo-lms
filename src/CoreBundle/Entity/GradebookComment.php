<?php

declare(strict_types=1);

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
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="gradeBookComments")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookCategory", inversedBy="comments")
     * @ORM\JoinColumn(name="gradebook_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected GradebookCategory $gradeBook;

    /**
     * @ORM\Column(name="comment", type="text")
     */
    protected ?string $comment;

    public function __construct()
    {
        $this->comment = '';
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getGradeBook(): GradebookCategory
    {
        return $this->gradeBook;
    }

    public function setGradeBook(GradebookCategory $gradeBook): self
    {
        $this->gradeBook = $gradeBook;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
