<?php

/*
 * This file is part of Media-Alchemyst.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaAlchemyst\Specification;

class Audio extends AbstractSpecification
{
    protected $audioKiloBitrate;
    protected $audioCodec;
    protected $audioSampleRate;
    protected $fileType;

    public function getType()
    {
        return self::TYPE_AUDIO;
    }

    public function setAudioKiloBitrate($kiloBitrate)
    {
        $this->audioKiloBitrate = $kiloBitrate;
    }

    public function getAudioKiloBitrate()
    {
        return $this->audioKiloBitrate;
    }

    public function setAudioCodec($audioCodec)
    {
        $this->audioCodec = $audioCodec;
    }

    public function getAudioCodec()
    {
        return $this->audioCodec;
    }

    public function setAudioSampleRate($audioSampleRate)
    {
        $this->audioSampleRate = $audioSampleRate;
    }

    public function getAudioSampleRate()
    {
        return $this->audioSampleRate;
    }
}
