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
class ExposureBracketStepSize extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'ExposureBracketStepSize';

    protected $FullName = 'Pentax::CameraSettings';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Exposure Bracket Step Size';

    protected $flag_Permanent = true;

    protected $Values = array(
        3 => array(
            'Id' => 3,
            'Label' => '0.3',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '0.5',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '0.7',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '1.0',
        ),
        11 => array(
            'Id' => 11,
            'Label' => '1.3',
        ),
        12 => array(
            'Id' => 12,
            'Label' => '1.5',
        ),
        13 => array(
            'Id' => 13,
            'Label' => '1.7',
        ),
        16 => array(
            'Id' => 16,
            'Label' => '2.0',
        ),
    );

}
