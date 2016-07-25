<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool;

use PHPExiftool\Driver\Metadata\MetadataBag;
use PHPExiftool\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Exiftool Metadatas Writer, it will be used to write metadatas in files
 *
 * Example usage :
 *
 *      $Writer = new Writer();
 *
 *      $metadatas = new MetadataBag();
 *      $metadata->add(new Metadata(new IPTC\ObjectName(), Value\Mono('Super title !')));
 *
 *      //writes the metadatas to the file
 *      $Writer->writes('image.jpg', $metadatas);
 *
 *      //writes the metadatas to image_copied.jpg
 *      $Writer->writes('image.jpg', $metadatas, 'image_copied.jpg');
 *
 * @todo implement remove partial content
 * @todo implement binary thumbnails
 * @todo implements stay_open
 */
class Writer
{
    const MODE_IPTC2XMP = 1;
    const MODE_IPTC2EXIF = 2;
    const MODE_EXIF2IPTC = 4;
    const MODE_EXIF2XMP = 8;
    const MODE_PDF2XMP = 16;
    const MODE_XMP2PDF = 32;
    const MODE_GPS2XMP = 64;
    const MODE_XMP2EXIF = 128;
    const MODE_XMP2IPTC = 256;
    const MODE_XMP2GPS = 512;
    const MODULE_MWG = 1;

    protected $mode;
    protected $modules;
    protected $erase;
    private $exiftool;
    private $eraseProfile;

    public function __construct(Exiftool $exiftool)
    {
        $this->exiftool = $exiftool;
        $this->reset();
    }

    public function reset()
    {
        $this->mode = 0;
        $this->modules = 0;
        $this->erase = false;
        $this->eraseProfile = false;

        return $this;
    }

    /**
     * Enable / Disable modes
     *
     * @param  integer $mode   One of the self::MODE_*
     * @param  Boolean $active Enable or disable the mode
     * @return Writer
     */
    public function setMode($mode, $active)
    {
        if ($active) {
            $this->mode |= $mode;
        } else {
            $this->mode = $this->mode & ~$mode;
        }

        return $this;
    }

    /**
     * Return true if the mode is enabled
     *
     * @param  integer $mode One of the self::MODE_*
     * @return Boolean True if the mode is enabled
     */
    public function isMode($mode)
    {
        return (boolean) ($this->mode & $mode);
    }

    /**
     * Enable / disable module.
     * There's currently only one module self::MODULE_MWG
     *
     * @param  integer $module One of the self::MODULE_*
     * @param  Boolean $active Enable or disable the module
     * @return Writer
     */
    public function setModule($module, $active)
    {
        if ($active) {
            $this->modules |= $module;
        } else {
            $this->modules = $this->modules & ~$module;
        }

        return $this;
    }

    /**
     * Return true if the module is enabled
     *
     * @param  integer $module
     * @return boolean
     */
    public function hasModule($module)
    {
        return (boolean) ($this->modules & $module);
    }

    /**
     * If set to true, erase all metadatas before write
     *
     * @param Boolean $boolean            Whether to erase metadata or not before writing.
     * @param Boolean $maintainICCProfile Whether to maintain or not ICC Profile in case of erasing metadata.
     */
    public function erase($boolean, $maintainICCProfile = false)
    {
        $this->erase = (boolean) $boolean;
        $this->eraseProfile = !$maintainICCProfile;
    }

    /**
     * Writes metadatas to the file. If a destination is provided, original file
     * is not modified.
     *
     * @param string      $file        The input file
     * @param MetadataBag $metadatas   A bag of metadatas
     * @param string      $destination The output file
     *
     * @return int the number of file written
     *
     * @throws InvalidArgumentException
     */
    public function write($file, MetadataBag $metadatas, $destination = null)
    {
        if ( ! file_exists($file)) {
            throw new InvalidArgumentException(sprintf('%s does not exists', $file));
        }

        $command = '';

        $common_args = ' -preserve -charset UTF8';

        if (count($metadatas)) {
            $common_args .= ' -codedcharacterset=utf8';
        }

        if ($this->erase) {
            /**
             * if erase is specfied, we MUST start by erasing datas before doing
             * anything else.
             */
            if (! $destination) {
                $command .= ' -all:all= ' . ($this->eraseProfile ? '' : '--icc_profile:all ') . '' . $file . ' -execute';

                /**
                 * If no destination, all commands will overwrite in place
                 */
                $common_args .= ' -overwrite_original_in_place';
            } else {

                /**
                 * @todo : NO RAW
                 * If destination was specified, we start by creating the blank
                 * destination, we will write in it at next step
                 */
                $command .= ' -all:all= ' . ($this->eraseProfile ? '' : '--icc_profile:all ') . '-o ' . $destination . ' ' . $file . ' -execute';

                $file = $destination;
                $destination = null;
            }
        }

        $command .= $this->addMetadatasArg($metadatas);

        if ($destination) {
            $command .= ' -o ' . escapeshellarg($destination) . ' ' . $file;
        } else {

            /**
             * Preserve the filesystem modification date/time of the original file
             * (FileModifyDate) when writing. Note that some filesystems (ie. Mac
             * and Windows) store a creation date which is not preserved by this
             * option. For these systems, the -overwrite_original_in_place option
             * may be used to preserve the creation date.
             */
            $command .= ' -overwrite_original_in_place ' . $file;
        }

        if ('' !== $syncCommand = $this->getSyncCommand()) {
            $command .= ' -execute -overwrite_original_in_place ' . $syncCommand . ' ' . $file;
        }

        $command .= ' -common_args' . $common_args;

        $lines = explode("\n", $this->exiftool->executeCommand($command));
        $lastLine = '';

        while ($lines && ! $lastLine) {
            $lastLine = array_pop($lines);
        }

        if (preg_match("/(\d+)\ image\ files\ (created|updated)/", $lastLine, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Factory for standard Writer
     *
     * @return Writer
     */
    public static function create(LoggerInterface $logger)
    {
        return new Writer(new Exiftool($logger));
    }

    /**
     * Computes modes, modules and metadatas to a single commandline
     *
     * @param MetadataBag $metadatas A Bag of metadatas
     *
     * @return string A part of the command
     */
    protected function addMetadatasArg(MetadataBag $metadatas)
    {
        $command = ' ';

        if ($this->modules & self::MODULE_MWG) {
            $command .= ' -use MWG';
        }

        foreach ($metadatas as $metadata) {
            foreach ($metadata->getValue()->asArray() as $value) {
                $command .= ' -' . $metadata->getTag()->getTagname() . '='
                    . escapeshellarg($value);
            }
        }

        return $command;
    }

    protected function getSyncCommand()
    {
        $syncCommand = '';

        $availableArgs = array(
            self::MODE_IPTC2XMP  => 'iptc2xmp.args',
            self::MODE_IPTC2EXIF => 'iptc2exif.args',
            self::MODE_EXIF2IPTC => 'exif2iptc.args',
            self::MODE_EXIF2XMP  => 'exif2xmp.args',
            self::MODE_PDF2XMP   => 'pdf2xmp.args',
            self::MODE_XMP2PDF   => 'xmp2pdf.args',
            self::MODE_GPS2XMP   => 'gps2xmp.args',
            self::MODE_XMP2EXIF  => 'xmp2exif.args',
            self::MODE_XMP2IPTC  => 'xmp2iptc.args',
            self::MODE_XMP2GPS   => 'xmp2gps.args',
        );

        foreach ($availableArgs as $arg => $cmd) {
            if ($this->mode & $arg) {
                $syncCommand .= ' -@ ' . $cmd;
            }
        }

        return $syncCommand;
    }
}
