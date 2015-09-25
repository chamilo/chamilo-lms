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

use Symfony\Component\Finder\Finder;

class OdmMetadata
{
    protected $mappingDocumentDirectory;
    protected $extendedMappingDocumentDirectory;
    protected $documentDirectory;
    protected $extendedDocumentDirectory;
    protected $extendedSerializerDirectory;

    public function __construct(BundleMetadata $bundleMetadata)
    {
        $this->mappingDocumentDirectory           = sprintf('%s/Resources/config/doctrine/', $bundleMetadata->getBundle()->getPath());
        $this->extendedMappingDocumentDirectory   = sprintf('%s/Resources/config/doctrine/', $bundleMetadata->getExtendedDirectory());
        $this->documentDirectory                  = sprintf('%s/Document', $bundleMetadata->getBundle()->getPath());
        $this->extendedDocumentDirectory          = sprintf('%s/Document', $bundleMetadata->getExtendedDirectory());
        $this->extendedSerializerDirectory        = sprintf('%s/Resources/config/serializer', $bundleMetadata->getExtendedDirectory());
    }

    public function getMappingDocumentDirectory()
    {
        return $this->mappingDocumentDirectory;
    }

    public function getExtendedMappingDocumentDirectory()
    {
        return $this->extendedMappingDocumentDirectory;
    }

    public function getDocumentDirectory()
    {
        return $this->documentDirectory;
    }

    public function getExtendedDocumentDirectory()
    {
        return $this->extendedDocumentDirectory;
    }

    public function getExtendedSerializerDirectory()
    {
        return $this->extendedSerializerDirectory;
    }

    public function getDocumentMappingFiles()
    {
        try {
            $f = new Finder;
            $f->name('*.mongodb.xml.skeleton');
            $f->in($this->getMappingDocumentDirectory());

            return $f->getIterator();
        } catch (\Exception $e) {
            return array();
        }
    }

    public function getDocumentNames()
    {
        $names = array();

        try {
            $f = new Finder;
            $f->name('*.mongodb.xml.skeleton');
            $f->in($this->getMappingDocumentDirectory());

            foreach ($f->getIterator() as $file) {
                $name = explode('.', basename($file));
                $names[] = $name[0];
            }

        } catch (\Exception $e) {

        }

        return $names;
    }

    public function getRepositoryFiles()
    {
        try {
            $f = new Finder;
            $f->name('*Repository.php');
            $f->in($this->getDocumentDirectory());

            return $f->getIterator();
        } catch (\Exception $e) {
            return array();
        }
    }
}
