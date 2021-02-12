<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\XApi;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class ActivityProfile.
 *
 * @package Chamilo\PluginBundle\Entity\XApi
 *
 * @ORM\Table(name="xapi_activity_profile")
 * @ORM\Entity()
 */
class ActivityProfile
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="profile_id", type="string")
     */
    private $profileId;
    /**
     * @var string
     *
     * @ORM\Column(name="activity_id", type="string")
     */
    private $activityId;
    /**
     * @var array
     *
     * @ORM\Column(name="document_data", type="json")
     */
    private $documentData;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return ActivityProfile
     */
    public function setId(int $id): ActivityProfile
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getProfileId(): string
    {
        return $this->profileId;
    }

    /**
     * @param string $profileId
     *
     * @return ActivityProfile
     */
    public function setProfileId(string $profileId): ActivityProfile
    {
        $this->profileId = $profileId;

        return $this;
    }

    /**
     * @return string
     */
    public function getActivityId(): string
    {
        return $this->activityId;
    }

    /**
     * @param string $activityId
     *
     * @return ActivityProfile
     */
    public function setActivityId(string $activityId): ActivityProfile
    {
        $this->activityId = $activityId;

        return $this;
    }

    /**
     * @return array
     */
    public function getDocumentData(): array
    {
        return $this->documentData;
    }

    /**
     * @param array $documentData
     *
     * @return ActivityProfile
     */
    public function setDocumentData(array $documentData): ActivityProfile
    {
        $this->documentData = $documentData;

        return $this;
    }
}
