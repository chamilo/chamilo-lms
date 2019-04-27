<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonVRD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AutoLightingOptimizer extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AutoLightingOptimizer';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Auto Lighting Optimizer';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Low',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Standard',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Strong',
        ),
        3 => array(
            'Id' => 100,
            'Label' => 'Low',
        ),
        4 => array(
            'Id' => 200,
            'Label' => 'Standard',
        ),
        5 => array(
            'Id' => 300,
            'Label' => 'Strong',
        ),
        6 => array(
            'Id' => 32767,
            'Label' => 'n/a',
        ),
    );

}
