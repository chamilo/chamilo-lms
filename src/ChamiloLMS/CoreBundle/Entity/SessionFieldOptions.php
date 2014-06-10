<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SessionFieldOptions
 *
 * @ORM\Table(name="session_field_options", indexes={@ORM\Index(name="idx_session_field_options_field_id", columns={"field_id"})})
 * @ORM\Entity
 */
class SessionFieldOptions
{
    /**
     * @var integer
     *
     * @ORM\Column(name="field_id", type="integer", nullable=false)
     */
    private $fieldId;

    /**
     * @var string
     *
     * @ORM\Column(name="option_value", type="text", nullable=true)
     */
    private $optionValue;

    /**
     * @var string
     *
     * @ORM\Column(name="option_display_text", type="string", length=255, nullable=true)
     */
    private $optionDisplayText;

    /**
     * @var integer
     *
     * @ORM\Column(name="option_order", type="integer", nullable=true)
     */
    private $optionOrder;

    /**
     * @var integer
     *
     * @ORM\Column(name="priority", type="integer", nullable=true)
     */
    private $priority;

    /**
     * @var string
     *
     * @ORM\Column(name="priority_message", type="string", length=255, nullable=true)
     */
    private $priorityMessage;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tms", type="datetime", nullable=false)
     */
    private $tms;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
