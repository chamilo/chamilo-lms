<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MNG;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Interlace extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Interlace';

    protected $FullName = 'mixed';

    protected $GroupName = 'MNG';

    protected $g0 = 'MNG';

    protected $g1 = 'MNG';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Interlace';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Noninterlaced',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Adam7 Interlace',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'Sequential',
        ),
        3 => array(
            'Id' => 8,
            'Label' => 'Progressive',
        ),
    );

}
