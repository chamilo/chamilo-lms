<?php

namespace Chash\Command\Info;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WhichCommand
 * Command meant to deal with what the user of this script is calling it for.
 * Gives info about where to find some important Chamilo code for specific tools
 * @package Chash\Command\Info
 */
class WhichCommand extends CommonInfoCommand
{
    protected $tools = array(
        'admin' => array(
            'path' => array(
                '1.9' => 'main/admin/',
                '1.10' => 'main/admin/',
            ),
            'info' => 'The admin tool is actually a package of tools for the platform administrator',
            'libpath' => 'main/inc/lib/settings.lib.php',
            'adminpath' => 'main/admin/*',
        ),
        'announcement' => array(
            'path' => array(
                '1.9' => 'main/announcements/',
                '1.10' => 'main/announcements/',
            ),
            'info' => 'The announcements tool is used to send announcements to users by mail and other channels',
            'libpath' => '-',
            'adminpath' => 'main/admin/system_announcements.php',
        ),
        'attendance' => array(
            'path' => array(
                '1.9' => 'main/attendance/',
                '1.10' => 'main/attendance/',
            ),
            'info' => 'The attendance tool is meant to register attendance of students to the courses. These can later be graded through the gradebook tool',
            'libpath' => 'main/inc/lib/attendance.lib.php',
            'adminpath' => '-',
        ),
        'auth' => array(
            'path' => array(
                '1.9' => 'main/auth/',
                '1.10' => 'main/auth/',
            ),
            'info' => 'The auth directory is not really a tool. It is a package of helpers for the authentication of users (passwords management, Single Sign On, OpenID, etc)',
            'libpath' => '-',
            'adminpath' => '-',
        ),
        'blog' => array(
            'path' => array(
                '1.9' => 'main/blog/',
                '1.10' => 'main/blog/',
            ),
            'info' => 'The blog tool allows teachers to prepare redaction activities where students can express themselves in writing about the courses activities',
            'libpath' => 'main/inc/lib/blog.lib.php',
            'adminpath' => '-',
        ),
        'calendar' => array(
            'path' => array(
                '1.9' => 'main/calendar/',
                '1.10' => 'main/calendar/',
            ),
            'info' => 'The calendar (or agenda) tool is used both globally and inside courses. It is mainly meant to show to students events organized on the platform.',
            'libpath' => '-',
            'adminpath' => 'main/admin/calendar*',
        ),
        'tool' => array(
            'path' => array(
                '1.9' => 'main/',
                '1.10' => 'main/',
            ),
            'info' => '',
            'libpath' => '',
            'adminpath' => 'main/',
        ),
        'aliases' => array(
            'admin'     => array('administration', 'management'),
            'announcement' => array('announcements', 'news'),
            'calendar' => array('agenda', 'schedule', 'events', 'event'),
            'attendance' => array('attendances', 'attend', 'assistance'),
            'auth' => array('openid', 'cas', 'sso', 'single-sign-on', 'shibboleth', 'drupal'),

        ),
    );
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('info:which')
            ->setDescription('Tells where to find code for Chamilo tools')
            ->addArgument(
                'tool',
                InputArgument::REQUIRED,
                'Allows you to specify the tool'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $_configuration = $this->getHelper('configuration')->getConfiguration();
        $version = $this->getHelper('configuration')->getMajorVersion();
        $tool = $input->getArgument('tool');
        $found = false;
        if (!isset($this->tools[$tool])) {
            foreach ($this->tools['aliases'] as $alias => $aliases) {
                if (in_array($tool, $aliases)) {
                    $tool = $alias;
                    $found = true;
                }
            }
        } else {
            $found = true;
        }
        if (!$found) {
            $output->writeln('Tool '.$tool.' could not be found.');

            return 0;
        }
        $output->writeln('');
        $output->writeln($tool.' info [Chamilo '.$version.']:');
        $output->writeln('');
        $output->writeln($this->tools[$tool]['info']);
        $output->writeln('* Tool\'s main code: '.$this->tools[$tool]['path'][$version]);
        if (!empty($this->tools[$tool]['adminpath'])) {
            $output->writeln('* Tool\'s admin code: '.$this->tools[$tool]['adminpath']);
        }
        if (!empty($this->tools[$tool]['libpath'])) {
            $output->writeln('* Tool\'s general library: '.$this->tools[$tool]['libpath']);
        }
        return 0;
    }
}
