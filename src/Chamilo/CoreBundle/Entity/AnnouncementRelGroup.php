<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AnnouncementRelGroup.
 *
 * @ORM\Table(name="announcement_rel_group")
 * @ORM\Entity
 */
class AnnouncementRelGroup
{
    /**
     * @var int
     *
     * @ORM\Column(name="group_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $groupId;

    /**
     * @var int
     *
     * @ORM\Column(name="announcement_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $announcementId;

    /**
     * Set groupId.
     *
     * @param int $groupId
     *
     * @return AnnouncementRelGroup
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId.
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set announcementId.
     *
     * @param int $announcementId
     *
     * @return AnnouncementRelGroup
     */
    public function setAnnouncementId($announcementId)
    {
        $this->announcementId = $announcementId;

        return $this;
    }

    /**
     * Get announcementId.
     *
     * @return int
     */
    public function getAnnouncementId()
    {
        return $this->announcementId;
    }
}
