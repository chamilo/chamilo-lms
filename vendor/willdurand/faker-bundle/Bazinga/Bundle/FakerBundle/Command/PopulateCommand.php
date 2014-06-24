<?php

/**
 * This file is part of the FakerBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Bazinga\Bundle\FakerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class PopulateCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Populates configured entities with random data')
            ->setHelp(<<<HELP
The <info>faker:populate</info> command populates configured entities with random data.

  <info>php app/console faker:populate</info>

HELP
            )
            ->setName('faker:populate');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $populator = $this->getContainer()->get('faker.populator');
        $insertedPks = $populator->execute();

        $output->writeln('');

        if (0 === count($insertedPks)) {
            $output->writeln('<error>No entities populated.</error>');
        } else {
            foreach ($insertedPks as $class => $pks) {
                $reflClass = new \ReflectionClass($class);
                $shortClassName = $reflClass->getShortName();
                $output->writeln(sprintf('Inserted <info>%s</info> new <info>%s</info> objects', count($pks), $shortClassName));
            }
        }
    }
}
