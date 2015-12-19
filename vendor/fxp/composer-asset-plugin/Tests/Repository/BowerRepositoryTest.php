<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Repository;

use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Fxp\Composer\AssetPlugin\Repository\BowerRepository;

/**
 * Tests of Bower repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class BowerRepositoryTest extends AbstractAssetsRepositoryTest
{
    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return 'bower';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRegistry(array $repoConfig, IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null)
    {
        return new BowerRepository($repoConfig, $io, $config, $eventDispatcher);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMockPackageForVcsConfig()
    {
        return array(
            'url' => 'http://foo.tld',
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getMockSearchResult($name = 'mock-package')
    {
        return array(
            array(
                'name' => $name,
            ),
        );
    }
}
