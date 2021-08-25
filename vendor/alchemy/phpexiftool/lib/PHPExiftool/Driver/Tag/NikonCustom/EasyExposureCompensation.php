<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class EasyExposureCompensation extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'EasyExposureCompensation';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Easy Exposure Compensation';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'On (auto reset)',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'On Auto Reset',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'On (auto reset)',
        ),
    );

}
