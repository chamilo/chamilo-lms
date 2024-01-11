<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Resource;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'resource_user_tag')]
#[ORM\Entity]
class ResourceUserTag
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $user = null;

    #[ORM\ManyToOne(targetEntity: ResourceTag::class)]
    #[ORM\JoinColumn(name: 'tag_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?ResourceTag $tag = null;
}
