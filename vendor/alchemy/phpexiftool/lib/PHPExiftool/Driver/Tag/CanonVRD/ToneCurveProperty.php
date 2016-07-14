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
class ToneCurveProperty extends AbstractTag
{

    protected $Id = 60;

    protected $Name = 'ToneCurveProperty';

    protected $FullName = 'CanonVRD::Ver1';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Tone Curve Property';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Shot Settings',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Linear',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Custom 1',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Custom 2',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Custom 3',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Custom 4',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Custom 5',
        ),
    );

}
