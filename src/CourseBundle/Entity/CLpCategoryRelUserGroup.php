<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Traits\CourseTrait;
use Chamilo\CoreBundle\Traits\SessionTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Table(name: 'c_lp_category_rel_usergroup')]
class CLpCategoryRelUserGroup
{
    use CourseTrait;
    use SessionTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CLpCategory::class)]
    #[ORM\JoinColumn(name: "lp_category_id", referencedColumnName: "iid")]
    protected CLpCategory $lpCategory;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: true)]
    protected ?Session $session = null;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false)]
    protected Course $course;

    #[ORM\ManyToOne(targetEntity: Usergroup::class)]
    #[ORM\JoinColumn(name: 'usergroup_id', referencedColumnName: 'id', nullable: true)]
    protected ?Usergroup $userGroup = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

    public function __construct()
    {
    }

    /**
     * @return CLpCategory
     */
    public function getLpCategory(): CLpCategory
    {
        return $this->lpCategory;
    }

    /**
     * @param CLpCategory $lpCategory
     */
    public function setLpCategory(CLpCategory $lpCategory): self
    {
        $this->lpCategory = $lpCategory;

        return $this;
    }

    /**
     * @return Usergroup|null
     */
    public function getUserGroup(): ?Usergroup
    {
        return $this->userGroup;
    }

    /**
     * @param Usergroup|null $userGroup
     */
    public function setUserGroup(?Usergroup $userGroup): self
    {
        $this->userGroup = $userGroup;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }


}
