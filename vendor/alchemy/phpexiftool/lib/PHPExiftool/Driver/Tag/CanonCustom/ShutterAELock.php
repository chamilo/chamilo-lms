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
class ShutterAELock extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Shutter-AELock';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Shutter-AE Lock';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AF/AE lock',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'AE lock/AF',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'AF/AF lock, No AE lock',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'AE/AF, No AE lock',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'AF/AE lock',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'AE lock/AF',
        ),
        6 => array(
            'Id' => 2,
            'Label' => 'AF/AF lock',
        ),
        7 => array(
            'Id' => 3,
            'Label' => 'AE+release/AE+AF',
        ),
    );

}
