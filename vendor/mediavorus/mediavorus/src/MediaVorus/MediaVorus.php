<?php

/*
 * This file is part of MediaVorus.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaVorus;

use FFMpeg\FFProbe;
use MediaVorus\MediaCollection;
use MediaVorus\Exception\FileNotFoundException;
use MediaVorus\Media\MediaInterface;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use PHPExiftool\Reader;
use PHPExiftool\Writer;

/**
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
class MediaVorus
{
    private $reader;
    private $writer;
    private $ffprobe;

    public function __construct(Reader $reader, Writer $writer, FFProbe $ffprobe = null)
    {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->ffprobe = $ffprobe;
    }

    /**
     * @return Reader
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * @return Writer
     */
    public function getWriter()
    {
        return $this->writer;
    }

    /**
     * @return FFProbe
     */
    public function getFFProbe()
    {
        return $this->ffprobe;
    }

    /**
     * Build a Media Object given a file
     *
     * @param string $file
     * @return MediaInterface
     * @throws FileNotFoundException
     */
    public function guess($file)
    {
        $fileObj = new File($file);
        $classname = $this->guessFromMimeType($fileObj->getMimeType());

        return new $classname($fileObj, $this->reader->reset()->files($file)->first(), $this->writer, $this->ffprobe);
    }

    /**
     *
     * @param \SplFileInfo $dir
     * @param type $recursive
     *
     * @return MediaCollection
     */
    public function inspectDirectory($dir, $recursive = false)
    {
        $this->reader
            ->reset()
            ->in($dir)
            ->followSymLinks();

        if ( ! $recursive) {
            $this->reader->notRecursive();
        }

        $files = new MediaCollection();

        foreach ($this->reader as $entity) {
            $file = new File($entity->getFile());
            $classname = $this->guessFromMimeType($file->getMimeType());
            $files[] = new $classname($file, $entity, $this->writer, $this->ffprobe);
        }

        return $files;
    }

    /**
     * Create MediaVorus
     *
     * @return MediaVorus
     */
    public static function create()
    {
        $logger = new Logger('MediaVorus');
        $logger->pushHandler(new NullHandler());

        return new static(Reader::create($logger), Writer::create($logger), FFProbe::create(array(), $logger));
    }

    /**
     * Return the corresponding \MediaVorus\Media\* class corresponding to a
     * mimetype
     *
     * @param string $mime
     * @return string The name of the MediaType class to use
     */
    protected function guessFromMimeType($mime)
    {
        $mime = strtolower($mime);

        switch (true) {
            case strpos($mime, 'image/') === 0:
            case $mime === 'application/postscript':
            case $mime === 'application/illustrator':
                return 'MediaVorus\Media\Image';
                break;

            case strpos($mime, 'video/') === 0:
            case $mime === 'application/vnd.rn-realmedia':
                return 'MediaVorus\Media\Video';
                break;

            case strpos($mime, 'audio/') === 0:
                return 'MediaVorus\Media\Audio';
                break;

            /**
             * @todo Implements Documents
             */
            case strpos($mime, 'text/') === 0:
            case $mime === 'application/msword':
            case $mime === 'application/access':
            case $mime === 'application/pdf':
            case $mime === 'application/excel':
            case $mime === 'application/powerpoint':
            case $mime === 'application/vnd.ms-powerpoint':
            case $mime === 'application/vnd.ms-excel':
            case $mime === 'application/vnd.oasis.opendocument.formula':
            case $mime === 'application/vnd.oasis.opendocument.text-master':
            case $mime === 'application/vnd.oasis.opendocument.database':
            case $mime === 'application/vnd.oasis.opendocument.formula':
            case $mime === 'application/vnd.oasis.opendocument.chart':
            case $mime === 'application/vnd.oasis.opendocument.graphics':
            case $mime === 'application/vnd.oasis.opendocument.presentation':
            case $mime === 'application/vnd.oasis.opendocument.speadsheet':
            case $mime === 'application/vnd.oasis.opendocument.text':
                return 'MediaVorus\Media\Document';
                break;

            case $mime === 'application/x-shockwave-flash':
                return 'MediaVorus\Media\Flash';
                break;

            default:
                break;
        }

        return 'MediaVorus\Media\DefaultMedia';
    }
}
