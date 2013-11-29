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

use FFMpeg\Exception\ExceptionInterface as FFMpegException;
use FFMpeg\Coordinate\TimeCode;
use Imagine\Exception\Exception as ImagineException;
use MediaVorus\Exception\ExceptionInterface as MediaVorusException;
use MediaAlchemyst\Specification\SpecificationInterface;
use MediaAlchemyst\Specification\Image;
use Imagine\Image\ImageInterface;
use MediaAlchemyst\Exception\RuntimeException;
use MediaAlchemyst\Exception\SpecNotSupportedException;
use MediaVorus\Media\MediaInterface;

class Video2Image extends AbstractTransmuter
{
    public static $time = '60%';

    public function execute(SpecificationInterface $spec, MediaInterface $source, $dest)
    {
        if (! $spec instanceof Image) {
            throw new SpecNotSupportedException('FFMpeg Adapter only supports Video specs');
        }

        /* @var $spec \MediaAlchemyst\Specification\Image */
        $tmpDest = $this->tmpFileManager->createTemporaryFile(self::TMP_FILE_SCOPE, 'ffmpeg', 'jpg');

        $time = (int) ($source->getDuration() * $this->parseTimeAsRatio(static::$time));

        try {
            $frame = $this->container['ffmpeg.ffmpeg']
                ->open($source->getFile()->getPathname())
                ->frame(TimeCode::fromSeconds($time));
            $frame->filters()->fixDisplayRatio();
            $frame->save($tmpDest);

            $image = $this->container['imagine']->open($tmpDest);

            if ($spec->getWidth() && $spec->getHeight()) {
                $box = $this->boxFromSize($spec, $image->getSize()->getWidth(), $image->getSize()->getHeight());

                if ($spec->getResizeMode() == Image::RESIZE_MODE_OUTBOUND) {
                    /* @var $image \Imagine\Gmagick\Image */
                    $image = $image->thumbnail($box, ImageInterface::THUMBNAIL_OUTBOUND);
                } else {
                    $image = $image->resize($box);
                }
            }

            $options = array(
                'quality'          => $spec->getQuality(),
                'resolution-units' => $spec->getResolutionUnit(),
                'resolution-x'     => $spec->getResolutionX(),
                'resolution-y'     => $spec->getResolutionY(),
            );

            $image->save($dest, $options);
            $image = null;
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
        } catch (FFMpegException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw new RuntimeException('Unable to transmute video to image due to FFMpeg', null, $e);
        } catch (ImagineException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw new RuntimeException('Unable to transmute video to image due to Imagine', null, $e);
        } catch (MediaVorusException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw new RuntimeException('Unable to transmute video to image due to Mediavorus', null, $e);
        } catch (RuntimeException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw $e;
        }
    }

    protected function parseTimeAsRatio($time)
    {
        if (substr($time, -1) === '%') {
            return substr($time, 0, strlen($time) - 1) / 100;
        }

        return Max(Min((float) $time, 1), 0);
    }
}
