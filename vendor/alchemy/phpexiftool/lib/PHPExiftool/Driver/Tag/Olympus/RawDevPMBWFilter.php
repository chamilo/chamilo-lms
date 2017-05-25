<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RawDevPMBWFilter extends AbstractTag
{

    protected $Id = 272;

    protected $Name = 'RawDevPM_BWFilter';

    protected $FullName = 'Olympus::RawDevelopment2';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Raw Dev PM BW Filter';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Neutral',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Yellow',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Orange',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Red',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Green',
        ),
    );

}
