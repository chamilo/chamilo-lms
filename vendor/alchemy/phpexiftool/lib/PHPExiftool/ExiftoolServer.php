<?php

/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool;

use PHPExiftool\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class ExiftoolServer extends Exiftool
{
    private $pipefile;
    private $server;
    private $offset = 0;

    public function __construct()
    {
        $this->setUp();
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    public function reset()
    {
        $this->cleanup();
        $this->setUp();
    }

    public function start()
    {
        $this->server->start();
    }

    public function stop()
    {
        if ($this->isRunning()) {
            file_put_contents($this->pipefile, "-stay_open False\n", FILE_APPEND);
        }

        $this->server->stop();
    }

    public function isRunning()
    {
        return null !== $this->server && $this->server->isRunning();
    }

    public function executeCommand($commands, $timeout = 4)
    {
        $optwithargs = array('-i', '-charset', '-if', '-w', '-common_args');
        if (true !== $this->isRunning()) {
            throw new RuntimeException('Server is not running');
        }

        $prev = null;

//        // first split execute (sub queries)
//
//        if (false !== $pos = strpos($commands, '-common_args')) {
//            $common_args = substr($commands, $pos + 12);
//
//            $recomposed = array();
//
//            foreach (explode(' -execute ', substr($commands, 0, $pos)) as $subcommand) {
//                $recomposed[] = $subcommand . ' ' . $common_args;
//            }
//            $commands = implode(' -execute ', $recomposed);
//        }

        foreach (explode(' ', $commands) as $command) {

            if ($command == '-q') {
                continue;
            }

            if (substr($command, 0, 1) === "'" && substr($command, -1) === "'") {
                $command = substr($command, 1, -1);
            }
            if (substr($command, 0, 1) === '"' && substr($command, -1) === '"') {
                $command = substr($command, 1, -1);
            }

            $end = "\n";

            file_put_contents($this->pipefile, $command . $end, FILE_APPEND);
        }

        file_put_contents($this->pipefile, "\n-execute\n", FILE_APPEND);
        // here we send sigcont
        //$this->server->signal(SIGCONT);
        $start = microtime(true);

        while ((strlen($this->server->getOutput()) <= $this->offset || substr(substr($this->server->getOutput(), $this->offset), -8) !== "{ready}\n") && (microtime(true) - $start) < $timeout) {
            usleep(25000);
        }

//        var_dump(microtime(true) - $start);
        // server output should be cached here because streams are polled
        // everytime I request the output

        $outputIsValid = substr($this->server->getOutput(), -8) === "{ready}\n";

        $output = $outputIsValid ? substr($this->server->getOutput(), $this->offset, -8) : '';

        $this->offset = strlen($this->server->getOutput());

        if (trim($output) === '' && $outputIsValid === false && $this->server->getErrorOutput()) {
            throw new RuntimeException('Command failed');
        }

        return $output;
    }

    private function setUp()
    {

        $this->pipefile = tempnam(sys_get_temp_dir(), 'exiftool-pipe');
        $this->server = new Process(self::getBinary() . ' -stay_open True -@ ' . $this->pipefile);
    }

    private function cleanup()
    {
        $this->stop();

        if (file_exists($this->pipefile) && is_writable($this->pipefile)) {
            unlink($this->pipefile);
            $this->pipefile = null;
        }

        $this->server = null;
    }
}
