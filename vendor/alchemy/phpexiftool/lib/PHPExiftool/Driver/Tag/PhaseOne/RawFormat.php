<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PhaseOne;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RawFormat extends AbstractTag
{

    protected $Id = 270;

    protected $Name = 'RawFormat';

    protected $FullName = 'PhaseOne::Main';

    protected $GroupName = 'PhaseOne';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'PhaseOne';

    protected $g2 = 'Camera';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'Raw Format';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'RAW 1',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'RAW 2',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'IIQ L',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'IIQ S',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'IIQ Sv2',
        ),
    );

}
