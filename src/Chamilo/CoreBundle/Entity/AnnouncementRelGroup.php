<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AnnouncementRelGroup
 *
 * @ORM\Table(name="announcement_rel_group")
 * @ORM\Entity
 */
class AnnouncementRelGroup
{
    /**
     * @var integer
     *
     * @ORM\Column(name="group_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $groupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="announcement_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $announcementId;

    /**
     * Set groupId
     *
     * @param integer $groupId
     * @return AnnouncementRelGroup
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId
     *
     * @return integer
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set announcementId
     *
     * @param integer $announcementId
     * @return AnnouncementRelGroup
     */
    public function setAnnouncementId($announcementId)
    {
        $this->announcementId = $announcementId;

        return $this;
    }

    /**
     * Get announcementId
     *
     * @return integer
     */
    public function getAnnouncementId()
    {
        return $this->announcementId;
    }
}
