<?php
/**
 * CompactVendorCommand.php
 *
 * Date: 15.02.14
 */

namespace Chamilo\AdminThemeBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Shell\Command;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\Process;

class CompactVendorCommand extends ContainerAwareCommand {


    protected function configure() {
        $this
            ->setName('avanzu:admin:compact-vendor')
            ->setDescription('fetch vendor assets')
            ->addArgument('theme', InputArgument::OPTIONAL, 'Which theme?', 'modern-touch')
            ->addOption('nojs', null, InputOption::VALUE_NONE, 'will skip js compression')
            ->addOption('nocss', null, InputOption::VALUE_NONE, 'will skip css compression')
            //->addArgument('name', InputArgument::OPTIONAL, 'Who do you want to greet?')
            //->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $kernel = $this->getContainer()->get('kernel');
        /** @var $kernel Kernel */
        $helper = $this->getHelperSet()->get('formatter');
        /** @var $helper FormatterHelper */

        $vendors = $kernel->locateResource('@ChamiloAdminThemeBundle/Resources/vendor/');

        $public = dirname($vendors) . '/public';
        $images = $public . '/images';
        $fonts  = $public . '/fonts';

        if (!$input->getOption('nojs')) {
            $this->compressVendorJs($output);
            $this->compressThemeJs($input, $output);
        }

        if (!$input->getOption('nocss')) {
            $this->compressThemeCss($input, $output);
        }

        $this->copyFonts($input, $output);
        $this->copyImages($input, $output);

    }


    protected function getThemePath($type, InputInterface $input, $kernel) {
        $theme    = $input->getArgument('theme');
        $themedir = strtr('@ChamiloAdminThemeBundle/Resources/vendor/bootflat/{type}',
                          array(
                              '{theme}' => $theme,
                              '{type}'  => $type
                          ));
        $vendors  = $kernel->locateResource($themedir);

        return $vendors;
    }

    protected function copyFonts(InputInterface $input, OutputInterface $output) {
        $kernel = $this->getContainer()->get('kernel');
        /** @var $kernel Kernel */
        $helper = $this->getHelperSet()->get('formatter');
        /** @var $helper FormatterHelper */


        $vendors  = $this->getThemePath('fonts', $input, $kernel);
        $target   = $kernel->locateResource('@ChamiloAdminThemeBundle/Resources/public/fonts');

        $process = new Process(sprintf('rm -rf %s/*', $target));
        $output->writeln($helper->formatSection('Executing', $process->getCommandLine(), 'comment'));
        $process->run();


        $process = new Process(sprintf('cp -R %s/* %s', $vendors, $target));
        $output->writeln($helper->formatSection('Executing', $process->getCommandLine(), 'comment'));
        $process->run();

    }

    protected function copyImages(InputInterface $input, OutputInterface $output) {

        $kernel = $this->getContainer()->get('kernel');
        /** @var $kernel Kernel */
        $helper = $this->getHelperSet()->get('formatter');
        /** @var $helper FormatterHelper */

        $vendors  = $this->getThemePath('img', $input, $kernel);
        $target   = $kernel->locateResource('@ChamiloAdminThemeBundle/Resources/public/img');

        $process = new Process(sprintf('rm -rf %s/*', $target));
        $output->writeln($helper->formatSection('Executing', $process->getCommandLine(), 'comment'));
        $process->run();


        $process = new Process(sprintf('cp -R %s/* %s', $vendors, $target));
        $output->writeln($helper->formatSection('Executing', $process->getCommandLine(), 'comment'));
        $process->run();

    }

