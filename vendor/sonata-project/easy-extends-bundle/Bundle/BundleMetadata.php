<?php
/*
 * This file is part of the Sonata project.
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
    protected $bundle;

    protected $vendor = false;

    protected $valid = false;

    protected $namespace;

    protected $name;

    protected $extendedDirectory = false;

    protected $extendedNamespace = false;

    protected $configuration = array();

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
     * @param BundleInterface $bundle
     * @param array           $configuration
     */
    public function __construct(BundleInterface $bundle, array $configuration = array())
    {
        $this->bundle = $bundle;
        $this->configuration = $configuration;

        $this->buildInformation();
    }

    /**
     * build basic information and check if the bundle respect the following convention
     *   Vendor/BundleNameBundle/VendorBundleNameBundle
     *
     * if the bundle does not respect this convention then the easy extends command will ignore
     * this bundle
     *
     * @return void
     */
    protected function buildInformation()
    {
        $information = explode('\\', $this->getClass());

        if (!$this->isExtendable()) {
            $this->valid = false;

            return;
        }

        if (count($information) != 3) {
            $this->valid = false;

            return;
        }

        if ($information[0].$information[1] != $information[2]) {
            $this->valid = false;

            return;
        }

        $this->name = $information[count($information) - 1];
        $this->vendor = $information[0];
        $this->namespace =  sprintf('%s\%s', $this->vendor, $information[1]);
        $this->extendedDirectory = sprintf('%s/%s/%s', $this->configuration['application_dir'], $this->vendor, $information[1]);
        $this->extendedNamespace = sprintf('Application\\%s\\%s', $this->vendor, $information[1]);
        $this->valid = true;

        $this->ormMetadata = new OrmMetadata($this);
        $this->odmMetadata = new OdmMetadata($this);
        $this->phpcrMetadata = new PhpcrMetadata($this);
    }

    public function isExtendable()
    {
        // does not extends Application bundle ...
        return !(
            strpos($this->getClass(), 'Application') === 0
            || strpos($this->getClass(), 'Symfony') === 0
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
     * @return boolean
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
     * return the bundle name
     *
     * @return string return the bundle name
     */
    public function getName()
    {
        return $this->name;
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
}
