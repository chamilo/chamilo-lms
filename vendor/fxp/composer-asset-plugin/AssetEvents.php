<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin;

/**
 * Asset events.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class AssetEvents
{
    /**
     * The ADD_VCS_REPOSITORIES event occurs as a asset VCS repository
     * that creates new VCS repositories required by dependencies.
     *
     * The event listener method receives a
     * Fxp\Composer\AssetPlugin\Event\VcsRepositoryEvent instance.
     *
     * @var string
     */
    const ADD_VCS_REPOSITORIES = 'fxp.composer.add.vcs.repositories';
}
