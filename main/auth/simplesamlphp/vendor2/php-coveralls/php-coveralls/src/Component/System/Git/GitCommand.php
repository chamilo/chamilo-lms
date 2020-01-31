<?php

namespace PhpCoveralls\Component\System\Git;

use PhpCoveralls\Component\System\SystemCommandExecutor;
use PhpCoveralls\Component\System\SystemCommandExecutorInterface;

/**
 * Git command.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class GitCommand
{
    /**
     * @var SystemCommandExecutorInterface
     */
    private $executor;

    public function __construct(SystemCommandExecutorInterface $executor = null)
    {
        $this->executor = $executor ? $executor : new SystemCommandExecutor();
    }

    /**
     * Return branch names.
     *
     * @return array
     */
    public function getBranches()
    {
        return $this->executor->execute('git branch');
    }

    /**
     * Return HEAD commit.
     *
     * @return array
     */
    public function getHeadCommit()
    {
        return $this->executor->execute("git log -1 --pretty=format:'%H%n%aN%n%ae%n%cN%n%ce%n%s'");
    }

    /**
     * Return remote repositories.
     *
     * @return array
     */
    public function getRemotes()
    {
        return $this->executor->execute('git remote -v');
    }
}
