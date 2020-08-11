<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IPTC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AudioType extends AbstractTag
{

    protected $Id = 150;

    protected $Name = 'AudioType';

    protected $FullName = 'IPTC::ApplicationRecord';

    protected $GroupName = 'IPTC';

    protected $g0 = 'IPTC';

    protected $g1 = 'IPTC';

    protected $g2 = 'Other';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Audio Type';

    protected $MaxLength = 2;

    protected $Values = array(
        '0T' => array(
            'Id' => '0T',
            'Label' => 'Text Only',
        ),
        '1A' => array(
            'Id' => '1A',
            'Label' => 'Mono Actuality',
        ),
        '1C' => array(
            'Id' => '1C',
            'Label' => 'Mono Question and Answer Session',
        ),
        '1M' => array(
            'Id' => '1M',
            'Label' => 'Mono Music',
        ),
        '1Q' => array(
            'Id' => '1Q',
            'Label' => 'Mono Response to a Question',
        ),
        '1R' => array(
            'Id' => '1R',
            'Label' => 'Mono Raw Sound',
        ),
        '1S' => array(
            'Id' => '1S',
            'Label' => 'Mono Scener',
        ),
        '1V' => array(
            'Id' => '1V',
            'Label' => 'Mono Voicer',
        ),
        '1W' => array(
            'Id' => '1W',
            'Label' => 'Mono Wrap',
        ),
        '2A' => array(
            'Id' => '2A',
            'Label' => 'Stereo Actuality',
        ),
        '2C' => array(
            'Id' => '2C',
            'Label' => 'Stereo Question and Answer Session',
        ),
        '2M' => array(
            'Id' => '2M',
            'Label' => 'Stereo Music',
        ),
        '2Q' => array(
            'Id' => '2Q',
            'Label' => 'Stereo Response to a Question',
        ),
        '2R' => array(
            'Id' => '2R',
            'Label' => 'Stereo Raw Sound',
        ),
        '2S' => array(
            'Id' => '2S',
            'Label' => 'Stereo Scener',
        ),
        '2V' => array(
            'Id' => '2V',
            'Label' => 'Stereo Voicer',
        ),
        '2W' => array(
            'Id' => '2W',
            'Label' => 'Stereo Wrap',
        ),
    );

}
