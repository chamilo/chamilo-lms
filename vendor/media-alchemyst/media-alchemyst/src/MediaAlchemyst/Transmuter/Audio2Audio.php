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

use FFMpeg\Format\Audio\Flac;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Exception\ExceptionInterface as FFMpegException;
use FFMpeg\Filters\Audio\AudioResamplableFilter;
use MediaAlchemyst\Specification\Audio;
use MediaAlchemyst\Specification\SpecificationInterface;
use MediaAlchemyst\Exception\FormatNotSupportedException;
use MediaAlchemyst\Exception\SpecNotSupportedException;
use MediaAlchemyst\Exception\RuntimeException;
use MediaVorus\Media\MediaInterface;

class Audio2Audio extends AbstractTransmuter
{
    public function execute(SpecificationInterface $spec, MediaInterface $source, $dest)
    {
        if (!$spec instanceof Audio) {
            throw new SpecNotSupportedException('FFMpeg Adapter only supports Audio specs');
        }

        try {
            $audio = $this->container['ffmpeg.ffmpeg']
              ->open($source->getFile()->getPathname());
        } catch (FFMpegException $e) {
            throw new RuntimeException('Unable to transmute audio to audio due to FFMpeg', null, $e);
        }

        /* @var $spec \MediaAlchemyst\Specification\Audio */
        $format = $this->getFormatFromFileType($dest);

        if ($spec->getAudioCodec()) {
            $format->setAudioCodec($spec->getAudioCodec());
        }
        if ($spec->getAudioSampleRate()) {
            $audio->addFilter(new AudioResamplableFilter($spec->getAudioSampleRate()));
        }
        if ($spec->getAudioKiloBitrate()) {
            $format->setAudioKiloBitrate($spec->getAudioKiloBitrate());
        }

        try {
            $audio->save($format, $dest);

            unset($audio);
        } catch (FFMpegException $e) {
            throw new RuntimeException('Unable to transmute audio to audio due to FFMpeg', null, $e);
        }
    }

    protected function getFormatFromFileType($dest)
    {
        $extension = strtolower(pathinfo($dest, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'flac':
                $format = new Flac();
                break;
            case 'mp3':
                $format = new Mp3();
                break;
            default:
                throw new FormatNotSupportedException(sprintf('Unsupported %s format', $extension));
                break;
        }

        return $format;
    }
}