    protected function compressThemeCss(InputInterface $input, OutputInterface $output) {

        $kernel = $this->getContainer()->get('kernel');
        /** @var $kernel Kernel */
        $helper = $this->getHelperSet()->get('formatter');
        /** @var $helper FormatterHelper */

        $vendors  = $this->getThemePath('css', $input, $kernel);

        $public = dirname(dirname(dirname($vendors))) . '/public';
        $script = $public . '/css/theme.min.css';

        $files    = array(
            dirname($vendors).'/bootstrap/bootstrap.css'
            ,'font-awesome.css'
            ,'bootflat.css'
            ,'bootflat-extensions.css'
            ,'bootflat-square.css'
        );

        $process = new Process(sprintf('/usr/local/share/npm/bin/uglifycss %s > %s', implode(' ', $files), $script));
        $output->writeln($helper->formatSection('Executing', $process->getCommandLine(), 'comment'));
        $process->setWorkingDirectory($vendors);

        $process->run(function ($type, $buffer) use ($output, $helper) {
            if (Process::ERR == $type) {
                $output->write($helper->formatSection('Error', $buffer, 'error'));
            } else {
                $output->write($helper->formatSection('Progress', $buffer, 'info'));
            }
        });

    }

    protected function compressThemeJs(InputInterface $input, OutputInterface $output) {
        $kernel = $this->getContainer()->get('kernel');
        /** @var $kernel Kernel */
        $helper = $this->getHelperSet()->get('formatter');
        /** @var $helper FormatterHelper */

        $vendors = $this->getThemePath('js', $input, $kernel);

        $public = dirname(dirname(dirname($vendors))) . '/public';
        $script = $public . '/js/theme.min.js';


        $files = array(
            'bootstrap.js'
            ,'jquery.icheck.js'
        );

        $process = new Process(sprintf('/usr/local/bin/uglifyjs %s -c -m -o %s', implode(' ', $files), $script));
        $output->writeln($helper->formatSection('Executing command', 'Compressing theme vendor scripts'));
        $output->writeln($helper->formatSection('Compressing', $process->getCommandLine(), 'comment'));
        $process->setWorkingDirectory($vendors);

        $process->run(function ($type, $buffer) use ($output, $helper) {
            if (Process::ERR == $type) {
                $output->write($helper->formatSection('Error', $buffer, 'error'));
            } else {
                $output->write($helper->formatSection('Progress', $buffer, 'info'));
            }
        });

    }


    protected function compressVendorJs(OutputInterface $output) {

        $kernel = $this->getContainer()->get('kernel');
        /** @var $kernel Kernel */
        $helper = $this->getHelperSet()->get('formatter');
        /** @var $helper FormatterHelper */

        $vendors = $kernel->locateResource('@ChamiloAdminThemeBundle/Resources/vendor/');

        $public = dirname($vendors) . '/public';
        $script = $public . '/js/vendors.js';

        $files = array(
            'jquery/dist/jquery.js'
            , 'jquery-ui/jquery-ui.js'
            , 'fastclick/lib/fastclick.js'
            , 'jquery.cookie/jquery.cookie.js'
            , 'jquery-placeholder/jquery.placeholder.js'
            , 'underscore/underscore.js'
            , 'backbone/backbone.js'
            , 'backbone.babysitter/lib/backbone.babysitter.js'
            , 'backbone.wreqr/lib/backbone.wreqr.js'
            , 'marionette/lib/backbone.marionette.js'
            , 'momentjs/moment.js'
            , 'momentjs/lang/de.js'
            , 'spinjs/spin.js'
            , 'spinjs/jquery.spin.js'
            , 'holderjs/holder.js'
        );

        $process = new Process(sprintf('/usr/local/bin/uglifyjs %s -c -m -o %s', implode(' ', $files), $script));
        $output->writeln($helper->formatSection('Executing command', 'Compressing general vendor scripts'));
        $output->writeln($helper->formatSection('Compressing', $process->getCommandLine(), 'comment'));
        $process->setWorkingDirectory($vendors);

        $process->run(function ($type, $buffer) use ($output, $helper) {
            if (Process::ERR == $type) {
                $output->write($helper->formatSection('Error', $buffer, 'error'));
            } else {
                $output->write($helper->formatSection('Progress', $buffer, 'info'));
            }
        });

    }
}

