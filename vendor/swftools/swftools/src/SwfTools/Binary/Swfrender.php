<?php

/*
 * This file is part of PHP-SwfTools.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwfTools\Binary;

use Alchemy\BinaryDriver\Configuration;
use Alchemy\BinaryDriver\ConfigurationInterface;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Psr\Log\LoggerInterface;
use SwfTools\Exception\InvalidArgumentException;
use SwfTools\Exception\RuntimeException;

class Swfrender extends Binary
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Swfrender';
    }

    /**
     * Renders an SWF file.
     *
     * @param string  $pathfile
     * @param string  $outputFile
     * @param Boolean $legacy
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function render($pathfile, $outputFile, $legacy)
    {
        if (trim($outputFile) === '') {
            throw new InvalidArgumentException('Invalid output file');
        }

        try {
            $this->command(array(
                ($legacy ? '-l' : ''),
                $pathfile, '-o',
                $outputFile,
            ));
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException(sprintf(
                '%s failed to run command', $this->getName()
            ), $e->getCode(), $e);
        }
    }

    /**
     * Creates the Swfrender binary driver
     *
     * @param array|ConfigurationInterface $conf
     * @param LoggerInterface              $logger
     *
     * @return Swfrender
     *
     * @throws ExecutableNotFound In case the executable is not found
     */
    public static function create($conf = array(), LoggerInterface $logger = null)
    {
        if (!$conf instanceof ConfigurationInterface) {
            $conf = new Configuration($conf);
        }

        $binaries = $conf->get('swfrender.binaries', array('swfrender'));

        return static::load($binaries, $logger, $conf);
    }
}
