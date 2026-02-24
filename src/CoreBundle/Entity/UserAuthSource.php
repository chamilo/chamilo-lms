<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\UserAuthSourceRepository;
use Doctrine\ORM\Mapping as ORM;

// agregar url_id priority
#[ORM\Entity(repositoryClass: UserAuthSourceRepository::class)]
class UserAuthSource
{
    public const PLATFORM = 'platform';
    public const CAS = 'cas';
    public const LDAP = 'extldap';
    public const AZURE = 'azure';
    public const FACEBOOK = 'facebook';
    public const KEYCLOAK = 'keycloak';
    public const OAUTH2 = 'oauth2';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $authentication = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AccessUrl $url = null;

    #[ORM\ManyToOne(inversedBy: 'authSources')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthentication(): ?string
    {
        return $this->authentication;
    }

    public function setAuthentication(string $authentication): static
    {
        $this->authentication = $authentication;

        return $this;
    }

    public function getUrl(): ?AccessUrl
    {
        return $this->url;
    }

    public function setUrl(?AccessUrl $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
