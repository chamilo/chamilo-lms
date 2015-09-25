<?php

/*
 * This file is part of PHP-Unoconv.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Unoconv;

use Alchemy\BinaryDriver\AbstractBinary;
use Alchemy\BinaryDriver\Configuration;
use Alchemy\BinaryDriver\ConfigurationInterface;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Psr\Log\LoggerInterface;
use Unoconv\Exception\RuntimeException;
use Unoconv\Exception\InvalidFileArgumentException;

class Unoconv extends AbstractBinary
{
    const FORMAT_PDF = 'pdf';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Unoconv';
    }

    /**
     * Transcodes a file to another format
     *
     * @param string $input      The path to the input file
     * @param string $format     The output format
     * @param string $outputFile The path to the output file
     * @param string $pageRange  The range of pages. 1-14 for pages 1 to 14
     *
     * @return Unoconv
     *
     * @throws InvalidFileArgumentException In case the input file is not readable or does not exist
     * @throws RuntimeException             In case the output file can not be written, or the process failes
     */
    public function transcode($input, $format, $outputFile, $pageRange = null)
    {
        if (!file_exists($input)) {
            throw new InvalidFileArgumentException(sprintf('File %s does not exists', $input));
        }

        $arguments = array(
            '--format=' . $format,
            '--stdout'
        );

        if (preg_match('/\d+-\d+/', $pageRange)) {
            $arguments[] = '-e';
            $arguments[] = 'PageRange=' . $pageRange;
        }

        $arguments[] = $input;

        try {
            $output = $this->command($arguments);
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException(
                'Unoconv failed to transcode file', $e->getCode(), $e
            );
        }

        if (!is_writable(dirname($outputFile)) || !file_put_contents($outputFile, $output)) {
            throw new RuntimeException(sprintf(
                'Unable to write to output file `%s`', $outputFile
            ));
        }

        return $this;
    }

    /**
     * Creates an Unoconv instance
     *
     * @param LoggerInterface              $logger
     * @param array|ConfigurationInterface $conf
     *
     * @return Unoconv
     */
    public static function create($conf = array(), LoggerInterface $logger = null)
    {
        if (!$conf instanceof ConfigurationInterface) {
            $conf = new Configuration($conf);
        }

        $binaries = $conf->get('unoconv.binaries', array('unoconv'));

        return static::load($binaries, $logger, $conf);
    }
}
