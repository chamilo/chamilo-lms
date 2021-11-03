<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Table(name="mail_template")
 * @ORM\Entity
 */
class MailTemplate
{
    use TimestampableEntity;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    protected string $name;

    /**
     * @ORM\Column(name="template", type="text", nullable=true)
     */
    protected ?string $template = null;

    /**
     * @ORM\Column(name="type", type="string", nullable=false)
     */
    protected string $type;

    /**
     * @ORM\Column(name="score", type="float", nullable=true)
     */
    protected float $authorId;

    /**
     * @ORM\Column(name="result_id", type="integer", nullable=false)
     */
    protected int $urlId;

    /**
     * @ORM\Column(name="default_template", type="boolean", nullable=false)
     */
    protected bool $defaultTemplate;

    /**
     * @ORM\Column(name="`system`", type="integer", nullable=false, options={"default":0})
     */
    protected bool $system;
}
