<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CDropboxPerson.
 */
#[ORM\Table(name: 'c_dropbox_person')]
#[ORM\Index(name: 'course', columns: ['c_id'])]
#[ORM\Index(name: 'user', columns: ['user_id'])]
#[ORM\Entity]
class CDropboxPerson
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[ORM\Column(name: 'c_id', type: 'integer')]
    protected int $cId;

    #[ORM\Column(name: 'file_id', type: 'integer')]
    protected int $fileId;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    protected int $userId;

    /**
     * Set cId.
     *
     * @return CDropboxPerson
     */
    public function setCId(int $cId)
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
     * @return CDropboxPerson
     */
    public function setFileId(int $fileId)
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
     * @return CDropboxPerson
     */
    public function setUserId(int $userId)
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
