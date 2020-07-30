<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Minolta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ZoneMatching extends AbstractTag
{

    protected $Id = 266;

    protected $Name = 'ZoneMatching';

    protected $FullName = 'Minolta::Main';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Zone Matching';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'ISO Setting Used',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'High Key',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Low Key',
        ),
    );

}
