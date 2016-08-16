<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPXmpDM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AudioChannelType extends AbstractTag
{

    protected $Id = 'audioChannelType';

    protected $Name = 'AudioChannelType';

    protected $FullName = 'XMP::xmpDM';

    protected $GroupName = 'XMP-xmpDM';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpDM';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Audio Channel Type';

    protected $Values = array(
        '5.1' => array(
            'Id' => '5.1',
            'Label' => '5.1',
        ),
        '7.1' => array(
            'Id' => '7.1',
            'Label' => '7.1',
        ),
        '16 Channel' => array(
            'Id' => '16 Channel',
            'Label' => '16 Channel',
        ),
        'Mono' => array(
            'Id' => 'Mono',
            'Label' => 'Mono',
        ),
        'Other' => array(
            'Id' => 'Other',
            'Label' => 'Other',
        ),
        'Stereo' => array(
            'Id' => 'Stereo',
            'Label' => 'Stereo',
        ),
    );

}
