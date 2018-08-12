<?php

/*
 * This file is part of Media-Alchemyst.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaAlchemyst\Transmuter;

use FFMpeg\Filters\Video\RotateFilter;
use FFMpeg\Format\Video\X264;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Format\Video\Ogg;
use FFMpeg\Format\Video\DefaultVideo;
use FFMpeg\Exception\ExceptionInterface as FFMpegException;
use FFMpeg\Filters\Video\ResizeFilter;
use FFMpeg\Filters\Video\SynchronizeFilter;
use FFMpeg\Filters\Video\FrameRateFilter;
use FFMpeg\Filters\Audio\AudioResamplableFilter;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\FrameRate;
use MP4Box\Exception\ExceptionInterface as MP4BoxException;
use MediaAlchemyst\Specification\SpecificationInterface;
use MediaAlchemyst\Specification\Video;
use MediaAlchemyst\Exception\RuntimeException;
use MediaAlchemyst\Exception\SpecNotSupportedException;
use MediaAlchemyst\Exception\FormatNotSupportedException;
use MediaVorus\Media\MediaInterface;
use MediaVorus\Media\Video as MediaVorusVideo;

class Video2Video extends AbstractTransmuter
{
    public static $autorotate = true;

    public function execute(SpecificationInterface $spec, MediaInterface $source, $dest)
    {
        if (! $spec instanceof Video) {
            throw new SpecNotSupportedException('FFMpeg Adapter only supports Video specs');
        }

        try {
            $video = $this->container['ffmpeg.ffmpeg']
                ->open($source->getFile()->getPathname());
        } catch (FFMpegException $e) {
            throw new RuntimeException('Unable to transmute video to video due to FFMpeg', null, $e);
        }

        /* @var $spec \MediaAlchemyst\Specification\Video */
        $format = $this->getFormatFromFileType($dest);

        $resizeMode = ResizeFilter::RESIZEMODE_FIT;
        if ($spec->getResizeMode()) {
            $resizeMode = $spec->getResizeMode();
        }


        if (true === static::$autorotate && method_exists($source, 'getOrientation')) {
            switch ($source->getOrientation()) {
                case MediaVorusVideo::ORIENTATION_90:
                    $video->addFilter(new RotateFilter(RotateFilter::ROTATE_90));
                    break;
                case MediaVorusVideo::ORIENTATION_270:
                    $video->addFilter(new RotateFilter(RotateFilter::ROTATE_270));
                    break;
                case MediaVorusVideo::ORIENTATION_180:
                    $video->addFilter(new RotateFilter(RotateFilter::ROTATE_180));
                    break;
                default:
                    break;
            }
        }

        $video->addFilter(new SynchronizeFilter());
        if ($source->getWidth() > $spec->getWidth() || $source->getHeight() > $spec->getHeight()) {
            $video->addFilter(
                new ResizeFilter(
                    new Dimension($spec->getWidth(), $spec->getHeight()), $resizeMode
                )
            );
        }

        if ($spec->getAudioCodec()) {
            $format->setAudioCodec($spec->getAudioCodec());
        }
        if ($spec->getVideoCodec()) {
            $format->setVideoCodec($spec->getVideoCodec());
        }
        if ($spec->getAudioSampleRate()) {
            $video->addFilter(new AudioResamplableFilter($spec->getAudioSampleRate()));
        }
        if ($spec->getAudioKiloBitrate()) {
            $format->setAudioKiloBitrate($spec->getAudioKiloBitrate());
        }
        if ($spec->getKiloBitrate()) {
            $format->setKiloBitrate($spec->getKiloBitrate());
        }
        if ($spec->getFramerate() && $spec->getGOPSize()) {
            $video->addFilter(
                new FrameRateFilter(
                    new FrameRate($spec->getFramerate()), $spec->getGOPSize()
                )
            );
        }

        try {
            $video->save($format, $dest);

            if ($format instanceof X264) {
                $this->container['mp4box']->process($dest);
            }

            unset($video);
        } catch (FFMpegException $e) {
            throw new RuntimeException('Unable to transmute video to video due to FFMpeg', null, $e);
        } catch (MP4BoxException $e) {
            throw new RuntimeException('Unable to transmute video to video due to MP4Box', null, $e);
        }

        return $this;
    }

    /**
     * @param string $dest
     *
     * @return DefaultVideo
     *
     * @throws FormatNotSupportedException
     */
    protected function getFormatFromFileType($dest)
    {
        $extension = strtolower(pathinfo($dest, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'webm':
                $format = new WebM();
                break;
            case 'mp4':
                $format = new X264();
                break;
            case 'ogv':
                $format = new Ogg();
                break;
            default:
                throw new FormatNotSupportedException(sprintf('Unsupported %s format', $extension));
                break;
        }

        return $format;
    }
}
