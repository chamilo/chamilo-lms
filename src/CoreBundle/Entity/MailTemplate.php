<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * MailTemplate.
 *
 * @ORM\Table(name="mail_template")
 * @ORM\Entity
 */
class MailTemplate
{
    use TimestampableEntity;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var bool
     *
     * @ORM\Column(name="name", type="string", nullable=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="text", nullable=true)
     */
    protected $template;

    /**
     * @var bool
     *
     * @ORM\Column(name="type", type="string", nullable=false)
     */
    protected $type;

    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float", nullable=true)
     */
    protected $authorId;

    /**
     * @var int
     *
     * @ORM\Column(name="result_id", type="integer", nullable=false)
     */
    protected $urlId;

    /**
     * @var bool
     *
     * @ORM\Column(name="default_template", type="boolean", nullable=false)
     */
    protected $defaultTemplate;

    /**
     * @var bool
     *
     * @ORM\Column(name="`system`", type="integer", nullable=false, options={"default":0})
     */
    protected $system;
}
