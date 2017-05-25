<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\CLI\Droplets;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use DigitalOcean\CLI\Command;
use DigitalOcean\DigitalOcean;

/**
 * Command-line droplets:create-interactively class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class CreateInteractiveCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('droplets:create-interactively')
            ->setDescription('Create interactively a new droplet')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    private function getSizes(DigitalOcean $digitalOcean)
    {
        foreach ($digitalOcean->sizes()->getAll()->sizes as $size) {
            $sizes[$size->id] = $size->name;
        }

        return $sizes;
    }

    private function getRegions(DigitalOcean $digitalOcean)
    {
        foreach ($digitalOcean->regions()->getAll()->regions as $region) {
            $regions[$region->id] = $region->name;
        }

        return $regions;
    }

    private function getImages(DigitalOcean $digitalOcean, $typeImageId)
    {
        switch ($typeImageId) {
            case 0:
                $availableImages = $digitalOcean->images()->getGlobal();
                break;
            case 1:
                $availableImages = $digitalOcean->images()->getMyImages();
                break;
            case 2:
                $availableImages = $digitalOcean->images()->getAll();
                break;
        }

        foreach ($availableImages->images as $image) {
            $images[$image->id] = sprintf('%s, %s', $image->name, $image->distribution);
        }

        return $images;
    }

    private function getSshKeys(DigitalOcean $digitalOcean)
    {
        $sshKeys = array(0 => 'None (default)');
        foreach ($digitalOcean->sshkeys()->getAll()->ssh_keys as $sshKey) {
            $sshKeys[$sshKey->id] = $sshKey->name;
        }

        return $sshKeys;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));

        $dialog = $this->getHelperSet()->get('dialog');

        $name = $dialog->ask(
            $output,
            '<question>Please enter the name of the new droplet:</question> '
        );
        if (null === $name) {
            $output->writeln('Aborted!');

            return;
        }

        $sizeId = (int) $dialog->select(
            $output,
            '<question>Please select the size:</question> ',
            $sizes = $this->getSizes($digitalOcean)
        );

        $regionId = (int) $dialog->select(
            $output,
            '<question>Please select the region:</question> ',
            $regions = $this->getRegions($digitalOcean)
        );

        $typeImageId = $dialog->select(
            $output,
            '<question>Please select your image type:</question> ',
            array('Global images (default)', 'Your images', 'All images'),
            0
        );
        $imageId = (int) $dialog->select(
            $output,
            '<question>Please select the image:</question> ',
            $images = $this->getImages($digitalOcean, $typeImageId)
        );

        $sshKeyId = $dialog->select(
            $output,
            '<question>Please select the SSH key to associate with your droplet: </question>',
            $sshKeys = $this->getSshKeys($digitalOcean),
            0
        );
        $sshKeyId = '0' !== $sshKeyId ? $sshKeyId : '';

        $confirmation = <<<EOT
Name:    <info>$name</info>
Size:    <info>{$sizes[$sizeId]}</info>
Region:  <info>{$regions[$regionId]}</info>
Image:   <info>{$images[$imageId]}</info>
SSH key: <info>{$sshKeys[$sshKeyId]}</info>
<question>Are you sure to create this droplet ? (y/N)</question>
EOT;

        if (!$dialog->askConfirmation($output, $confirmation, false)) {
            $output->writeln('Aborted!');

            return;
        }

        $droplet = $digitalOcean->droplets()->create(array(
            'name'        => $name,
            'size_id'     => $sizeId,
            'image_id'    => $imageId,
            'region_id'   => $regionId,
            'ssh_key_ids' => $sshKeyId,
        ));

        $content   = array();
        $content[] = array(
            $droplet->status,
            $droplet->droplet->event_id,
        );

        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('Status', 'Event ID'))
            ->setRows($content);

        $table->render($output);
    }
}
