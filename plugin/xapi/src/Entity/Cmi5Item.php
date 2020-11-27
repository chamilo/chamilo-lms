<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\XApi;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Cmi5Item.
 *
 * @package Chamilo\PluginBundle\Entity\XApi
 *
 * @ORM\Table(name="xapi_cmi5_item")
 * @ORM\Entity()
 */
class Cmi5Item
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="activity_id", type="string")
     */
    private $activityId;
    /**
     * @var string
     *
     * @ORM\Column(name="activity_type", type="string")
     */
    private $activityType;
    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string")
     */
    private $url;

    public function getId(): int
    {
        return $this->id;
    }

    public function getActivityId(): string
    {
        return $this->activityId;
    }

    public function setActivityId(string $activityId): Cmi5Item
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function getActivityType(): string
    {
        return $this->activityType;
    }

    public function setActivityType(string $activityType): Cmi5Item
    {
        $this->activityType = $activityType;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): Cmi5Item
    {
        $this->url = $url;

        return $this;
    }
}
