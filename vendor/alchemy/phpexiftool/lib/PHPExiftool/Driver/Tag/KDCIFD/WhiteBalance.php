<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\KDCIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WhiteBalance extends AbstractTag
{

    protected $Id = 64013;

    protected $Name = 'WhiteBalance';

    protected $FullName = 'Kodak::KDC_IFD';

    protected $GroupName = 'KDC_IFD';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'KDC_IFD';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'White Balance';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Fluorescent',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Tungsten',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Daylight',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Shade',
        ),
    );

}
