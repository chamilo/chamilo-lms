<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

use Symfony\Contracts\EventDispatcher\Event;

abstract class HookEvent extends Event
{
    public const TYPE_NONE = -1;
    public const TYPE_PRE = 0;
    public const TYPE_POST = 1;
    public const TYPE_PRE_POST = 2;

    public function __construct(
        protected array $data = [],
        protected int $type = self::TYPE_NONE
    ) {}

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
