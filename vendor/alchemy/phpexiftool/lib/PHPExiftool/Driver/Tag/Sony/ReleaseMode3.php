<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ReleaseMode3 extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ReleaseMode3';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Release Mode 3';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Continuous',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Bracketing',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Continuous - Burst',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Continuous - Speed/Advance Priority',
        ),
    );

}
