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
class AspectRatio extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'AspectRatio';

    protected $FullName = 'Canon::AspectInfo';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Aspect Ratio';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '3:2',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '1:1',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '4:3',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '16:9',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '4:5',
        ),
    );

}
