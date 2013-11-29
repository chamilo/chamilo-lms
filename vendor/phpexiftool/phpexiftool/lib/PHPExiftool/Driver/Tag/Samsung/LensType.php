<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Samsung;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LensType extends AbstractTag
{

    protected $Id = 40963;

    protected $Name = 'LensType';

    protected $FullName = 'Samsung::Type2';

    protected $GroupName = 'Samsung';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Samsung';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Lens Type';

    protected $local_g2 = 'Camera';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Built-in or Manual Lens',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Samsung NX 30mm F2 Pancake',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Samsung NX 18-55mm F3.5-5.6 OIS',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Samsung NX 50-200mm F4-5.6 ED OIS',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Samsung NX 20-50mm F3.5-5.6 ED',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Samsung NX 20mm F2.8 Pancake',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Samsung NX 18-200mm F3.5-6.3 ED OIS',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Samsung NX 60mm F2.8 Macro ED OIS SSA',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Samsung NX 16mm F2.4 Pancake',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Samsung NX 85mm F1.4 ED SSA',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Samsung NX 12-24mm F4-5.6 ED',
        ),
    );

}
