<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * CStudentPublicationRelUser.
 *
 * @ORM\Table(
 *  name="c_student_publication_rel_user",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="work", columns={"work_id"}),
 *      @ORM\Index(name="user", columns={"user_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CStudentPublicationRelUser
{
    use UserTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="work_id", type="integer", nullable=false)
     */
    protected $workId;

    /**
     * @var User
     * @ORM\ManyToOne (
     *    targetEntity="Chamilo\CoreBundle\Entity\User",
     *    inversedBy="cStudentPublicationRelUsers"
     * )
     * @ORM\JoinColumn(
     *    name="user_id",
     *    referencedColumnName="id",
     *    onDelete="CASCADE"
     * )
     */
    protected $user;

    /**
     * Set workId.
     *
     * @param int $workId
     *
     * @return CStudentPublicationRelUser
     */
    public function setWorkId($workId)
    {
        $this->workId = $workId;

        return $this;
    }

    /**
     * Get workId.
     *
     * @return int
     */
    public function getWorkId()
    {
        return $this->workId;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CStudentPublicationRelUser
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
