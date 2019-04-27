<?php

namespace Chash\Command\Files;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ReplaceURLCommand
 * Clean the archives directory, leaving only index.html, twig and Serializer
 * @package Chash\Command\Files
 */
class ReplaceURLCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('files:replace_url')
            ->setDescription('Cleans the config files to help you re-install')
            ->addArgument(
                'search',
                InputArgument::REQUIRED,
                'The string to search'
            )
            ->addArgument(
                'replace',
                InputArgument::REQUIRED,
                'The string to replace'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $search = $input->getArgument('search');
        $replace = $input->getArgument('replace');
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $output->writeln('');
            $output->writeln('<info>Running in --dry-run mode no changes or queries will be executed.</info>');
            $output->writeln('');
        }

        $this->writeCommandHeader($output, "Replacing URLs in these tables");
        $tables = $this->getTables();

        foreach ($tables as $table => $fields) {
            $output->write('<comment>'.$table.': </comment>');
            $output->writeln(implode(', ', $fields));
        }

        $dialog = $this->getHelperSet()->get('dialog');

        $output->writeln('');

        if (!$dialog->askConfirmation(
            $output,
            '<question>Are you sure you want to replace</question> <comment>'.$search.'</comment> with <comment>'.$replace.'</comment>? (y/N)',
            false
        )
        ) {
            return;
        }

        $output->writeln('');

        $connection = $this->getConnection();

        // Replace URLs from Database:

        foreach ($tables as $table => $fields) {
            foreach ($fields as $field) {
                $sql = "UPDATE $table SET $field = REPLACE ($field, '$search', '$replace')";
                $output->writeln($sql);
                if (!$dryRun) {
                    $result = $connection->query($sql);
                    $count = $result->rowCount();
                    $output->writeln("<comment># $count row(s) modified.</comment>");
                } else {
                    $output->writeln("<comment>Nothing was changed.</comment>");
                }
            }
        }

        // Replacing documents.
        $output->writeln('');
        $this->writeCommandHeader($output, "Replacing documents matching this query:");

        $sql = "SELECT
                    DISTINCT d.id, d.c_id, d.title, d.path, c.code, c.directory
                FROM c_document d
                INNER JOIN course c
                ON d.c_id = c.id
                WHERE
                  filetype = 'file' AND
                  (d.path LIKE '%.html' or d.path LIKE '%.htm')";
        $output->writeln('');
        $output->writeln(preg_replace('/\s+/', ' ', $sql));
        $result = $connection->query($sql);
        $count = $result->rowCount();
        $output->writeln("<comment># $count html files found</comment>");
        $results = $result->fetchAll();
        $coursePath = $this->getCourseSysPath();
        $output->writeln('');
        if (!$dialog->askConfirmation(
            $output,
            '<question>Are you sure you want to replace</question> <comment>'.$search.'</comment> with <comment>'.$replace.' in those '.$count.' files</comment> ? (y/N)',
            false
        )
        ) {
            return;
        }

        $output->writeln('');

        if (!empty($results)) {
            foreach ($results as $row) {
                $filePath = $coursePath . '/' . $row['directory'] . '/document' . $row['path'];
                $output->writeln($filePath);
                if (file_exists($filePath) && !empty($row['path'])) {
                    if (!$dryRun) {
                        $contents = file_get_contents($filePath);
                        $contents = str_replace($search, $replace, $contents);
                        $result = file_put_contents($filePath, $contents);

                        if ($result) {
                            $output->writeln(
                                "<comment>File Updated.</comment>"
                            );
                        } else {
                            $output->writeln("<error>Error!<error>");
                        }
                    } else {
                        $output->writeln(
                            "<comment>Nothing was changed.</comment>"
                        );
                    }
                } else {
                    $output->writeln("<error>File doesn't exists.</error>");
                }
            }
        } else {
            $output->writeln(
                "<comment>No results found.</comment>"
            );
        }
    }

    /**
     * @return array
     */
    private function getTables()
    {
        return array(
            'c_quiz' => array('description'),
            'c_quiz_answer' => array('answer', 'comment'),
            'c_quiz_question' => array('description'),
            'c_tool_intro' => array('intro_text'),
            'track_e_attempt' => array('answer'),
            'c_link' => array('url'),
            'c_glossary' => array('description')
        );
    }
}
