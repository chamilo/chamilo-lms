<?php

namespace FOS\MessageBundle\ModelManager;

/**
 * Abstract Message Manager implementation which can be used as base by
 * your concrete manager.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
abstract class MessageManager implements MessageManagerInterface
{
    /**
     * Creates an empty message instance
     *
     * @return MessageInterface
     */
    public function createMessage()
    {
        $class = $this->getClass();
        $message = new $class;

        return $message;
    }
}
