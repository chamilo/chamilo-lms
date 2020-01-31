<?php

namespace PhpCoveralls\Component\System;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
interface SystemCommandExecutorInterface
{
    /**
     * Execute command.
     *
     * @param string $command
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    public function execute($command);
}
