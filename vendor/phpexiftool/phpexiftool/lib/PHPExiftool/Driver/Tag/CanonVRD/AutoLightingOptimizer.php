<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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

    protected $Id = 111;

    protected $Name = 'AutoLightingOptimizer';

    protected $FullName = 'CanonVRD::Ver2';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Auto Lighting Optimizer';

    protected $Values = array(
        100 => array(
            'Id' => 100,
            'Label' => 'Low',
        ),
        200 => array(
            'Id' => 200,
            'Label' => 'Standard',
        ),
        300 => array(
            'Id' => 300,
            'Label' => 'Strong',
        ),
        32767 => array(
            'Id' => 32767,
            'Label' => 'n/a',
        ),
    );

}
