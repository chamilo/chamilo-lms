<?php

namespace PhpCoveralls\Component\System;

/**
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class SystemCommandExecutor implements SystemCommandExecutorInterface
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
    public function execute($command)
    {
        exec($command, $result, $returnValue);

        if ($returnValue === 0) {
            return $result;
        }

        throw new \RuntimeException(sprintf('Failed to execute command: %s', $command), $returnValue);
    }
}
