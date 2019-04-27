<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\EasyExtendsBundle\Generator;

use Sonata\EasyExtendsBundle\Bundle\BundleMetadata;
use Symfony\Component\Console\Output\OutputInterface;

class OdmGenerator implements GeneratorInterface
{
    /**
     * @var string
     */
    protected $documentTemplate;

    /**
     * @var string
     */
    protected $documentRepositoryTemplate;

    public function __construct()
    {
        $this->documentTemplate = file_get_contents(__DIR__.'/../Resources/skeleton/odm/document.mustache');
        $this->documentRepositoryTemplate = file_get_contents(__DIR__.'/../Resources/skeleton/odm/repository.mustache');
    }

    /**
     * {@inheritdoc}
     */
    public function generate(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $this->generateMappingDocumentFiles($output, $bundleMetadata);
        $this->generateDocumentFiles($output, $bundleMetadata);
        $this->generateDocumentRepositoryFiles($output, $bundleMetadata);
    }

    /**
     * @return string
     */
    public function getDocumentTemplate()
    {
        return $this->documentTemplate;
    }

    /**
     * @return string
     */
    public function getDocumentRepositoryTemplate()
    {
        return $this->documentRepositoryTemplate;
    }

    /**
     * @param OutputInterface $output
     * @param BundleMetadata  $bundleMetadata
     */
    protected function generateMappingDocumentFiles(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $output->writeln(' - Copy document files');

        $files = $bundleMetadata->getOdmMetadata()->getDocumentMappingFiles();

        foreach ($files as $file) {
            // copy mapping definition
            $fileName = substr($file->getFileName(), 0, strrpos($file->getFileName(), '.'));

            $dest_file = sprintf('%s/%s', $bundleMetadata->getOdmMetadata()->getExtendedMappingDocumentDirectory(), $fileName);
            $src_file = sprintf('%s/%s.skeleton', $bundleMetadata->getOdmMetadata()->getMappingDocumentDirectory(), $fileName);

            if (is_file($dest_file)) {
                $output->writeln(sprintf('   ~ <info>%s</info>', $fileName));
            } else {
                $output->writeln(sprintf('   + <info>%s</info>', $fileName));

                $mappingEntityTemplate = file_get_contents($src_file);

                $string = Mustache::replace($mappingEntityTemplate, [
                    'namespace' => $bundleMetadata->getExtendedNamespace(),
                ]);

                file_put_contents($dest_file, $string);
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param BundleMetadata  $bundleMetadata
     */
    protected function generateDocumentFiles(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $output->writeln(' - Generating document files');

        $names = $bundleMetadata->getOdmMetadata()->getDocumentNames();

        foreach ($names as $name) {
            $extendedName = $name;

            $dest_file = sprintf('%s/%s.php', $bundleMetadata->getOdmMetadata()->getExtendedDocumentDirectory(), $name);
            $src_file = sprintf('%s/%s.php', $bundleMetadata->getOdmMetadata()->getDocumentDirectory(), $extendedName);

            if (!is_file($src_file)) {
                $extendedName = 'Base'.$name;
                $src_file = sprintf('%s/%s.php', $bundleMetadata->getOdmMetadata()->getDocumentDirectory(), $extendedName);

                if (!is_file($src_file)) {
                    $output->writeln(sprintf('   ! <info>%s</info>', $extendedName));

                    continue;
                }
            }

            if (is_file($dest_file)) {
                $output->writeln(sprintf('   ~ <info>%s</info>', $name));
            } else {
                $output->writeln(sprintf('   + <info>%s</info>', $name));

                $string = Mustache::replace($this->getDocumentTemplate(), [
                    'extended_namespace' => $bundleMetadata->getExtendedNamespace(),
                    'name' => $name != $extendedName ? $extendedName : $name,
                    'class' => $name,
                    'extended_name' => $name == $extendedName ? 'Base'.$name : $extendedName,
                    'namespace' => $bundleMetadata->getNamespace(),
                ]);

                file_put_contents($dest_file, $string);
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param BundleMetadata  $bundleMetadata
     */
    protected function generateDocumentRepositoryFiles(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $output->writeln(' - Generating document repository files');

        $names = $bundleMetadata->getOdmMetadata()->getDocumentNames();

        foreach ($names as $name) {
            $dest_file = sprintf('%s/%sRepository.php', $bundleMetadata->getOdmMetadata()->getExtendedDocumentDirectory(), $name);
            $src_file = sprintf('%s/Base%sRepository.php', $bundleMetadata->getOdmMetadata()->getDocumentDirectory(), $name);

            if (!is_file($src_file)) {
                $output->writeln(sprintf('   ! <info>%sRepository</info>', $name));

                continue;
            }

            if (is_file($dest_file)) {
                $output->writeln(sprintf('   ~ <info>%sRepository</info>', $name));
            } else {
                $output->writeln(sprintf('   + <info>%sRepository</info>', $name));

                $string = Mustache::replace($this->getDocumentRepositoryTemplate(), [
                    'extended_namespace' => $bundleMetadata->getExtendedNamespace(),
                    'name' => $name,
                    'namespace' => $bundleMetadata->getNamespace(),
                ]);

                file_put_contents($dest_file, $string);
            }
        }
    }
}
