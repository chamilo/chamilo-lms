<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\AzureActiveDirectory;

use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @package Chamilo\PluginBundle\Entity\AzureActiveDirectory
 *
 * @ORM\Table(name="azure_ad_sync_state")
 * @ORM\Entity()
 */
class AzureSyncState
{
    use TimestampableTypedEntity;

    public const USERS_DATALINK = 'users_datalink';
    public const USERGROUPS_DATALINK = 'usergroups_datalink';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private int $id = 0;

    /**
     * @ORM\Column(name="title", type="string")
     */
    private string $title;

    /**
     * @ORM\Column(name="value", type="text")
     */
    private string $value;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): AzureSyncState
    {
        $this->title = $title;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): AzureSyncState
    {
        $this->value = $value;

        return $this;
    }
}
