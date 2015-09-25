<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\EasyExtendsBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;

/**
 * Generate Application entities from bundle entities
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class DumpMappingCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('sonata:easy-extends:dump-mapping');
        $this->setDescription('Dump some mapping information (debug only)');

        $this->addArgument('manager', InputArgument::OPTIONAL, 'The manager name to use', false);
        $this->addArgument('model', InputArgument::OPTIONAL, 'The class to dump', false);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory = $this->getContainer()->get('doctrine')->getManager($input->getArgument('manager'))->getMetadataFactory();

        $metadata = $factory->getMetadataFor($input->getArgument('model'));

        $cme = new ClassMetadataExporter();
        $exporter = $cme->getExporter('php');

        $output->writeln($exporter->exportClassMetadata($metadata));
        $output->writeln('Done!');
    }
}
