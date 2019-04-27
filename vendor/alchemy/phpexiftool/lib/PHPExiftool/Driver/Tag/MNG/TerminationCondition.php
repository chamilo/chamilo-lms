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
class TerminationCondition extends AbstractTag
{

    protected $Id = 5;

    protected $Name = 'TerminationCondition';

    protected $FullName = 'MNG::Loop';

    protected $GroupName = 'MNG';

    protected $g0 = 'MNG';

    protected $g1 = 'MNG';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Termination Condition';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Deterministic, not cacheable',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Decoder discretion, not cacheable',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'User discretion, not cacheable',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'External signal, not cacheable',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Deterministic, cacheable',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Decoder discretion, cacheable',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'User discretion, cacheable',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'External signal, cacheable',
        ),
    );

}
