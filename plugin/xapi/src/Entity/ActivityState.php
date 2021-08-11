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

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ActivityState
    {
        $this->id = $id;

        return $this;
    }

    public function getStateId(): string
    {
        return $this->stateId;
    }

    public function setStateId(string $stateId): ActivityState
    {
        $this->stateId = $stateId;

        return $this;
    }

    public function getActivityId(): string
    {
        return $this->activityId;
    }

    public function setActivityId(string $activityId): ActivityState
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function getAgent(): array
    {
        return $this->agent;
    }

    public function setAgent(array $agent): ActivityState
    {
        $this->agent = $agent;

        return $this;
    }

    public function getDocumentData(): array
    {
        return $this->documentData;
    }

    public function setDocumentData(array $documentData): ActivityState
    {
        $this->documentData = $documentData;

        return $this;
    }
}
