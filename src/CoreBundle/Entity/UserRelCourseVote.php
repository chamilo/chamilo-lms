<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * UserRelCourseVote.
 */
#[ORM\Table(name: 'user_rel_course_vote')]
#[ORM\Index(columns: ['c_id'], name: 'idx_ucv_cid')]
#[ORM\Index(columns: ['user_id'], name: 'idx_ucv_uid')]
#[ORM\Index(columns: ['user_id', 'c_id'], name: 'idx_ucv_cuid')]
#[ORM\Entity]
class UserRelCourseVote
{
    use UserTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userRelCourseVotes')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Course $course;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Session $session = null;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class, inversedBy: 'courses')]
    #[ORM\JoinColumn(name: 'url_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected AccessUrl $url;

    #[ORM\Column(name: 'vote', type: 'integer', nullable: false)]
    protected int $vote;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setVote(int $vote): self
    {
        $this->vote = $vote;

        return $this;
    }

    public function getVote(): int
    {
        return $this->vote;
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

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getUrl(): AccessUrl
    {
        return $this->url;
    }

    public function setUrl(AccessUrl $url): self
    {
        $this->url = $url;

        return $this;
    }
}
