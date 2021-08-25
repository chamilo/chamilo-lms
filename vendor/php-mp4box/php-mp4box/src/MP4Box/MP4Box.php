<?php

/*
 * This file is part of PHP-MP4Box.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MP4Box;

use Alchemy\BinaryDriver\AbstractBinary;
use Alchemy\BinaryDriver\Configuration;
use Alchemy\BinaryDriver\ConfigurationInterface;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use MP4Box\Exception\InvalidFileArgumentException;
use MP4Box\Exception\RuntimeException;
use Psr\Log\LoggerInterface;

class MP4Box extends AbstractBinary
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'MP4Box';
    }

    /**
     * Processes a file
     *
     * @param string      $inputFile  The file to process.
     * @param null|string $outputFile The output file to write. If not provided, processes the file in place.
     *
     * @return MP4Box
     *
     * @throws InvalidFileArgumentException In case the input file is not readable
     * @throws RuntimeException             In case the process failed
     */
    public function process($inputFile, $outputFile = null)
    {
        if (!file_exists($inputFile) || !is_readable($inputFile)) {
            throw new InvalidFileArgumentException(sprintf('File %s does not exist or is not readable', $inputFile));
        }

        $arguments = array(
            '-quiet',
            '-inter',
            '0.5',
            '-tmp',
            dirname($inputFile),
            $inputFile,
        );

        if ($outputFile) {
            $arguments[] = '-out';
            $arguments[] = $outputFile;
        }

        try {
            $this->command($arguments);
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException(sprintf(
                'MP4Box failed to process %s', $inputFile
            ), $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * Creates an MP4Box binary adapter.
     *
     * @param null|LoggerInterface         $logger
     * @param array|ConfigurationInterface $conf
     *
     * @return MP4Box
     */
    public static function create($conf = array(), LoggerInterface $logger = null)
    {
        if (!$conf instanceof ConfigurationInterface) {
            $conf = new Configuration($conf);
        }

        $binaries = $conf->get('mp4box.binaries', array('MP4Box'));

        return static::load($binaries, $logger, $conf);
    }
}
