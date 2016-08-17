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

use Doctrine\ORM\Mapping\ClassMetadata;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Filesystem;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Return useful data on the database schema.
 *
 * Class SonataDoctrineUtils
 */
class SonataDumpDoctrineMetaCommand extends ContainerAwareCommand
{
    /**
     * @var array
     */
    protected $metadata;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sonata:core:dump-doctrine-metadata')
            ->setDefinition(array(
                new InputOption(
                    'entity-name', 'E', InputOption::VALUE_OPTIONAL,
                    'If entity-name is set, dump will only contain the specified entity and all its extended classes.', null
                ),
                new InputOption(
                    'regex', 'r', InputOption::VALUE_OPTIONAL,
                    'If regex is set, dump will only contain entities which name match the pattern.', null
                ),
                new InputOption(
                    'filename', 'f', InputOption::VALUE_OPTIONAL,
                    'If filename is specified, result will be dumped into this file under json format.', null
                ),
            ))
            ->setDescription(
                'Get information on the current Doctrine\'s schema'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Initialising Doctrine metadata.');
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $metadata = $manager->getMetadataFactory()->getAllMetadata();

        $allowedMeta = $this->filterMetadata($metadata, $input, $output);

        /** @var ClassMetadata $meta */
        foreach ($allowedMeta as $meta) {
            $this->metadata[$meta->getName()] = $this->normalizeDoctrineORMMeta($meta);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->metadata) {
            $output->writeln('<error>No meta was found</error>');

            return 1;
        }

        $this->dumpMetadata($this->metadata, $input, $output);

        return 0;
    }

    /**
     * Display the list of entities handled by Doctrine and their fields.
     *
     * @param array           $metadata
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    private function dumpMetadata(array $metadata, InputInterface $input, OutputInterface $output)
    {
        foreach ($metadata as $name => $meta) {
            $output->writeln(sprintf('<info>%s</info>', $name));

            foreach ($meta['fields'] as $fieldName => $columnName) {
                $output->writeln(sprintf('  <comment>></comment> %s <info>=></info> %s', $fieldName, $columnName));
            }
        }
        $output->writeln('---------------');
        $output->writeln('----  END  ----');
        $output->writeln('---------------');
        $output->writeln('');

        if ($input->getOption('filename')) {
            $directory = dirname($input->getOption('filename'));
            $filename = basename($input->getOption('filename'));

            if (empty($directory) || $directory === '.') {
                $directory = getcwd();
            }

            $adapter = new LocalAdapter($directory, true);
            $fileSystem = new Filesystem($adapter);
            $success = $fileSystem->write($filename, json_encode($metadata), true);

            if ($success) {
                $output->writeLn(sprintf('<info>File %s/%s successfully created</info>', $directory, $filename));
            } else {
                $output->writeLn(sprintf('<error>File %s/%s could not be created</error>', $directory, $filename));
            }
        }
    }

    /**
     * @param array           $metadata
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return array
     */
    private function filterMetadata(array $metadata, InputInterface $input, OutputInterface $output)
    {
        $baseEntity = $input->getOption('entity-name');
        $regex = $input->getOption('regex');

        if ($baseEntity) {
            $allowedMeta = array_filter(
                $metadata,
                function ($meta) use ($baseEntity) {
                    /* @var \Doctrine\ORM\Mapping\ClassMetadata $meta */
                    return $meta->rootEntityName === $baseEntity;
                }
            );
        } elseif ($regex) {
            $allowedMeta = array_filter(
                $metadata,
                function ($meta) use ($regex) {
                    /* @var \Doctrine\ORM\Mapping\ClassMetadata $meta */
                    return preg_match($regex, $meta->rootEntityName);
                }
            );
        } else {
            $allowedMeta = $metadata;
        }

        return $allowedMeta;
    }

    /**
     * @param ClassMetadata $meta
     *
     * @return array
     */
    private function normalizeDoctrineORMMeta(ClassMetadata $meta)
    {
        $normalizedMeta = array();
        $fieldMappings = $meta->fieldMappings;

        $normalizedMeta['table'] = $meta->table['name'];

        foreach ($fieldMappings as $field) {
            $normalizedMeta['fields'][$field['fieldName']] = isset($field['columnName']) ? $field['columnName'] : null;
        }

        return $normalizedMeta;
    }
}
