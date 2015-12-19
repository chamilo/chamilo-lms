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
use Composer\Repository\Vcs\GitBitbucketDriver as BaseGitBitbucketDriver;

/**
 * Git Bitbucket vcs driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class GitBitbucketDriver extends BaseGitBitbucketDriver
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        parent::initialize();

        $this->cache = new Cache($this->io, $this->config->get('cache-repo-dir').'/'.$this->originUrl.'/'.$this->owner.'/'.$this->repository);
    }

    /**
     * {@inheritdoc}
     */
    public function getComposerInformation($identifier)
    {
        return BitbucketUtil::getComposerInformation($this->cache, $this->infoCache, $this->getScheme(), $this->repoConfig, $identifier, $this->owner, $this->repository, $this, 'getContents');
    }
}
