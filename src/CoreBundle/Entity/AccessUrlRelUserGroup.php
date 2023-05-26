<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccessUrlRelUser.
 */
#[ORM\Table(name: 'access_url_rel_usergroup')]
#[ORM\Entity]
class AccessUrlRelUserGroup implements EntityAccessUrlInterface
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class)]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id')]
    protected ?AccessUrl $url;

    #[ORM\ManyToOne(targetEntity: Usergroup::class, inversedBy: 'urls', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'usergroup_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Usergroup $userGroup;

    /**
     * @return int
     */
    public function getId()
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

    public function getUserGroup(): Usergroup
    {
        return $this->userGroup;
    }

    public function setUserGroup(Usergroup $userGroup): self
    {
        $this->userGroup = $userGroup;

        return $this;
    }
}
