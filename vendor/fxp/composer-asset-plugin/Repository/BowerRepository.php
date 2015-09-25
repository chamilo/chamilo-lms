<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Repository;

/**
 * Bower repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class BowerRepository extends AbstractAssetsRepository
{
    /**
     * {@inheritDoc}
     */
    protected function getType()
    {
        return 'bower';
    }

    /**
     * {@inheritDoc}
     */
    protected function getUrl()
    {
        return 'https://bower.herokuapp.com/packages';
    }

    /**
     * {@inheritDoc}
     */
    protected function getPackageUrl()
    {
        return $this->canonicalizeUrl($this->baseUrl.'/%package%');
    }

    /**
     * {@inheritDoc}
     */
    protected function getSearchUrl()
    {
        return $this->canonicalizeUrl($this->baseUrl.'/search/%query%');
    }

    /**
     * {@inheritDoc}
     */
    protected function createVcsRepositoryConfig(array $data, $registryName = null)
    {
        return array(
            'type' => $this->assetType->getName().'-vcs',
            'url' => $data['url'],
            'name' => $registryName,
        );
    }
}
