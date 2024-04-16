<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'mail_template')]
#[ORM\Entity]
class MailTemplate
{
    use TimestampableEntity;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'title', type: 'string', nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'template', type: 'text', nullable: true)]
    protected ?string $template = null;

    #[ORM\Column(name: 'type', type: 'string', nullable: false)]
    protected string $type;

    #[ORM\ManyToOne(targetEntity: "User")]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $author = null;

    #[ORM\ManyToOne(targetEntity: "AccessUrl")]
    #[ORM\JoinColumn(name: 'url_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?AccessUrl $url = null;

    #[ORM\Column(name: 'default_template', type: 'boolean', nullable: false)]
    protected bool $defaultTemplate;

    #[ORM\Column(name: '`system`', type: 'integer', nullable: false, options: ['default' => 0])]
    protected int $system;
}
