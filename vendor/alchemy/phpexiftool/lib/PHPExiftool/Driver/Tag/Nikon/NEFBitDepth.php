<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class NEFBitDepth extends AbstractTag
{

    protected $Id = 3618;

    protected $Name = 'NEFBitDepth';

    protected $FullName = 'Nikon::Main';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'NEF Bit Depth';

    protected $flag_Permanent = true;

    protected $flag_Unsafe = true;

    protected $MaxLength = 4;

    protected $Values = array(
        '0 0 0 0' => array(
            'Id' => '0 0 0 0',
            'Label' => 'n/a (JPEG)',
        ),
        '8 8 8 0' => array(
            'Id' => '8 8 8 0',
            'Label' => '8 x 3',
        ),
        '12 0 0 0' => array(
            'Id' => '12 0 0 0',
            'Label' => 12,
        ),
        '14 0 0 0' => array(
            'Id' => '14 0 0 0',
            'Label' => 14,
        ),
        '16 16 16 0' => array(
            'Id' => '16 16 16 0',
            'Label' => '16 x 3',
        ),
    );

}
