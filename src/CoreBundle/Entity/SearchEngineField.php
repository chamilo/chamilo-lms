<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SearchEngineField.
 */
#[ORM\Table(name: 'search_engine_field')]
#[ORM\UniqueConstraint(name: 'unique_specific_field__code', columns: ['code'])]
#[ORM\Entity]
class SearchEngineField
{
    #[ORM\Column(name: 'code', type: 'string', length: 1, nullable: false)]
    protected string $code;

    #[ORM\Column(name: 'title', type: 'string', length: 200, nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
