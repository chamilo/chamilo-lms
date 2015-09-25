<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\EasyExtendsBundle\Generator;

use Symfony\Component\Console\Output\OutputInterface;
use Sonata\EasyExtendsBundle\Bundle\BundleMetadata;

class SerializerGenerator implements GeneratorInterface
{
    protected $entitySerializerTemplate;
    protected $documentSerializerTemplate;

    public function __construct()
    {
        $this->entitySerializerTemplate   = file_get_contents(__DIR__.'/../Resources/skeleton/serializer/entity.mustache');
        $this->documentSerializerTemplate = file_get_contents(__DIR__.'/../Resources/skeleton/serializer/document.mustache');
    }

    /**
     * @param OutputInterface $output
     * @param BundleMetadata  $bundleMetadata
     */
    public function generate(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $this->generateOrmSerializer($output, $bundleMetadata);
        $this->generateOdmSerializer($output, $bundleMetadata);
        $this->generatePhpcrSerializer($output, $bundleMetadata);
    }

    protected function generateOrmSerializer(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $names = $bundleMetadata->getOrmMetadata()->getEntityNames();

        if (is_array($names) && count($names) > 0) {
            $output->writeln(' - Generating ORM serializer files');

            foreach ($names as $name) {
                $destFile  = sprintf('%s/Entity.%s.xml', $bundleMetadata->getOrmMetadata()->getExtendedSerializerDirectory(), $name);

                $this->writeSerializerFile($output, $bundleMetadata, $this->entitySerializerTemplate, $destFile, $name);
            }
        }
    }

    protected function generateOdmSerializer(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $names = $bundleMetadata->getOdmMetadata()->getDocumentNames();

        if (is_array($names) && count($names) > 0) {
            $output->writeln(' - Generating ODM serializer files');

            foreach ($names as $name) {
                $destFile  = sprintf('%s/Document.%s.xml', $bundleMetadata->getOdmMetadata()->getExtendedSerializerDirectory(), $name);

                $this->writeSerializerFile($output, $bundleMetadata, $this->documentSerializerTemplate, $destFile, $name);
            }
        }
    }

    protected function generatePhpcrSerializer(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $names = $bundleMetadata->getPhpcrMetadata()->getDocumentNames();

        if (is_array($names) && count($names) > 0) {
            $output->writeln(' - Generating PHPCR serializer files');

            foreach ($names as $name) {
                $destFile  = sprintf('%s/Document.%s.xml', $bundleMetadata->getPhpcrMetadata()->getExtendedSerializerDirectory(), $name);

                $this->writeSerializerFile($output, $bundleMetadata, $this->documentSerializerTemplate, $destFile, $name);
            }
        }
    }

    protected function writeSerializerFile(OutputInterface $output, BundleMetadata $bundleMetadata, $template, $destFile, $name)
    {
        if (is_file($destFile)) {
            $output->writeln(sprintf('   ~ <info>%s</info>', $name));
        } else {
            $output->writeln(sprintf('   + <info>%s</info>', $name));

            $string = Mustache::replace($template, array(
                'name'      => $name,
                'namespace' => $bundleMetadata->getExtendedNamespace(),
                'root_name' => strtolower(preg_replace('/[A-Z]/', '_\\0', $name)),
            ));

            file_put_contents($destFile, $string);
        }
    }
}
