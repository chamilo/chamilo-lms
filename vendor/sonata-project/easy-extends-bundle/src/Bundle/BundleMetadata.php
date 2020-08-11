<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\EasyExtendsBundle\Bundle;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class BundleMetadata
{
    /**
     * @var BundleInterface
     */
    protected $bundle;

    /**
     * @var string|bool
     */
    protected $vendor = false;

    /**
     * @var bool
     */
    protected $valid = false;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $extendedDirectory = false;

    /**
     * @var bool
     */
    protected $extendedNamespace = false;

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @var OrmMetadata
     */
    protected $ormMetadata = null;

    /**
     * @var OdmMetadata
     */
    protected $odmMetadata = null;

    /**
     * @var PhpcrMetadata
     */
    protected $phpcrMetadata = null;

    /**
     * @var string
     */
    private $application;

    /**
     * @param BundleInterface $bundle
     * @param array           $configuration
     */
    public function __construct(BundleInterface $bundle, array $configuration = [])
    {
        $this->bundle = $bundle;
        $this->configuration = $configuration;

        $this->buildInformation();
    }

    /**
     * @return bool
     */
    public function isExtendable()
    {
        // does not extends Application bundle ...
        return !(
            0 === strpos($this->getClass(), $this->configuration['namespace'])
            || 0 === strpos($this->getClass(), 'Symfony')
        );
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return get_class($this->bundle);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @return string
     */
    public function getExtendedDirectory()
    {
        return $this->extendedDirectory;
    }

    /**
     * @return string
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @return string
     */
    public function getExtendedNamespace()
    {
        return $this->extendedNamespace;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * return the bundle name.
     *
     * @return string return the bundle name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return BundleInterface
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * @return OdmMetadata
     */
    public function getOdmMetadata()
    {
        return $this->odmMetadata;
    }

    /**
     * @return OrmMetadata
     */
    public function getOrmMetadata()
    {
        return $this->ormMetadata;
    }

    /**
     * @return PhpcrMetadata
     */
    public function getPhpcrMetadata()
    {
        return $this->phpcrMetadata;
    }

    /**
     * build basic information and check if the bundle respect the following convention
     *   Vendor/BundleNameBundle/VendorBundleNameBundle.
     *
     * if the bundle does not respect this convention then the easy extends command will ignore
     * this bundle
     */
    protected function buildInformation()
    {
        $information = explode('\\', $this->getClass());

        if (!$this->isExtendable()) {
            $this->valid = false;

            return;
        }

        if (3 != count($information)) {
            $this->valid = false;

            return;
        }

        if ($information[0].$information[1] != $information[2]) {
            $this->valid = false;

            return;
        }

        $this->name = $information[count($information) - 1];
        $this->vendor = $information[0];
        $this->namespace = sprintf('%s\\%s', $this->vendor, $information[1]);
        $this->extendedDirectory =
            str_replace(':vendor', $this->vendor, $this->configuration['application_dir']).
            DIRECTORY_SEPARATOR.
            $information[1];
        $this->extendedNamespace = sprintf(
            '%s%s\\%s',
            $this->configuration['namespace_prefix'],
            str_replace(':vendor', $this->vendor, $this->configuration['namespace']),
            $information[1]
        );
        $this->application = explode('\\', $this->configuration['namespace'])[0];
        $this->valid = true;

        $this->ormMetadata = new OrmMetadata($this);
        $this->odmMetadata = new OdmMetadata($this);
        $this->phpcrMetadata = new PhpcrMetadata($this);
    }
}
