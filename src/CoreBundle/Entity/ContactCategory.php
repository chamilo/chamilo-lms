<?php
declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Column;

#[Entity]
#[Table(name: "contact_form_contact_category")]
class ContactCategory
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: "integer")]
    private ?int $id = null;

    #[Column(type: "string", length: 255)]
    private string $name;

    #[Column(type: "string", length: 255)]
    private string $email;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }
}
