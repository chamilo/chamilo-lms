<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Package;

/**
 * The lazy loading complete package.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class LazyCompletePackage extends AbstractLazyCompletePackage implements LazyPackageInterface
{
    /**
     * {@inheritDoc}
     */
    public function getTransportOptions()
    {
        $this->initialize();

        return parent::getTransportOptions();
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetDir()
    {
        $this->initialize();

        return parent::getTargetDir();
    }

    /**
     * {@inheritDoc}
     */
    public function getExtra()
    {
        $this->initialize();

        return parent::getExtra();
    }

    /**
     * {@inheritDoc}
     */
    public function getBinaries()
    {
        $this->initialize();

        return parent::getBinaries();
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallationSource()
    {
        $this->initialize();

        return parent::getInstallationSource();
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceType()
    {
        $this->initialize();

        return parent::getSourceType();
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceUrl()
    {
        $this->initialize();

        return parent::getSourceUrl();
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceReference()
    {
        $this->initialize();

        return parent::getSourceReference();
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceMirrors()
    {
        $this->initialize();

        return parent::getSourceMirrors();
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceUrls()
    {
        $this->initialize();

        return parent::getSourceUrls();
    }

    /**
     * {@inheritDoc}
     */
    public function getDistType()
    {
        $this->initialize();

        return parent::getDistType();
    }

    /**
     * {@inheritDoc}
     */
    public function getDistUrl()
    {
        $this->initialize();

        return parent::getDistUrl();
    }

    /**
     * {@inheritDoc}
     */
    public function getDistReference()
    {
        $this->initialize();

        return parent::getDistReference();
    }

    /**
     * {@inheritDoc}
     */
    public function getDistSha1Checksum()
    {
        $this->initialize();

        return parent::getDistSha1Checksum();
    }

    /**
     * {@inheritDoc}
     */
    public function getDistMirrors()
    {
        $this->initialize();

        return parent::getDistMirrors();
    }

    /**
     * {@inheritDoc}
     */
    public function getDistUrls()
    {
        $this->initialize();

        return parent::getDistUrls();
    }

    /**
     * {@inheritDoc}
     */
    public function getReleaseDate()
    {
        $this->initialize();

        return parent::getReleaseDate();
    }

    /**
     * {@inheritDoc}
     */
    public function getRequires()
    {
        $this->initialize();

        return parent::getRequires();
    }

    /**
     * {@inheritDoc}
     */
    public function getConflicts()
    {
        $this->initialize();

        return parent::getConflicts();
    }

    /**
     * {@inheritDoc}
     */
    public function getDevRequires()
    {
        $this->initialize();

        return parent::getDevRequires();
    }

    /**
     * {@inheritDoc}
     */
    public function getSuggests()
    {
        $this->initialize();

        return parent::getSuggests();
    }
}
