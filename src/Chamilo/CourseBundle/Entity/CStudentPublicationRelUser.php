<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

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
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

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
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

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
     * Set userId.
     *
     * @param int $userId
     *
     * @return CStudentPublicationRelUser
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
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
