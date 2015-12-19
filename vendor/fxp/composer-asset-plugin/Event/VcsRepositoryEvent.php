<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Event;

use Composer\EventDispatcher\Event;

/**
 * The VCS repository event.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class VcsRepositoryEvent extends Event
{
    /**
     * @var array
     */
    protected $repositories;

    /**
     * Constructor.
     *
     * @param string $name  The event name
     * @param array  $repos The list of vcs repositories config
     */
    public function __construct($name, array $repos)
    {
        parent::__construct($name);

        $this->repositories = $repos;
    }

    /**
     * Gets the list of vcs repositories config.
     *
     * @return array The list of vcs repositories config
     */
    public function getRepositories()
    {
        return $this->repositories;
    }
}
