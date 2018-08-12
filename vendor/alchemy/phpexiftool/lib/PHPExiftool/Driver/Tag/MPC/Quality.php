<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MPC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Quality extends AbstractTag
{

    protected $Id = 'Bit084-087';

    protected $Name = 'Quality';

    protected $FullName = 'MPC::Main';

    protected $GroupName = 'MPC';

    protected $g0 = 'MPC';

    protected $g1 = 'MPC';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Quality';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Unstable/Experimental',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 0,
        ),
        6 => array(
            'Id' => 6,
            'Label' => 1,
        ),
        7 => array(
            'Id' => 7,
            'Label' => '2 (Telephone)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '3 (Thumb)',
        ),
        9 => array(
            'Id' => 9,
            'Label' => '4 (Radio)',
        ),
        10 => array(
            'Id' => 10,
            'Label' => '5 (Standard)',
        ),
        11 => array(
            'Id' => 11,
            'Label' => '6 (Xtreme)',
        ),
        12 => array(
            'Id' => 12,
            'Label' => '7 (Insane)',
        ),
        13 => array(
            'Id' => 13,
            'Label' => '8 (BrainDead)',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 9,
        ),
        15 => array(
            'Id' => 15,
            'Label' => 10,
        ),
    );

}
