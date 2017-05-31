<?php

/*
 * This file is part of MediaVorus.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaVorus\Media;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\Exclude;
use FFMpeg\Exception\ExceptionInterface as FFMpegException;
use FFMpeg\FFProbe;
use MediaVorus\File;
use PHPExiftool\Writer;
use PHPExiftool\FileEntity;

/**
 * @ExclusionPolicy("all")
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
class Video extends Image
{
    /**
     * @VirtualProperty
     *
     * @return string
     */
    public function getType()
    {
        return self::TYPE_VIDEO;
    }

    /**
     * @VirtualProperty
     *
     * @return Integer
     */
    public function getWidth()
    {
        $width = parent::getWidth();

        if (null === $this->ffprobe) {
            return $width;
        }

        try {
            $video = $this->ffprobe
                ->streams($this->file->getPathname())
                ->videos()
                ->first();

            return $video->getDimensions()->getWidth();
        } catch (FFMpegException $e) {

        }

        return $width;
    }

    /**
     * @VirtualProperty
     *
     * @return Integer
     */
    public function getHeight()
    {
        $height = parent::getHeight();

        if (null === $this->ffprobe) {
            return $height;
        }

        try {
            $video = $this->ffprobe
                ->streams($this->file->getPathname())
                ->videos()
                ->first();

            return $video->getDimensions()->getHeight();
        } catch (FFMpegException $e) {

        }

        return $height;
    }

    /**
     * Returns one one the ORIENTATION_* constants, the degrees value of Orientation
     *
     * @VirtualProperty
     *
     * @return int
     */
    public function getOrientation()
    {
        switch ($this->findInSources(array('Composite:Rotation'))) {
            case 90:
                return self::ORIENTATION_90;
                break;
            case 270:
                return self::ORIENTATION_270;
                break;
            case 0:
                return self::ORIENTATION_0;
                break;
            case 180:
                return self::ORIENTATION_180;
                break;
        }

        return null;
    }

    /**
     * Get the duration of the video in seconds, null if unavailable
     *
     * @VirtualProperty
     *
     * @return float
     */
    public function getDuration()
    {
        $sources = array('Composite:Duration', 'Flash:Duration', 'QuickTime:Duration', 'Real-PROP:Duration');

        if (null !== $value = $this->findInSources($sources)) {
            return $this->castValue($value, 'float');
        }

        if (null === $this->ffprobe) {
            return null;
        }

        $format = $this->ffprobe->format($this->file->getPathname());

        if ($format->has('duration')) {
            return $this->castValue($format->get('duration'), 'float');
        }

        return null;
    }

    /**
     * Returns the value of video frame rate, null if not available
     *
     * @VirtualProperty
     *
     * @return string
     */
    public function getFrameRate()
    {
        $sources = array('RIFF:FrameRate', 'RIFF:VideoFrameRate', 'Flash:FrameRate');

        if (null !== $value = $this->findInSources($sources)) {
            return $this->castValue($value, 'float');
        }

        if (null !== $value = $this->entity->executeQuery('Track1:VideoFrameRate')) {
            return $this->castValue($value->asString(), 'float');
        }

        if (null !== $value = $this->entity->executeQuery('Track2:VideoFrameRate')) {
            return $this->castValue($value->asString(), 'float');
        }

        return null;
    }

    /**
     * Returns the value of audio samplerate, null if not available
     *
     * @VirtualProperty
     *
     * @return string
     */
    public function getAudioSampleRate()
    {
        $sources = array('RIFF:AudioSampleRate', 'Flash:AudioSampleRate');

        if (null !== $value = $this->findInSources($sources)) {
            return $this->castValue($value, 'int');
        }

        if (null !== $value = $this->entity->executeQuery('Track1:AudioSampleRate')) {
            return $this->castValue($value->asString(), 'int');
        }

        if (null !== $value = $this->entity->executeQuery('Track2:AudioSampleRate')) {
            return $this->castValue($value->asString(), 'int');
        }

        return null;
    }

    /**
     * Returns the name of video codec, null if not available
     *
     * @VirtualProperty
     *
     * @return string
     */
    public function getVideoCodec()
    {
        $sources = array('RIFF:AudioSampleRate', 'Flash:VideoEncoding');

        if (null !== $value = $this->findInSources($sources)) {
            return $this->castValue($value, 'string');
        }

        if (null !== $value = $this->entity->executeQuery('QuickTime:ComAppleProappsOriginalFormat')) {
            return $this->castValue($value->asString(), 'string');
        }

        if (null !== $value = $this->entity->executeQuery('Track1:CompressorName')) {
            return $this->castValue($value->asString(), 'string');
        }

        if (null !== $value = $this->entity->executeQuery('Track2:CompressorName')) {
            return $this->castValue($value->asString(), 'string');
        }

        if (null !== $value = $this->entity->executeQuery('Track1:CompressorID')) {
            return $this->castValue($value->asString(), 'string');
        }

        if (null !== $value = $this->entity->executeQuery('Track2:CompressorID')) {
            return $this->castValue($value->asString(), 'string');
        }

        return null;
    }

    /**
     * Returns the name of audio codec, null if not available
     *
     * @VirtualProperty
     *
     * @return string
     */
    public function getAudioCodec()
    {
        if ($this->getMetadatas()->containsKey('RIFF:AudioCodec')
            && $this->getMetadatas()->containsKey('RIFF:Encoding')
            && $this->getMetadatas()->get('RIFF:AudioCodec')->getValue()->asString() === '') {
            return $this->getMetadatas()->get('RIFF:Encoding')->getValue()->asString();
        }

        if (null !== $value = $this->findInSources(array('Flash:AudioEncoding'))) {
            return $this->castValue($value, 'string');
        }

        if (null !== $VideoCodec = $this->entity->executeQuery('Track1:AudioFormat')) {
            return $this->castValue($VideoCodec->asString(), 'string');
        }

        if (null !== $VideoCodec = $this->entity->executeQuery('Track2:AudioFormat')) {
            return $this->castValue($VideoCodec->asString(), 'string');
        }

        return null;
    }
}

