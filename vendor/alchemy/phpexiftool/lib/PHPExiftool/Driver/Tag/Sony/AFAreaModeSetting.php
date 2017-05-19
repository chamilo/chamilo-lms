<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFAreaModeSetting extends AbstractTag
{

    protected $Id = 8220;

    protected $Name = 'AFAreaModeSetting';

    protected $FullName = 'Sony::Main';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Area Mode Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Wide',
        ),
        1 => array(
            'Id' => 4,
            'Label' => 'Local',
        ),
        2 => array(
            'Id' => 8,
            'Label' => 'Zone',
        ),
        3 => array(
            'Id' => 9,
            'Label' => 'Spot',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Multi',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        6 => array(
            'Id' => 3,
            'Label' => 'Flexible Spot',
        ),
        7 => array(
            'Id' => 11,
            'Label' => 'Zone',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Wide',
        ),
        9 => array(
            'Id' => 4,
            'Label' => 'Flexible Spot',
        ),
        10 => array(
            'Id' => 8,
            'Label' => 'Zone',
        ),
        11 => array(
            'Id' => 9,
            'Label' => 'Center',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Expanded Flexible Spot',
        ),
    );

    protected $Index = 'mixed';

}
