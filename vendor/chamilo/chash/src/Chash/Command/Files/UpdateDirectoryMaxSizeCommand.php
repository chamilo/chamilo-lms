<?php

namespace Chash\Command\Files;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateDirectoryMaxSizeCommand
 * Increase the maximum space allowed on disk progressively. This command is
 * used called once every night, to make a "progressive increase" of space which
 * will block abuse attempts, but still provide enough space to all courses to
 * continue working progressively.
 * @package Chash\Command\Files
 */
class UpdateDirectoryMaxSizeCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('files:update_directory_max_size')
            ->setAliases(array('fudms'))
            ->setDescription('Increases the max disk space for all the courses reaching a certain threshold.')
            ->addOption(
                'threshold',
                null,
                InputOption::VALUE_NONE,
                'Sets the threshold, in %, above which a course size should be automatically increased'
            )
            ->addOption(
                'add-size',
                null,
                InputOption::VALUE_NONE,
                'Number of MB to add to the max size of the course'
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
        $add = $input->getOption('add-size'); //1 if the option was set
        if (empty($add)) {
            $add = 100;
        }

        if ($add == 1) {
            $this->writeCommandHeader($output, 'Max space needs to be of at least 1MB for each course first');
            return;
        }

        $threshold = $input->getOption('threshold');
        if (empty($threshold)) {
            $threshold = 75;
        }
        $this->writeCommandHeader($output, 'Using threshold: '.$threshold);
        $this->writeCommandHeader($output, 'Checking courses dir...');

        // Get database and path information
        $coursesPath = $this->getConfigurationHelper()->getSysPath();
        $connection = $this->getConnection($input);
        $_configuration = $this->getConfigurationHelper()->getConfiguration();

        $courseTable = $_configuration['main_database'].'.course';
        $globalCourses = array();
        $sql = "SELECT c.id as cid, c.code as ccode, c.directory as cdir, c.disk_quota as cquota
                FROM $courseTable c";
        $res = mysql_query($sql);
        if ($res && mysql_num_rows($res) > 0) {
            while ($row = mysql_fetch_assoc($res)) {
                $globalCourses[$row['cdir']] = array('id' => $row['cid'], 'code' => $row['ccode'], 'quota' => $row['cquota']);
            }
        }

        $dirs = $this->getConfigurationHelper()->getDataFolders();
        if (count($dirs) > 0) {
            foreach ($dirs as $dir) {
                $file = $dir->getFileName();
                $res = exec('du -s '.$dir->getRealPath()); // results are returned in KB (under Linux)
                $res = preg_split('/\s/',$res);
                $size = round($res[0]/1024,1); // $size is stored in MB
                if (isset($globalCourses[$file]['code'])) {
                    $code = $globalCourses[$file]['code'];
                    $quota = round($globalCourses[$file]['quota']/(1024*1024), 0); //quota is originally in Bytes in DB. Store in MB
                    $rate = '-';
                    if ($quota > 0) {
                        $newAllowedSize = $quota;
                        $rate = round(($size/$newAllowedSize)*100, 0); //rate is a percentage of disk use vs allowed quota, in MB
                        $increase = false;
                        while ($rate > $threshold) { // Typically 80 > 75 -> increase quota
                            //$output->writeln('...Rate '.$rate.' is larger than '.$threshold.', so increase allowed size');
                            // Current disk usage goes beyond threshold. Increase allowed size by 100MB
                            $newAllowedSize += $add;
                            //$output->writeln('....New allowed size is '.$newAllowedSize);
                            $rate = round(($size/$newAllowedSize)*100, 0);
                            //$output->writeln('...Rate is now '.$rate);
                            $increase = true;
                        }
                        $newAllowedSize = $newAllowedSize*1024*1024;
                        //$output->writeln('Allowed size is '.$newAllowedSize.' Bytes, or '.round($newAllowedSize/(1024*1024)));
                        $sql = "UPDATE $courseTable SET disk_quota = $newAllowedSize WHERE id = ".$globalCourses[$file]['id'];
                        $res = mysql_query($sql);
                        if ($increase) {
                            $output->writeln('Increased max size of '.$globalCourses[$file]['code'].'('.$globalCourses[$file]['id'].') to '.$newAllowedSize);
                        }
                    } else {
                        //Quota is 0 (unlimited?)
                    }
                }
            }
        }
        $output->writeln('Done increasing disk space');
    }
}
