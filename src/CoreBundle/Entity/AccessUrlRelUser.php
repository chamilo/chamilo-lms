<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

#[ORM\Table(name: 'access_url_rel_user')]
#[ORM\Index(name: 'idx_access_url_rel_user_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_access_url_rel_user_access_url', columns: ['access_url_id'])]
#[ORM\Index(name: 'idx_access_url_rel_user_access_url_user', columns: ['user_id', 'access_url_id'])]
#[ORM\Entity]
class AccessUrlRelUser implements EntityAccessUrlInterface, Stringable
{
    use UserTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'portals')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class, cascade: ['persist'], inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id')]
    protected ?AccessUrl $url;

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUrl(): ?AccessUrl
    {
        return $this->url;
    }

    public function setUrl(?AccessUrl $url): self
    {
        $this->url = $url;

        return $this;
    }
}
