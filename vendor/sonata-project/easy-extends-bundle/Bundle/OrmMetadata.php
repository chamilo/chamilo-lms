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

class OrmMetadata
{
    protected $mappingEntityDirectory;
    protected $extendedMappingEntityDirectory;
    protected $entityDirectory;
    protected $extendedEntityDirectory;
    protected $extendedSerializerDirectory;

    public function __construct(BundleMetadata $bundleMetadata)
    {
        $this->mappingEntityDirectory           = sprintf('%s/Resources/config/doctrine/', $bundleMetadata->getBundle()->getPath());
        $this->extendedMappingEntityDirectory   = sprintf('%s/Resources/config/doctrine/', $bundleMetadata->getExtendedDirectory());
        $this->entityDirectory                  = sprintf('%s/Entity', $bundleMetadata->getBundle()->getPath());
        $this->extendedEntityDirectory          = sprintf('%s/Entity', $bundleMetadata->getExtendedDirectory());
        $this->extendedSerializerDirectory      = sprintf('%s/Resources/config/serializer', $bundleMetadata->getExtendedDirectory());
    }

    public function getMappingEntityDirectory()
    {
        return $this->mappingEntityDirectory;
    }

    public function getExtendedMappingEntityDirectory()
    {
        return $this->extendedMappingEntityDirectory;
    }

    public function getEntityDirectory()
    {
        return $this->entityDirectory;
    }

    public function getExtendedEntityDirectory()
    {
        return $this->extendedEntityDirectory;
    }

    public function getExtendedSerializerDirectory()
    {
        return $this->extendedSerializerDirectory;
    }

    public function getEntityMappingFiles()
    {
        try {
            $f = new Finder;
            $f->name('*.orm.xml.skeleton');
            $f->name('*.orm.yml.skeleton');
            $f->in($this->getMappingEntityDirectory());

            return $f->getIterator();
        } catch (\Exception $e) {
            return array();
        }
    }

    public function getEntityNames()
    {
        $names = array();

        try {
            $f = new Finder;
            $f->name('*.orm.xml.skeleton');
            $f->name('*.orm.yml.skeleton');
            $f->in($this->getMappingEntityDirectory());

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
            $f->in($this->getEntityDirectory());

            return $f->getIterator();
        } catch (\Exception $e) {
            return array();
        }
    }
}
