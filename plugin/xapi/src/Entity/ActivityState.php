<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\XApi;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class ActivityState.
 *
 * @package Chamilo\PluginBundle\Entity\XApi
 *
 * @ORM\Table(name="xapi_activity_state")
 * @ORM\Entity()
 */
class ActivityState
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
     * @ORM\Column(name="state_id", type="string")
     */
    private $stateId;
    /**
     * @var string
     *
     * @ORM\Column(name="activity_id", type="string")
     */
    private $activityId;
    /**
     * @var array
     *
     * @ORM\Column(name="agent", type="json")
     */
    private $agent;
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
     * @return ActivityState
     */
    public function setId(int $id): ActivityState
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getStateId(): string
    {
        return $this->stateId;
    }

    /**
     * @param string $stateId
     *
     * @return ActivityState
     */
    public function setStateId(string $stateId): ActivityState
    {
        $this->stateId = $stateId;

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
     * @return ActivityState
     */
    public function setActivityId(string $activityId): ActivityState
    {
        $this->activityId = $activityId;

        return $this;
    }

    /**
     * @return array
     */
    public function getAgent(): array
    {
        return $this->agent;
    }

    /**
     * @param array $agent
     *
     * @return ActivityState
     */
    public function setAgent(array $agent): ActivityState
    {
        $this->agent = $agent;

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
     * @return ActivityState
     */
    public function setDocumentData(array $documentData): ActivityState
    {
        $this->documentData = $documentData;

        return $this;
    }
}
