<?php

namespace Braincrafted\Bundle\BootstrapBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

class InstallCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('braincrafted:bootstrap:install')
            ->setDescription('Installs the icon font');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $destDir = $this->getDestDir();

        $finder = new Finder;
        $fs = new Filesystem;

        try {
            $fs->mkdir($destDir);
        } catch (IOException $e) {
            $output->writeln(sprintf('<error>Could not create directory %s.</error>', $destDir));

            return;
        }

        $srcDir = $this->getSrcDir();
        if (false === file_exists($srcDir)) {
            $output->writeln(sprintf(
                '<error>Fonts directory "%s" does not exist. Did you install twbs/bootstrap? '.
                'If you used something other than Compoer you need to manually change the path in '.
                '"braincrafted_bootstrap.assets_dir".</error>',
                $srcDir
            ));

            return;
        }
        $finder->files()->in($srcDir);

        foreach ($finder as $file) {
            $dest = sprintf('%s/%s', $destDir, $file->getBaseName());
            try {
                $fs->copy($file, $dest);
            } catch (IOException $e) {
                $output->writeln(sprintf('<error>Could not copy %s</error>', $file->getBaseName()));
                return;
            }
        }

        $output->writeln(sprintf('Copied Glyphicon fonts to <comment>%s</comment>.', $destDir));
    }

    /**
     * @return string
     */
    protected function getSrcDir()
    {
        return sprintf('%s/fonts', $this->getContainer()->getParameter('braincrafted_bootstrap.assets_dir'));
    }

    /**
     * @return string
     */
    protected function getDestDir()
    {
        $outputDir = $this->getContainer()->getParameter('braincrafted_bootstrap.output_dir');
        if (strlen($outputDir) > 0 && '/' !== substr($outputDir, -1)) {
            $outputDir .= '/';
        }

        return sprintf(
            '%s/../web/%sfonts',
            $this->getContainer()->getParameter('kernel.root_dir'),
            $outputDir
        );
    }
}
