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
     * {@inheritdoc}
     */
    public function getTransportOptions()
    {
        $this->initialize();

        return parent::getTransportOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetDir()
    {
        $this->initialize();

        return parent::getTargetDir();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtra()
    {
        $this->initialize();

        return parent::getExtra();
    }

    /**
     * {@inheritdoc}
     */
    public function getBinaries()
    {
        $this->initialize();

        return parent::getBinaries();
    }

    /**
     * {@inheritdoc}
     */
    public function getInstallationSource()
    {
        $this->initialize();

        return parent::getInstallationSource();
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceType()
    {
        $this->initialize();

        return parent::getSourceType();
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceUrl()
    {
        $this->initialize();

        return parent::getSourceUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceReference()
    {
        $this->initialize();

        return parent::getSourceReference();
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceMirrors()
    {
        $this->initialize();

        return parent::getSourceMirrors();
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceUrls()
    {
        $this->initialize();

        return parent::getSourceUrls();
    }

    /**
     * {@inheritdoc}
     */
    public function getDistType()
    {
        $this->initialize();

        return parent::getDistType();
    }

    /**
     * {@inheritdoc}
     */
    public function getDistUrl()
    {
        $this->initialize();

        return parent::getDistUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function getDistReference()
    {
        $this->initialize();

        return parent::getDistReference();
    }

    /**
     * {@inheritdoc}
     */
    public function getDistSha1Checksum()
    {
        $this->initialize();

        return parent::getDistSha1Checksum();
    }

    /**
     * {@inheritdoc}
     */
    public function getDistMirrors()
    {
        $this->initialize();

        return parent::getDistMirrors();
    }

    /**
     * {@inheritdoc}
     */
    public function getDistUrls()
    {
        $this->initialize();

        return parent::getDistUrls();
    }

    /**
     * {@inheritdoc}
     */
    public function getReleaseDate()
    {
        $this->initialize();

        return parent::getReleaseDate();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequires()
    {
        $this->initialize();

        return parent::getRequires();
    }

    /**
     * {@inheritdoc}
     */
    public function getConflicts()
    {
        $this->initialize();

        return parent::getConflicts();
    }

    /**
     * {@inheritdoc}
     */
    public function getDevRequires()
    {
        $this->initialize();

        return parent::getDevRequires();
    }

    /**
     * {@inheritdoc}
     */
    public function getSuggests()
    {
        $this->initialize();

        return parent::getSuggests();
    }
}
