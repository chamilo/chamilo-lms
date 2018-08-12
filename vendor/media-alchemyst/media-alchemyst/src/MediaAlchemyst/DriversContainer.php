<?php

/*
 * This file is part of Media-Alchemyst.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaAlchemyst;

use Alchemy\BinaryDriver\Exception\ExecutableNotFoundException;
use Doctrine\Common\Cache\ArrayCache;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Exception\ExecutableNotFoundException as FFMpegExecutableNotFound;
use Ghostscript\Transcoder;
use MediaVorus\MediaVorus;
use MediaAlchemyst\Exception\RuntimeException;
use MediaAlchemyst\Exception\InvalidArgumentException;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use MP4Box\MP4Box;
use PHPExiftool\Exiftool;
use PHPExiftool\PreviewExtractor;
use PHPExiftool\Reader;
use PHPExiftool\RDFParser;
use PHPExiftool\Writer;
use Pimple;
use SwfTools\Binary\DriverContainer;
use SwfTools\Processor\FlashFile;
use SwfTools\Processor\PDFFile;
use Unoconv\Unoconv;

class DriversContainer extends Pimple
{
    public function __construct()
    {
        $this['logger.name'] = 'Media-Alchemyst drivers logger';
        $this['logger.level'] = function (Pimple $container) {
            return Logger::DEBUG;
        };

        $this['logger.handler'] = $this->share(function(Pimple $container) {
            return new NullHandler($container['logger.level']);
        });

        $bridge = class_exists('Symfony\Bridge\Monolog\Logger');

        $this['logger.class'] = $bridge ? 'Symfony\Bridge\Monolog\Logger' : 'Monolog\Logger';

        $this['logger'] = $this->share(function(Pimple $container) {
            $logger = new $container['logger.class']($container['logger.name']);
            $logger->pushHandler($container['logger.handler']);

            return $logger;
        });

        $this['default.configuration'] = array(
            'ffmpeg.threads'               => 4,
            'ffmpeg.ffmpeg.timeout'        => 3600,
            'ffmpeg.ffprobe.timeout'       => 60,
            'ffmpeg.ffmpeg.binaries'       => null,
            'ffmpeg.ffprobe.binaries'      => null,
            'imagine.driver'               => null,
            'gs.timeout'                   => 60,
            'gs.binaries'                  => null,
            'mp4box.timeout'               => 60,
            'mp4box.binaries'              => null,
            'swftools.timeout'             => 60,
            'swftools.pdf2swf.binaries'    => null,
            'swftools.swfrender.binaries'  => null,
            'swftools.swfextract.binaries' => null,
            'unoconv.binaries'             => null,
            'unoconv.timeout'              => 60,
        );
        $this['configuration'] = array();
        $this['configuration.merged'] = $this->share(function(Pimple $container) {
            return array_replace(
                $container['default.configuration'], $container['configuration']
            );
        });
        $this['ffmpeg.ffmpeg'] = $this->share(function(Pimple $container) {
            try {
                return FFMpeg::create(array_filter(array(
                    'ffmpeg.threads'  => $container['configuration.merged']['ffmpeg.threads'],
                    'timeout'         => $container['configuration.merged']['ffmpeg.ffmpeg.timeout'],
                    'ffmpeg.binaries' => $container['configuration.merged']['ffmpeg.ffmpeg.binaries'],
                )), $container['logger'], $container['ffmpeg.ffprobe']);
            } catch (FFMpegExecutableNotFound $e) {
                throw new RuntimeException('Unable to create FFMpeg driver', $e->getCode(), $e);
            }
        });
        $this['ffmpeg.ffprobe.cache'] = $this->share(function(Pimple $container) {
            return new ArrayCache();
        });
        $this['ffmpeg.ffprobe'] = $this->share(function(Pimple $container) {
            try {
                return FFProbe::create(array_filter(array(
                    'timeout'         => $container['configuration.merged']['ffmpeg.ffprobe.timeout'],
                    'ffprobe.binaries' => $container['configuration.merged']['ffmpeg.ffprobe.binaries'],
                )), $container['logger'], $container['ffmpeg.ffprobe.cache']);
            } catch (FFMpegExecutableNotFound $e) {
                throw new RuntimeException('Unable to create FFProbe driver', $e->getCode(), $e);
            }
        });
        $this['imagine'] = $this->share(function(Pimple $container) {
            $driver = $container['configuration.merged']['imagine.driver'];

            switch (true) {
                case 'imagick' === strtolower($driver):
                case null === $driver && class_exists('Imagick'):
                    $driver = 'Imagine\Imagick\Imagine';
                    break;
                case 'gmagick' === strtolower($driver):
                case null === $driver && class_exists('Gmagick'):
                    $driver = 'Imagine\Gmagick\Imagine';
                    break;
                case 'gd' === strtolower($driver):
                case null === $driver && extension_loaded('gd'):
                    $driver = 'Imagine\Gd\Imagine';
                    break;
            }

            if (false === class_exists($driver) || false === in_array('Imagine\Image\ImagineInterface', class_implements($driver))) {
                throw new InvalidArgumentException(sprintf('Invalid Imagine driver %s', $driver));
            }

            return new $driver();
        });

        $this['swftools.driver-container'] = $this->share(function(Pimple $container) {
            return DriverContainer::create(array_filter(array(
                'pdf2swf.binaries'    => $container['configuration.merged']['swftools.pdf2swf.binaries'],
                'swfrender.binaries'  => $container['configuration.merged']['swftools.swfrender.binaries'],
                'swfextract.binaries' => $container['configuration.merged']['swftools.swfextract.binaries'],
                'timeout'             => $container['configuration.merged']['swftools.timeout'],
            )), $container['logger']);
        });

        $this['swftools.flash-file'] = $this->share(function(Pimple $container) {
            return new FlashFile($container['swftools.driver-container']);
        });

        $this['swftools.pdf-file'] = $this->share(function(Pimple $container) {
            return new PDFFile($container['swftools.driver-container']);
        });

        $this['unoconv'] = $this->share(function(Pimple $container) {
            try {
                return Unoconv::create(array_filter(array(
                    'unoconv.binaries' => $container['configuration.merged']['unoconv.binaries'],
                    'timeout'          => $container['configuration.merged']['unoconv.timeout'],
                )), $container['logger']);
            } catch (ExecutableNotFoundException $e) {
                throw new RuntimeException('Unable to create Unoconv driver', $e->getCode(), $e);
            }
        });

        $this['exiftool.exiftool'] = $this->share(function(Pimple $container) {
            return new Exiftool($container['logger']);
        });

        $this['exiftool.rdf-parser'] = $this->share(function(Pimple $container) {
            return new RDFParser();
        });

        $this['exiftool.reader'] = $this->share(function(Pimple $container) {
            return new Reader(
                $container['exiftool.exiftool'],
                $container['exiftool.rdf-parser']
            );
        });

        $this['exiftool.writer'] = $this->share(function(Pimple $container) {
            return new Writer($container['exiftool.exiftool']);
        });

        $this['exiftool.preview-extractor'] = $this->share(function(Pimple $container) {
            return new PreviewExtractor($container['exiftool.exiftool']);
        });

        $this['ghostscript.transcoder'] = $this->share(function(Pimple $container) {
            try {
                return Transcoder::create(array_filter(array(
                    'gs.binaries' => $container['configuration.merged']['gs.binaries'],
                    'timeout'     => $container['configuration.merged']['gs.timeout'],
                )), $container['logger']);
            } catch (ExecutableNotFoundException $e) {
                throw new RuntimeException('Unable to create Unoconv driver', $e->getCode(), $e);
            }
        });

        $this['mp4box'] = $this->share(function(Pimple $container) {
            try {
                return MP4Box::create(array_filter(array(
                    'mp4box.binaries' => $container['configuration.merged']['mp4box.binaries'],
                    'timeout'         => $container['configuration.merged']['mp4box.timeout'],
                )));
            } catch (ExecutableNotFoundException $e) {
                throw new RuntimeException('Unable to create Unoconv driver', $e->getCode(), $e);
            }
        });

        $this['mediavorus'] = $this->share(function(Pimple $container) {
            $ffprobe = null;
            try {
                $ffprobe = $container['ffmpeg.ffprobe'];
            } catch (RuntimeException $e) {

            }

            return new MediaVorus(
                $container['exiftool.reader'],
                $container['exiftool.writer'],
                $ffprobe
            );
        });
    }

    public static function create()
    {
        return new static();
    }
}
