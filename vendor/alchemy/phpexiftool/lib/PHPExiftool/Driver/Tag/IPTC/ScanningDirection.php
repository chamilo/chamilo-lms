<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IPTC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ScanningDirection extends AbstractTag
{

    protected $Id = 100;

    protected $Name = 'ScanningDirection';

    protected $FullName = 'IPTC::NewsPhoto';

    protected $GroupName = 'IPTC';

    protected $g0 = 'IPTC';

    protected $g1 = 'IPTC';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Scanning Direction';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'L-R, Top-Bottom',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'R-L, Top-Bottom',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'L-R, Bottom-Top',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'R-L, Bottom-Top',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Top-Bottom, L-R',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Bottom-Top, L-R',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Top-Bottom, R-L',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Bottom-Top, R-L',
        ),
    );

}
