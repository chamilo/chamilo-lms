<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\ExternalNotificationConnect\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'plugin_ext_notif_conn_access_token')]
#[ORM\Entity]
class AccessToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: "id", type: "integer")]
    private ?int $id;

    #[ORM\Column(name: "access_token", type: "text")]
    private string $token;

    #[ORM\Column(name: 'is_valid', type: 'boolean')]
    private bool $isValid;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): AccessToken
    {
        $this->id = $id;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): AccessToken
    {
        $this->token = $token;

        return $this;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): AccessToken
    {
        $this->isValid = $isValid;

        return $this;
    }
}
