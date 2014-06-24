<?php

namespace FOS\MessageBundle\ModelManager;

/**
 * Abstract Thread Manager implementation which can be used as base class by your
 * concrete manager.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
abstract class ThreadManager implements ThreadManagerInterface
{
    /**
     * Creates an empty comment thread instance
     *
     * @return ThreadInterface
     */
    public function createThread()
    {
        $class = $this->getClass();
        $commentThread = new $class;

        return $commentThread;
    }
}
