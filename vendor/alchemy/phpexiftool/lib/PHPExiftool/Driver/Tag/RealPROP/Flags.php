<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RealPROP;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Flags extends AbstractTag
{

    protected $Id = 10;

    protected $Name = 'Flags';

    protected $FullName = 'Real::Properties';

    protected $GroupName = 'Real-PROP';

    protected $g0 = 'Real';

    protected $g1 = 'Real-PROP';

    protected $g2 = 'Video';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Flags';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Allow Recording',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Perfect Play',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Live',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Allow Download',
        ),
    );

}
