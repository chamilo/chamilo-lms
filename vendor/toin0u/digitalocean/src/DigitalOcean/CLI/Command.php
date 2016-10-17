<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\CLI;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Yaml\Yaml;
use DigitalOcean\Credentials;
use DigitalOcean\DigitalOcean;

/**
 * Command class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class Command extends BaseCommand
{
    /**
     * The distribution file with fake credentials.
     *
     * @var string
     */
    const DIST_CREDENTIALS_FILE = './credentials.yml.dist';

    /**
     * The default file with credentials.
     *
     * @var string
     */
    const DEFAULT_CREDENTIALS_FILE = './credentials.yml';


    /**
     * Returns an instance of DigitalOcean
     *
     * @param string $file The file with credentials.
     *
     * @return DigitalOcean An instance of DigitalOcean
     *
     * @throws \RuntimeException
     */
    public function getDigitalOcean($file = self::DEFAULT_CREDENTIALS_FILE)
    {
        if (!file_exists($file)) {
            throw new \RuntimeException(sprintf('Impossible to get credentials informations in %s', $file));
        }

        $credentials = Yaml::parse($file);

        return new DigitalOcean(new Credentials($credentials['CLIENT_ID'], $credentials['API_KEY']));
    }
}
