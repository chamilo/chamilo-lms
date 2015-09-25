<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Util;

use Composer\IO\IOInterface;
use Composer\Util\Perforce as BasePerforce;
use Composer\Util\ProcessExecutor;

/**
 * Helper for perforce driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class Perforce extends BasePerforce
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @param array $repoConfig
     */
    public function initialize($repoConfig)
    {
        parent::initialize($repoConfig);

        $this->filename = (string) $repoConfig['filename'];
    }

    /**
     * @param string $identifier
     *
     * @return array|string
     */
    public function getComposerInformation($identifier)
    {
        $index = strpos($identifier, '@');

        if ($index === false) {
            $composerJson = $identifier.'/'.$this->filename;

            return $this->getComposerInformationFromPath($composerJson);
        }

        return $this->getComposerInformationFromLabel($identifier, $index);
    }

    /**
     * @param string $identifier
     * @param string $index
     *
     * @return array|string
     */
    public function getComposerInformationFromLabel($identifier, $index)
    {
        $composerJsonPath = substr($identifier, 0, $index).'/'.$this->filename.substr($identifier, $index);
        $command = $this->generateP4Command(' files '.$composerJsonPath, false);
        $this->executeCommand($command);
        $result = $this->commandResult;
        $index2 = strpos($result, 'no such file(s).');

        if ($index2 === false) {
            $index3 = strpos($result, 'change');

            if (!($index3 === false)) {
                $phrase = trim(substr($result, $index3));
                $fields = explode(' ', $phrase);
                $id = $fields[1];
                $composerJson = substr($identifier, 0, $index).'/'.$this->filename.'@'.$id;

                return $this->getComposerInformationFromPath($composerJson);
            }
        }

        return '';
    }

    /**
     * Create perforce helper.
     *
     * @param array           $repoConfig
     * @param int|string      $port
     * @param string          $path
     * @param ProcessExecutor $process
     * @param IOInterface     $io
     *
     * @return Perforce
     */
    public static function create($repoConfig, $port, $path, ProcessExecutor $process, IOInterface $io)
    {
        $isWindows = defined('PHP_WINDOWS_VERSION_BUILD');
        $perforce = new self($repoConfig, $port, $path, $process, $isWindows, $io);

        return $perforce;
    }
}
