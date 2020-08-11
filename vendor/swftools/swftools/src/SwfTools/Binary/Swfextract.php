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
use SwfTools\EmbeddedObject;
use SwfTools\Exception\InvalidArgumentException;
use SwfTools\Exception\RuntimeException;

class Swfextract extends Binary
{
    public function getName()
    {
        return 'Swfextract';
    }

    /**
     * Executes the command to list the embedded objects
     *
     * @param string $pathfile
     *
     * @return string|null The ouptut string, null on error
     *
     * @throws RuntimeException
     */
    public function listEmbedded($pathfile)
    {
        try {
            return $this->command(array($pathfile));
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException(sprintf(
                '%s failed to run command', $this->getName()
            ), $e->getCode(), $e);
        }
    }

    /**
     *
     * Execute the command to extract an embedded object from a flash file
     *
     * @param string         $pathfile   the file
     * @param EmbeddedObject $embedded   The id of the object
     * @param string         $outputFile the path where to extract
     *
     * @return string|null The ouptut string, null on error
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function extract($pathfile, EmbeddedObject $embedded, $outputFile)
    {
        if (trim($outputFile) === '') {
            throw new InvalidArgumentException('Invalid output file');
        }

        try {
            return $this->command(array(
                '-' . $embedded->getOption(),
                $embedded->getId(),
                $pathfile,
                '-o',
                $outputFile,
            ));
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException(sprintf(
                '%s failed to run command', $this->getName()
            ), $e->getCode(), $e);
        }
    }

    /**
     * Creates the Swfextract binary driver
     *
     * @param array|ConfigurationInterface $conf
     * @param LoggerInterface              $logger
     *
     * @return Swfextract
     *
     * @throws ExecutableNotFound In case the executable is not found
     */
    public static function create($conf = array(), LoggerInterface $logger = null)
    {
        if (!$conf instanceof ConfigurationInterface) {
            $conf = new Configuration($conf);
        }

        $binaries = $conf->get('swfextract.binaries', array('swfextract'));

        return static::load($binaries, $logger, $conf);
    }
}
