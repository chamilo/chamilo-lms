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

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ActivityProfile
    {
        $this->id = $id;

        return $this;
    }

    public function getProfileId(): string
    {
        return $this->profileId;
    }

    public function setProfileId(string $profileId): ActivityProfile
    {
        $this->profileId = $profileId;

        return $this;
    }

    public function getActivityId(): string
    {
        return $this->activityId;
    }

    public function setActivityId(string $activityId): ActivityProfile
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function getDocumentData(): array
    {
        return $this->documentData;
    }

    public function setDocumentData(array $documentData): ActivityProfile
    {
        $this->documentData = $documentData;

        return $this;
    }
}
