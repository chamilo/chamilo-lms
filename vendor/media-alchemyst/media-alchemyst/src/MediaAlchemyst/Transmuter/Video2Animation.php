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
use Imagine\Image\ImageInterface;
use Imagine\Exception\Exception as ImagineException;
use MediaAlchemyst\Specification\Animation;
use MediaAlchemyst\Specification\SpecificationInterface;
use MediaAlchemyst\Exception\SpecNotSupportedException;
use MediaAlchemyst\Exception\RuntimeException;
use MediaVorus\Media\MediaInterface;
use FFMpeg\Coordinate\TimeCode;
use MediaVorus\Media\Video as MediaVorusVideo;

class Video2Animation extends AbstractTransmuter
{
    public static $autorotate = true;
    public static $lookForEmbeddedPreview = false;

    public function execute(SpecificationInterface $spec, MediaInterface $source, $dest)
    {
        if (! $spec instanceof Animation) {
            throw new SpecNotSupportedException('Imagine Adapter only supports Image specs');
        }

        if ($source->getType() !== MediaInterface::TYPE_VIDEO) {
            throw new SpecNotSupportedException('Imagine Adapter only supports Images');
        }

        try {
            $movie = $this->container['ffmpeg.ffmpeg']
                ->open($source->getFile()->getPathname());

            $duration = $source->getDuration();

            $time = $pas = Max(1, $duration / 11);
            $files = array();

            while (ceil($time) < floor($duration)) {
                $files[] = $tmpFile =  $this->tmpFileManager->createTemporaryFile(self::TMP_FILE_SCOPE, 'ffmpeg', 'jpg');
                $frame = $movie->frame(TimeCode::fromSeconds($time));
                $frame->filters()->fixDisplayRatio();
                $frame->save($tmpFile);
                $time += $pas;
            }

            if (true === static::$autorotate && method_exists($source, 'getOrientation')) {
                switch ($source->getOrientation()) {
                    case MediaVorusVideo::ORIENTATION_90:
                        $rotate = 90;
                        break;
                    case MediaVorusVideo::ORIENTATION_270:
                        $rotate = -90;
                        break;
                    case MediaVorusVideo::ORIENTATION_180:
                        $rotate = 180;
                        break;
                    default:
                        $rotate = 0;
                        break;
                }
            }

            foreach ($files as $file) {
                $image = $this->container['imagine']->open($file);

                if (0 !== $rotate) {
                    $image->rotate($rotate);
                }

                if ($spec->getWidth() && $spec->getHeight()) {
                    if (0 !== $rotate / 90 % 2) {
                        $box = $this->boxFromSize($spec, $image->getSize()->getHeight(), $image->getSize()->getWidth());
                    } else {
                        $box = $this->boxFromSize($spec, $image->getSize()->getWidth(), $image->getSize()->getHeight());
                    }

                    if (null !== $box) {
                        if ($spec->getResizeMode() == Animation::RESIZE_MODE_OUTBOUND) {
                            /* @var $image \Imagine\Gmagick\Image */
                            $image = $image->thumbnail($box, ImageInterface::THUMBNAIL_OUTBOUND);
                        } else {
                            $image = $image->resize($box);
                        }
                    }
                }

                $image->save($file, array(
                    'quality'          => $spec->getQuality(),
                    'resolution-units' => $spec->getResolutionUnit(),
                    'resolution-x'     => $spec->getResolutionX(),
                    'resolution-y'     => $spec->getResolutionY(),
                ));

                unset($image);
            }

            $image = $this->container['imagine']->open(array_shift($files));

            foreach ($files as $file) {
                $image->layers()->add($this->container['imagine']->open($file));
            }

            $image->save($dest, array(
                'animated' => true,
                'animated.delay' => $spec->getDelay(),
                'animated.loops' => $spec->getLoops(),
            ));
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
        } catch (FFMpegException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw new RuntimeException('Unable to transmute video to animation due to FFMpeg', null, $e);
        } catch (ImagineException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw new RuntimeException('Unable to transmute video to animation due to Imagine', null, $e);
        } catch (RuntimeException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw $e;
        }

        return $this;
    }
}
