<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RecordMode extends AbstractTag
{

    protected $Id = 9;

    protected $Name = 'RecordMode';

    protected $FullName = 'Canon::CameraSettings';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Record Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'JPEG',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'CRW+THM',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'AVI+THM',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'TIF',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'TIF+JPEG',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'CR2',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'CR2+JPEG',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'MOV',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'MP4',
        ),
    );

}
