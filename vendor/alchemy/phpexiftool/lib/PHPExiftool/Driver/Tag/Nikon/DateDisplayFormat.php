<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DateDisplayFormat extends AbstractTag
{

    protected $Id = 3;

    protected $Name = 'DateDisplayFormat';

    protected $FullName = 'Nikon::WorldTime';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Time';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Date Display Format';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Y/M/D',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'M/D/Y',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'D/M/Y',
        ),
    );

}
