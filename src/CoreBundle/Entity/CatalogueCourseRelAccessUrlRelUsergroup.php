<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\CatalogueCourseRelAccessUrlRelUsergroupRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'catalogue_course_rel_access_url_rel_usergroup')]
#[ORM\Entity(repositoryClass: CatalogueCourseRelAccessUrlRelUsergroupRepository::class)]
class CatalogueCourseRelAccessUrlRelUsergroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Course $course;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class)]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private AccessUrl $accessUrl;

    #[ORM\ManyToOne(targetEntity: Usergroup::class)]
    #[ORM\JoinColumn(name: 'usergroup_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Usergroup $usergroup = null;


    public function getId(): ?int
    {
        return $this->id;
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

    public function getAccessUrl(): AccessUrl
    {
        return $this->accessUrl;
    }

    public function setAccessUrl(AccessUrl $accessUrl): self
    {
        $this->accessUrl = $accessUrl;

        return $this;
    }

    public function getUsergroup(): ?Usergroup
    {
        return $this->usergroup;
    }

    public function setUsergroup(?Usergroup $usergroup): self
    {
        $this->usergroup = $usergroup;

        return $this;
    }
}
