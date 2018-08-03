<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CDropboxPerson.
 *
 * @ORM\Table(
 *  name="c_dropbox_person",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="user", columns={"user_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CDropboxPerson
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
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="file_id", type="integer")
     */
    protected $fileId;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    protected $userId;

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CDropboxPerson
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
     * Set fileId.
     *
     * @param int $fileId
     *
     * @return CDropboxPerson
     */
    public function setFileId($fileId)
    {
        $this->fileId = $fileId;

        return $this;
    }

    /**
     * Get fileId.
     *
     * @return int
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return CDropboxPerson
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
}
