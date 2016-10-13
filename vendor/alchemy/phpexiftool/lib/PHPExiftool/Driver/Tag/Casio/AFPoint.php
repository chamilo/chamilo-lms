<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Casio;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFPoint extends AbstractTag
{

    protected $Id = 24;

    protected $Name = 'AFPoint';

    protected $FullName = 'Casio::Main';

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'AF Point';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Upper Left',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Upper Right',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Near Left/Right of Center',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Far Left/Right of Center',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Far Left/Right of Center/Bottom',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Top Near-left',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Near Upper/Left',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Top Near-right',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Top Left',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Top Center',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Top Right',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Center Left',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Center Right',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Bottom Left',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Bottom Center',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Bottom Right',
        ),
    );

}
