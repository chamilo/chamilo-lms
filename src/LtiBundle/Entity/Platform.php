<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\LtiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Platform.
 *
 * @package Chamilo\LtiBundle\Entity
 *
 * @ORM\Table(name="lti_platform")
 * @ORM\Entity()
 */
class Platform
{
    /**
     * @var string
     *
     * @ORM\Column(name="public_key", type="text")
     */
    public string $publicKey;
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    protected int $id;
    /**
     * @var string
     *
     * @ORM\Column(name="kid", type="string")
     */
    private string $kid;
    /**
     * @var string
     *
     * @ORM\Column(name="private_key", type="text")
     */
    private string $privateKey;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getKid(): string
    {
        return $this->kid;
    }

    public function setKid(string $kid): static
    {
        $this->kid = $kid;

        return $this;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function setPrivateKey(string $privateKey): static
    {
        $this->privateKey = $privateKey;

        return $this;
    }
}
