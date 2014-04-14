<?php

/**
 * This file is part of BraincraftedBootstrapBundle.
 *
 * (c) 2012-2013 by Florian Eckerstorfer
 */

namespace Braincrafted\Bundle\BootstrapBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Braincrafted\Bundle\BootstrapBundle\Util\PathUtil;

/**
 * GenerateCommand
 *
 * @package    BraincraftedBootstrapBundle
 * @subpackage Command
 * @author     Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright  2012-2013 Florian Eckerstorfer
 * @license    http://opensource.org/licenses/MIT The MIT License
 * @link       http://bootstrap.braincrafted.com BraincraftedBootstrapBundle
 */
class GenerateCommand extends ContainerAwareCommand
{
    /** @var PathUtil */
    private $pathUtil;

    /**
     * {@inheritDoc}
     */
    public function __construct($name = null)
    {
        $this->pathUtil = new PathUtil;

        parent::__construct($name);
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    protected function configure()
    {
        $this
            ->setName('braincrafted:bootstrap:generate')
            ->setDescription('Generates a custom bootstrap.less')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getContainer()->getParameter('braincrafted_bootstrap.customize');

        if (false === isset($config['variables_file']) || null === $config['variables_file']) {
            $output->writeln('<error>Found no custom variables.less file.</error>');

            return;
        }

        $filter = $this->getContainer()->getParameter('braincrafted_bootstrap.less_filter');
        if ('less' !== $filter && 'lessphp' !== $filter) {
            $output->writeln(
                '<error>Bundle must be configured with "less" or "lessphp" to generated bootstrap.less</error>'
            );

            return;
        }

        $output->writeln('<comment>Found custom variables file. Generating...</comment>');
        $this->executeGenerateBootstrap($config);
        $output->writeln(sprintf('Saved to <info>%s</info>', $config['bootstrap_output']));
    }

    protected function executeGenerateBootstrap(array $config)
    {
        // In the template for bootstrap.less we need the path where Bootstraps .less files are stored and the path
        // to the variables.less file.
        // Absolute path do not work in LESSs import statement, we have to calculate the relative ones

        $lessDir = $this->pathUtil->getRelativePath(
            dirname($config['bootstrap_output']),
            $this->getContainer()->getParameter('braincrafted_bootstrap.assets_dir')
        );
        $variablesDir = $this->pathUtil->getRelativePath(
            dirname($config['bootstrap_output']),
            dirname($config['variables_file'])
        );
        $variablesFile = sprintf(
            '%s%s%s',
            $variablesDir,
            strlen($variablesDir) > 0 ? '/' : '',
            basename($config['variables_file'])
        );

        // We can now use Twig to render the bootstrap.less file and save it
        $content = $this->getContainer()->get('twig')->render(
            $config['bootstrap_template'],
            array(
                'variables_file' => $variablesFile,
                'assets_dir'     => $lessDir
            )
        );
        file_put_contents($config['bootstrap_output'], $content);
    }
}
