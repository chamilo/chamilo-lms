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

/**
 * @ORM\Table(name="c_lp_category_rel_usergroup")
 * ORM\Entity
 */
class CLpCategoryRelUserGroup
{
    use CourseTrait;
    use SessionTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CLpCategory")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="iid")
     */
    protected CLpCategory $category;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true)
     */
    protected ?Session $session = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false)
     */
    protected Course $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Usergroup")
     * @ORM\JoinColumn(name="usergroup_id", referencedColumnName="id", nullable=true)
     */
    protected ?Usergroup $userGroup = null;

    /**
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected DateTime $createdAt;

    public function __construct()
    {
    }
}
