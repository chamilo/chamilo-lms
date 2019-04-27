<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;

class SonataListFormMappingCommand extends ContainerAwareCommand
{
    /**
     * @var array
     */
    protected $metadata;

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return Kernel::MAJOR_VERSION !== 3;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sonata:core:form-mapping')
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output the mapping into a dedicated format (available: yaml, php)',
                'yaml'
            )
            ->setDescription('Get information on the current form mapping')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Getting form types:');
        foreach ($this->getContainer()->getParameter('sonata.core.form.types') as $id) {
            try {
                $instance = $this->getContainer()->get($id);

                if ($input->getOption('format') === 'yaml') {
                    $output->writeln(sprintf('              %s: %s', $instance->getName(), get_class($instance)));
                } else {
                    $output->writeln(sprintf(" '%s' => '%s',", $instance->getName(), get_class($instance)));
                }
            } catch (\Exception $e) {
                $output->writeln(sprintf('<error>Unable load service: %s</error>', $id));
            }
        }

        $output->writeln("\n\n\nGetting form type extensions:");
        $types = array();
        foreach ($this->getContainer()->getParameter('sonata.core.form.type_extensions') as $id) {
            try {
                $instance = $this->getContainer()->get($id);
                if (!isset($types[$instance->getExtendedType()])) {
                    $types[$instance->getExtendedType()] = array();
                }

                $types[$instance->getExtendedType()][] = $id;
            } catch (\Exception $e) {
                $output->writeln(sprintf('<error>Unable load service: %s</error>', $id));
            }
        }

        foreach ($types as $type => $classes) {
            if ($input->getOption('format') === 'yaml') {
                $output->writeln(sprintf('        %s: ', $type));
            } else {
                $output->writeln(sprintf("        '%s' => array( ", $type));
            }

            foreach ($classes as $class) {
                if ($input->getOption('format') === 'yaml') {
                    $output->writeln(sprintf('              - %s', $class));
                } else {
                    $output->writeln(sprintf("              '%s',", $class));
                }
            }

            if ($input->getOption('format') === 'php') {
                $output->writeln('        ), ');
            }
        }

        return 0;
    }
}
