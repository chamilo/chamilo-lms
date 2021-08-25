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
class AutoISOMax extends AbstractTag
{

    protected $Id = '1.2';

    protected $Name = 'AutoISOMax';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Auto ISO Max';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 16,
            'Label' => 400,
        ),
        1 => array(
            'Id' => 32,
            'Label' => 800,
        ),
        2 => array(
            'Id' => 48,
            'Label' => 1600,
        ),
        3 => array(
            'Id' => 0,
            'Label' => 200,
        ),
        4 => array(
            'Id' => 16,
            'Label' => 400,
        ),
        5 => array(
            'Id' => 32,
            'Label' => 800,
        ),
        6 => array(
            'Id' => 48,
            'Label' => 1600,
        ),
    );

}
