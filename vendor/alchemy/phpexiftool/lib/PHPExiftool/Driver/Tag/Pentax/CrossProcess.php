<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CrossProcess extends AbstractTag
{

    protected $Id = 123;

    protected $Name = 'CrossProcess';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Cross Process';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Random',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Preset 1',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Preset 2',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Preset 3',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Favorite 1',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'Favorite 2',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Favorite 3',
        ),
    );

}
