<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Repository\Vcs;

use Composer\Cache;
use Composer\Repository\Vcs\GitDriver as BaseGitDriver;

/**
 * Git vcs driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class GitDriver extends BaseGitDriver
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * {@inheritdoc}
     */
    public function getComposerInformation($identifier)
    {
        $resource = sprintf('%s:%s', escapeshellarg($identifier), $this->repoConfig['filename']);

        return ProcessUtil::getComposerInformation($this->cache, $this->infoCache, $this->repoConfig['asset-type'], $this->process, $identifier, $resource, sprintf('git show %s', $resource), sprintf('git log -1 --format=%%at %s', escapeshellarg($identifier)), $this->repoDir, '@');
    }
}
