<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\CLI\Images;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use DigitalOcean\CLI\Command;

/**
 * Command-line images:global class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class GetGlobalCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('images:global')
            ->setDescription('Return all global images')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));
        $images       = $digitalOcean->images()->getGlobal()->images;

        $content = array();
        foreach ($images as $image) {
            $content[] = array($image->id, $image->name, $image->distribution);
        }

        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('ID', 'Name', 'Distribution'))
            ->setRows($content);

        $table->render($output);
    }
}
