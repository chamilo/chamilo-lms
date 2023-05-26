<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Resource;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'resource_tag')]
#[ORM\Entity]
class ResourceTag
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', nullable: false)]
    protected string $name;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $author = null;
}
