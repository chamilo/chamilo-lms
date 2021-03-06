<?php

declare(strict_types=1);

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
     * @ORM\Column(name="group_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected int $groupId;

    /**
     * @ORM\Column(name="announcement_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected int $announcementId;

    /**
     * Set groupId.
     *
     * @return AnnouncementRelGroup
     */
    public function setGroupId(int $groupId)
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
     * @return AnnouncementRelGroup
     */
    public function setAnnouncementId(int $announcementId)
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
