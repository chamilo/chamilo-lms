<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DebugBlocksCommand extends BaseCommand
{
    public function configure()
    {
        $this->setName('sonata:block:debug');
        $this->setDescription('Debug all blocks available, show default settings of each block');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->getBlockServiceManager()->getServices() as $code => $service) {

            $resolver = new OptionsResolver();
            $service->setDefaultSettings($resolver);

            $settings = $resolver->resolve();

            $output->writeln('');
            $output->writeln(sprintf('<info>>> %s</info> (<comment>%s</comment>)', $service->getName(), $code));

            foreach ($settings as $key => $val) {
                $output->writeln(sprintf("    %-30s%s", $key, json_encode($val)));
            }
        }

        $output->writeln('done!');
    }
}
