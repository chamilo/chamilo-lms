<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs;

use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Repository\Vcs\VcsDriverInterface;

/**
 * Mock vcs driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class MockVcsDriver implements VcsDriverInterface
{
    /**
     * @var bool
     */
    public static $supported = true;

    /**
     * @var mixed
     */
    public $contents = null;

    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        // no action
    }

    /**
     * {@inheritDoc}
     */
    public function getComposerInformation($identifier)
    {
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function getRootIdentifier()
    {
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function getBranches()
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function getTags()
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function getDist($identifier)
    {
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function getSource($identifier)
    {
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl()
    {
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function hasComposerFile($identifier)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function cleanup()
    {
        // no action
    }

    /**
     * {@inheritDoc}
     */
    public static function supports(IOInterface $io, Config $config, $url, $deep = false)
    {
        return static::$supported;
    }

    /**
     * @return mixed
     */
    protected function getContents()
    {
        return $this->contents;
    }
}
