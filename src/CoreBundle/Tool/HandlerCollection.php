<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use InvalidArgumentException;

class HandlerCollection
{
    /**
     * @var AbstractTool[]|iterable
     */
    private $handlers;

    public function __construct(iterable $handlers)
    {
        $this->handlers = $handlers;
    }

    public function getHandler(string $title): AbstractTool
    {
        foreach ($this->handlers as $handler) {
            if ($title === $handler->getTitle()) {
                return $handler;
            }
        }

        throw new InvalidArgumentException(\sprintf('Cannot handle tool "%s"', $title));
    }

    /**
     * @return AbstractTool[]|iterable
     */
    public function getCollection()
    {
        return $this->handlers;
    }
}
