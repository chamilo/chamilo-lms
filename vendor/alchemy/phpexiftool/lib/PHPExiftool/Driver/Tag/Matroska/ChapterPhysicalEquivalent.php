<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Matroska;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ChapterPhysicalEquivalent extends AbstractTag
{

    protected $Id = 9155;

    protected $Name = 'ChapterPhysicalEquivalent';

    protected $FullName = 'Matroska::Main';

    protected $GroupName = 'Matroska';

    protected $g0 = 'Matroska';

    protected $g1 = 'Matroska';

    protected $g2 = 'Video';

    protected $Type = 'unsigned';

    protected $Writable = false;

    protected $Description = 'Chapter Physical Equivalent';

    protected $Values = array(
        10 => array(
            'Id' => 10,
            'Label' => 'Index',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Track',
        ),
        30 => array(
            'Id' => 30,
            'Label' => 'Session',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'Layer',
        ),
        50 => array(
            'Id' => 50,
            'Label' => 'Side',
        ),
        60 => array(
            'Id' => 60,
            'Label' => 'CD / DVD',
        ),
        70 => array(
            'Id' => 70,
            'Label' => 'Set / Package',
        ),
    );

}
