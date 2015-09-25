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

class BundleGenerator implements GeneratorInterface
{
    protected $bundleTemplate;

    public function __construct()
    {
        $this->bundleTemplate = file_get_contents(__DIR__.'/../Resources/skeleton/bundle/bundle.mustache');
    }

    /**
     * @param OutputInterface $output
     * @param BundleMetadata  $bundleMetadata
     */
    public function generate(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $this->generateBundleDirectory($output, $bundleMetadata);
        $this->generateBundleFile($output, $bundleMetadata);
    }

    /**
     * @param OutputInterface $output
     * @param BundleMetadata  $bundleMetadata
     */
    protected function generateBundleDirectory(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $directories = array(
            '',
            'Resources/config/serializer',
            'Resources/config/doctrine',
            'Resources/config/routing',
            'Resources/views',
            'Command',
            'DependencyInjection',
            'Entity',
            'Document',
            'PHPCR',
            'Controller'
        );

        foreach ($directories as $directory) {
            $dir = sprintf('%s/%s', $bundleMetadata->getExtendedDirectory(), $directory);
            if (!is_dir($dir)) {
                $output->writeln(sprintf('  > generating bundle directory <comment>%s</comment>', $dir));
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param BundleMetadata  $bundleMetadata
     */
    protected function generateBundleFile(OutputInterface $output, BundleMetadata $bundleMetadata)
    {
        $file = sprintf('%s/Application%s.php', $bundleMetadata->getExtendedDirectory(), $bundleMetadata->getName());

        if (is_file($file)) {
            return;
        }

        $output->writeln(sprintf('  > generating bundle file <comment>%s</comment>', $file));

        $string = Mustache::replace($this->getBundleTemplate(), array(
            'bundle'    => $bundleMetadata->getName(),
            'namespace' => $bundleMetadata->getExtendedNamespace(),
        ));

        file_put_contents($file, $string);
    }

    /**
     * @return string
     */
    protected function getBundleTemplate()
    {
        return $this->bundleTemplate;
    }
}
