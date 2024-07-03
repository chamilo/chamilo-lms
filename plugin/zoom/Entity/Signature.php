<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="plugin_zoom_signature")
 */
class Signature
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var Registrant
     *
     * @ORM\OneToOne(targetEntity="Chamilo\PluginBundle\Zoom\Registrant", inversedBy="signature")
     * @ORM\JoinColumn(name="registrant_id", referencedColumnName="id")
     */
    private $registrant;
    /**
     * @var string
     *
     * @ORM\Column(name="signature", type="text")
     */
    private $file;
    /**
     * @var DateTime
     *
     * @ORM\Column(name="registered_at", type="datetime")
     */
    private $registeredAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getRegistrant(): Registrant
    {
        return $this->registrant;
    }

    public function setRegistrant(Registrant $registrant): Signature
    {
        $this->registrant = $registrant;

        return $this;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function setFile(string $file): Signature
    {
        $this->file = $file;

        return $this;
    }

    public function getRegisteredAt(): DateTime
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(DateTime $registeredAt): Signature
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }
}
