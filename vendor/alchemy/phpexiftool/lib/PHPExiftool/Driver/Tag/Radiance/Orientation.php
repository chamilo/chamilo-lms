<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Radiance;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Orientation extends AbstractTag
{

    protected $Id = '_orient';

    protected $Name = 'Orientation';

    protected $FullName = 'Radiance::Main';

    protected $GroupName = 'Radiance';

    protected $g0 = 'Radiance';

    protected $g1 = 'Radiance';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Orientation';

    protected $Values = array(
        '+X +Y' => array(
            'Id' => '+X +Y',
            'Label' => 'Rotate 90 CW',
        ),
        '+X -Y' => array(
            'Id' => '+X -Y',
            'Label' => 'Mirror horizontal and rotate 270 CW',
        ),
        '+Y +X' => array(
            'Id' => '+Y +X',
            'Label' => 'Mirror vertical',
        ),
        '+Y -X' => array(
            'Id' => '+Y -X',
            'Label' => 'Rotate 180',
        ),
        '-X +Y' => array(
            'Id' => '-X +Y',
            'Label' => 'Mirror horizontal and rotate 90 CW',
        ),
        '-X -Y' => array(
            'Id' => '-X -Y',
            'Label' => 'Rotate 270 CW',
        ),
        '-Y +X' => array(
            'Id' => '-Y +X',
            'Label' => 'Horizontal (normal)',
        ),
        '-Y -X' => array(
            'Id' => '-Y -X',
            'Label' => 'Mirror horizontal',
        ),
    );

}
