<?php

namespace Chash\Command\Files;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Helper\TableHelper;

/**
 * Class ShowDiskUsageCommand
 * Show the total disk usage per course compared to the maximum space allowed
 * for the corresponding courses
 * @package Chash\Command\Files
 */
class ShowDiskUsageCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('files:show_disk_usage')
            ->setAliases(array('fsdu'))
            ->setDescription('Shows the disk usage vs allowed space, per course')
            ->addOption(
                'multi-url',
                null,
                InputOption::VALUE_NONE,
                'Show the results split by url, if using the multi-url feature, using an unprecise "best-guess" process considering all session-specific material to be part of the same root course'
            )
            ->addOption(
                'csv',
                null,
                InputOption::VALUE_NONE,
                'Skip confirmation question and output directly to semi-column CSV format'
            )
            ->addOption(
                'KB',
                null,
                InputOption::VALUE_NONE,
                'Show results in KB instead of MB'
            )
            ->addOption(
                'documents-only',
                null,
                InputOption::VALUE_NONE,
                'Show results only from the document directory'
            )
            ->addOption(
                'precision',
                null,
                InputOption::VALUE_OPTIONAL,
                'Precision to show results',
                '1'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $kb = $input->getOption('KB'); //1 if the option was set
        $div = 1024*1024;
        $div2 = 1024;
        $unit = 'MB';

        if ($kb) {
            // Show results in KB instead of MB
            $div = 1024;
            $div2 = 1;
            $unit = 'KB';
        }

        $csv = $input->getOption('csv'); //1 if the option was set

        if (!$csv) {
            $this->writeCommandHeader($output, 'Checking courses dir...');
        }

        $dialog = $this->getHelperSet()->get('dialog');

        if (!$csv && !$dialog->askConfirmation(
            $output,
            '<question>This operation can take several hours on large volumes. Continue? (y/N)</question>',
            false
        )
        ) {
            return;
        }

        $_configuration = $this->getConfigurationHelper()->getConfiguration();
        $connection = $this->getConnection();

        // Check whether we want to use multi-url
        $portals = array(1 => 'http://localhost/');
        $multi = $input->getOption('multi-url'); //1 if the option was set
        if ($multi) {
            if (!$csv) {
                $output->writeln('Using multi-url mode');
            }
            $sql = "SELECT id, url FROM access_url ORDER BY url";
            $stmt = $connection->query($sql);
            while ($row = $stmt->fetch()) {
                $portals[$row['id']] = $row['url'];
            }
        }

        $globalCourses = array();
        $sql = "SELECT id, code, directory, disk_quota FROM course ORDER BY code";
        $stmt = $connection->query($sql);
        while ($row = $stmt->fetch()) {
            $globalCourses[$row['directory']] = array(
                'code' => $row['code'],
                'quota' => $row['disk_quota']
            );
        }
        $globalCoursesSizeSum = array();
        $sql = "SELECT directory, sum(size) as tSize
            FROM c_document INNER JOIN course ON c_document.c_id = course.id
            WHERE c_document.path NOT LIKE '%_DELETED'
            GROUP BY directory";
        $stmt = $connection->query($sql);
        while ($row = $stmt->fetch()) {
            $globalCoursesSizeSum[$row['directory']] = $row['tSize'];
            $globalCourses[$row['directory']]['dbSize'] = $row['tSize'];
        }

        // Size for all portals combined
        $totalSize = $totalDbSize = 0;
        $finalList = array();
        $orphanList = array();
        $dirs = $this->getConfigurationHelper()->getDataFolders();

        $isDocumentOnly = $input->getOption('documents-only');
        $dirDoc = "";
        $docsOnly = "AllDiskFiles";
        if ($isDocumentOnly) {
            $dirDoc = "/document";
            $docsOnly = " DocFilesOnly";
        }
        $precision = $input->getOption('precision');

        if (version_compare($_configuration['system_version'], '10.0', '>=')) {
            $sql = " SELECT access_url_id, c.id as course_id, c.code, directory, disk_quota
                FROM course c JOIN access_url_rel_course u
                ON u.c_id = c.id
                WHERE u.access_url_id = ? ";
        } else {
            $sql = " SELECT access_url_id, c.id as course_id, c.code, directory, disk_quota
                FROM course c JOIN access_url_rel_course u
                ON u.course_code = c.code
                WHERE u.access_url_id = ? ";
        }

        /** @var TableHelper $table */
        $table = $this->getHelperSet()->get('table');
        $table->setHeaders(array(
                'Portal',
                'Code',
                $docsOnly. '(' . $unit . ')',
                'DBDocs(' . $unit . ')',
                'DBQuota(' . $unit . ')',
                'UsedDiskVsDBQuota',
            )
        );

        // browse all the portals
        foreach ($portals as $portalId => $portalName) {
            if (empty($portalId)) {
                continue;
            }
            $stmt = $connection->prepare($sql);
            $stmt->bindParam(1, $portalId);
            $stmt->execute();

            $localCourses = array();
            while ($row = $stmt->fetch()) {
                if (!empty($row['directory'])) {
                    $localCourses[$row['directory']] = array(
                        'code' => $row['code'],
                        'quota' => $row['disk_quota'],
                        // recover the previously-calculated total size of the documents folder from DB
                        'dbSize' => $globalCoursesSizeSum[$row['directory']],
                    );
                }
            }

            // Size "local" to each course
            $localSize = $localDbSize = 0;
            if (count($dirs) > 0) {
                foreach ($dirs as $dir) {
                    $file = $dir->getFileName();

                    if (isset($localCourses[$file]['code']) &&
                        isset($globalCourses[$file]['code']) &&
                        isset($finalList[$globalCourses[$file]['code']])
                    ) {
                        // if this course has already been analysed, recover existing information
                        $size = $finalList[$globalCourses[$file]['code']]['size'];
                        $dbSize = $finalList[$globalCourses[$file]['code']]['dbSize'];
                        $table->addRow(
                            array(
                                $portalName,
                                $globalCourses[$file]['code'],
                                round($size/$div2, $precision),
                                round($dbSize/$div2, $precision),
                                $finalList[$globalCourses[$file]['code']]['quota'],
                                $finalList[$globalCourses[$file]['code']]['rate']
                            )
                        );
                        $localSize += $size;
                        $localDbSize += $dbSize;
                    } else {
                        $res = exec('du -s ' . $dir->getRealPath() . $dirDoc);
                        $res = preg_split('/\s/',$res);
                        $size = $res[0];

                        if (isset($localCourses[$file]['code'])) {
                            $localSize += $size; //always add size to local portal (but only add to total size if new)
                            $code = $localCourses[$file]['code'];
                            $dbSize = round($localCourses[$file]['dbSize']/$div2, $precision);
                            $localDbSize += $dbSize;
                            $quota = round($localCourses[$file]['quota']/$div, 0);
                            $rate = '-';
                            if ($quota > 0) {
                                $rate = round((round($size/$div2, 2)/$quota)*100, 0);
                            }
                            $finalList[$code] = array(
                                'code'  => $code,
                                'dir'   => $file,
                                'size'  => $size,
                                'dbSize'=> $dbSize,
                                'quota' => $quota,
                                'rateVsDisk'  => $rate,
                            );
                            //$finalListOrder[$code] = $size;
                            $totalSize += $size; //only add to total if new course
                            $totalDbSize += $dbSize; //only add to total if new course

                            $table->addRow(
                                array(
                                    $portalName,
                                    $code,
                                    round($size/$div2, $precision),
                                    round($dbSize/$div2, $precision),
                                    $finalList[$code]['quota'],
                                    $rate
                                )
                            );

                        } elseif (!isset($globalCourses[$file]['code']) && !isset($orphanList[$file])) {
                            // only add to orphans if not in global list from db
                            $orphanList[$file] = array('size' => $size);
                        }
                    }
                }
            }
            //$output->writeln($portalName . ';Subtotal;' . round($localSize/$div2, $precision) . ';;;');
            $table->addRow(
                array(
                    $portalName,
                    'SubtotalWithoutOrphans',
                    round($localSize/$div2, $precision),
                    round($localDbSize/$div2, $precision),
                )
            );
        }

        if (count($orphanList) > 0) {
            $table->addRow(array());
            $table->addRow(
                array(
                    'Portal',
                    'Code',
                    $docsOnly . '(' . $unit . ')',
                    'DBDocs(' . $unit . ')',
                    'Quota(' . $unit . ')',
                    'UsedRatio'
                )
            );
            //$output->writeln('CCC Code;Size' . $docsOnly . '(' . $unit . ');Quota(' . $unit . ');UsedRatio');
            foreach($orphanList as $key => $orphan) {
                $size = $orphan['size'];
                $sizeToShow = !empty($orphan['size']) ? round($orphan['size']/$div2, $precision) : 0;
                //$output->writeln($portalName . ';ORPHAN-DIR: ' . $key . ';' . $sizeToShow . ';;;');
                $table->addRow(array(
                    $portalName,
                    'ORPHAN-DIR: ' . $key,
                    $sizeToShow
                ));
                $totalSize += $size;
            }
        }
        //$output->writeln($portalName . ';Total size;' . round($totalSize/$div2, $precision) . ';;;');
        $table->addRow(
            array(
                $portalName,
                'Total size',
                round($totalSize/$div2, $precision),
                round($totalDbSize/$div2, $precision)
            )
        );

        if ($csv) {
            $table
                ->setPaddingChar(' ')
                ->setHorizontalBorderChar('')
                ->setVerticalBorderChar(' ')
                //->setCrossingChar('')
                //->setCellHeaderFormat(';')
                //->setCellRowFormat(' ')
                //->setCellRowContentFormat(' ')
                ->setBorderFormat(';')
                ->setPadType(STR_PAD_RIGHT);
        }
        $table->render($output);
    }
}
