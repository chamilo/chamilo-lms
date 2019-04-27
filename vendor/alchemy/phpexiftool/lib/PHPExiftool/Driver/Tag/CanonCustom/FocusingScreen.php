<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FocusingScreen extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FocusingScreen';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Focusing Screen';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Ec-N, R',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Ec-A,B,C,CII,CIII,D,H,I,L',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'Ef-A',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'Ef-D',
        ),
        4 => array(
            'Id' => 2,
            'Label' => 'Ef-S',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Eg-A',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Eg-D',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'Eg-S',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Eg-A II',
        ),
        9 => array(
            'Id' => 1,
            'Label' => 'Eg-D',
        ),
        10 => array(
            'Id' => 2,
            'Label' => 'Eg-S',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'Eh-A',
        ),
        12 => array(
            'Id' => 1,
            'Label' => 'Eh-S',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'Ec-CV',
        ),
        14 => array(
            'Id' => 1,
            'Label' => 'Ec-A,B,D,H,I,L',
        ),
        15 => array(
            'Id' => 0,
            'Label' => 'Ec-CIV',
        ),
        16 => array(
            'Id' => 1,
            'Label' => 'Ec-A,B,C,CII,CIII,D,H,I,L',
        ),
        17 => array(
            'Id' => 2,
            'Label' => 'Ec-S',
        ),
        18 => array(
            'Id' => 3,
            'Label' => 'Ec-N,R',
        ),
        19 => array(
            'Id' => 0,
            'Label' => 'Ee-A',
        ),
        20 => array(
            'Id' => 1,
            'Label' => 'Ee-D',
        ),
        21 => array(
            'Id' => 2,
            'Label' => 'Ee-S',
        ),
    );

    protected $Index = 'mixed';

}
